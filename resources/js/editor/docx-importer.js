import JSZip from 'jszip';

/**
 * VelScribe High-Fidelity OpenXML (DOCX) Importer
 * Preserves alignments (center, right, justify, left), tables, colspans, borders,
 * cell background colors, bullet/numbered lists, underlines, strikethroughs,
 * font sizes, colors, highlights, embedded images (base64), and page breaks.
 */
export class DocxImporter {
    static async parse(arrayBuffer, filename = '') {
        const importer = new DocxImporter();
        return await importer.parseDocx(arrayBuffer, filename);
    }

    async parseDocx(arrayBuffer, filename = '') {
        const zip = await JSZip.loadAsync(arrayBuffer);
        const docFile = zip.file('word/document.xml');
        if (!docFile) {
            throw new Error('Berkas DOCX tidak valid (word/document.xml tidak ditemukan).');
        }

        const docXml = await docFile.async('string');

        // 1. Relationships (hyperlinks & images)
        const rels = await this.parseRelationships(zip);

        // 2. Numbering (bullet & ordered list definitions)
        const numberingMap = await this.parseNumbering(zip);

        // 3. Styles (headings, titles)
        const stylesMap = await this.parseStyles(zip);

        // 4. Parse Document DOM
        const parser = new DOMParser();
        const dom = parser.parseFromString(docXml, 'application/xml');

        // Check for XML parse errors
        const parseError = dom.querySelector('parsererror');
        if (parseError) {
            console.warn('XML Parse Warning in word/document.xml:', parseError.textContent);
        }

        const body = dom.getElementsByTagName('w:body')[0] || dom.getElementsByTagName('body')[0];
        if (!body) {
            return '<p>Dokumen Microsoft Word berhasil diimpor.</p>';
        }

        const blocks = [];
        let currentListItems = [];
        let currentListType = null; // 'ul' or 'ol'
        let currentListLevel = 0;

        const flushList = () => {
            if (currentListItems.length > 0) {
                const tag = (currentListType === 'ol') ? 'ol' : 'ul';
                const listClass = (tag === 'ol') ? 'list-decimal pl-6 my-3 space-y-1' : 'list-disc pl-6 my-3 space-y-1';
                const itemsHtml = currentListItems.map(item => `<li>${item}</li>`).join('\n');
                blocks.push(`<${tag} class="${listClass}">\n${itemsHtml}\n</${tag}>`);
                currentListItems = [];
                currentListType = null;
            }
        };

        const childNodes = Array.from(body.childNodes);
        for (const node of childNodes) {
            if (node.nodeType !== Node.ELEMENT_NODE) continue;

            const nodeName = node.nodeName.toLowerCase();

            if (nodeName === 'w:p') {
                const pResult = await this.parseParagraph(node, zip, rels, numberingMap, stylesMap);

                if (pResult.pageBreakBefore) {
                    flushList();
                    blocks.push(this.renderPageBreak());
                }

                if (pResult.isList) {
                    if (currentListType !== null && currentListType !== pResult.listType) {
                        flushList();
                    }
                    currentListType = pResult.listType;
                    currentListLevel = pResult.listLevel;
                    currentListItems.push(pResult.htmlContent);
                } else {
                    flushList();
                    if (pResult.html) {
                        blocks.push(pResult.html);
                    }
                }

                if (pResult.pageBreakAfter) {
                    flushList();
                    blocks.push(this.renderPageBreak());
                }
            } else if (nodeName === 'w:tbl') {
                flushList();
                const tblHtml = await this.parseTable(node, zip, rels, numberingMap, stylesMap);
                if (tblHtml) {
                    blocks.push(tblHtml);
                }
            } else if (nodeName === 'w:sdt') {
                // Structured Document Tag - extract contents
                const sdtContent = node.getElementsByTagName('w:sdtContent')[0] || node.getElementsByTagName('sdtContent')[0];
                if (sdtContent) {
                    const sdtChildren = Array.from(sdtContent.childNodes);
                    for (const sc of sdtChildren) {
                        if (sc.nodeType !== Node.ELEMENT_NODE) continue;
                        const scName = sc.nodeName.toLowerCase();
                        if (scName === 'w:p') {
                            const pResult = await this.parseParagraph(sc, zip, rels, numberingMap, stylesMap);
                            flushList();
                            if (pResult.html) blocks.push(pResult.html);
                        } else if (scName === 'w:tbl') {
                            flushList();
                            const tblHtml = await this.parseTable(sc, zip, rels, numberingMap, stylesMap);
                            if (tblHtml) blocks.push(tblHtml);
                        }
                    }
                }
            }
        }

        flushList();

        if (blocks.length === 0) {
            return '<p>Dokumen Microsoft Word berhasil diimpor.</p>';
        }

        return blocks.join('\n');
    }

    async parseRelationships(zip) {
        const rels = {};
        const relFile = zip.file('word/_rels/document.xml.rels');
        if (relFile) {
            const xml = await relFile.async('string');
            const parser = new DOMParser();
            const dom = parser.parseFromString(xml, 'application/xml');
            const relNodes = Array.from(dom.getElementsByTagName('Relationship'));
            for (const r of relNodes) {
                const id = r.getAttribute('Id');
                const type = r.getAttribute('Type');
                const target = r.getAttribute('Target');
                rels[id] = { type, target };
            }
        }
        return rels;
    }

    async parseNumbering(zip) {
        const map = {};
        const numFile = zip.file('word/numbering.xml');
        if (!numFile) return map;

        const xml = await numFile.async('string');
        const parser = new DOMParser();
        const dom = parser.parseFromString(xml, 'application/xml');

        const abstractNums = {};
        const abNodes = Array.from(dom.getElementsByTagName('w:abstractNum'));
        for (const ab of abNodes) {
            const abId = ab.getAttribute('w:abstractNumId');
            abstractNums[abId] = {};
            const lvlNodes = Array.from(ab.getElementsByTagName('w:lvl'));
            for (const lvl of lvlNodes) {
                const ilvl = lvl.getAttribute('w:ilvl');
                const numFmt = lvl.getElementsByTagName('w:numFmt')[0];
                const fmtVal = numFmt ? numFmt.getAttribute('w:val')?.toLowerCase() : 'bullet';
                abstractNums[abId][ilvl] = fmtVal;
            }
        }

        const numNodes = Array.from(dom.getElementsByTagName('w:num'));
        for (const num of numNodes) {
            const numId = num.getAttribute('w:numId');
            const abRef = num.getElementsByTagName('w:abstractNumId')[0];
            if (abRef) {
                const refId = abRef.getAttribute('w:val');
                map[numId] = abstractNums[refId] || {};
            }
        }

        return map;
    }

    async parseStyles(zip) {
        const map = {};
        const styleFile = zip.file('word/styles.xml');
        if (!styleFile) return map;

        const xml = await styleFile.async('string');
        const parser = new DOMParser();
        const dom = parser.parseFromString(xml, 'application/xml');

        const styles = Array.from(dom.getElementsByTagName('w:style'));
        for (const style of styles) {
            const type = style.getAttribute('w:type');
            if (type !== 'paragraph') continue;

            const styleId = style.getAttribute('w:styleId');
            const nameEl = style.getElementsByTagName('w:name')[0];
            const name = (nameEl ? nameEl.getAttribute('w:val') : styleId || '').toLowerCase();

            let headingLevel = null;
            const hMatch = name.match(/heading\s*(\d)/i) || name.match(/judul\s*(\d)/i);
            if (hMatch) {
                headingLevel = parseInt(hMatch[1], 10);
            } else if (name === 'title' || styleId === 'Title' || name === 'judul') {
                headingLevel = 1;
            } else if (name === 'subtitle' || styleId === 'Subtitle' || name === 'subjudul') {
                headingLevel = 2;
            }

            map[styleId] = { name, headingLevel };
        }

        return map;
    }

    async parseParagraph(p, zip, rels, numberingMap, stylesMap) {
        let pageBreakBefore = false;
        let pageBreakAfter = false;
        let headingLevel = null;
        let align = null; // 'center', 'right', 'justify', 'left'
        let isList = false;
        let listType = 'ul';
        let listLevel = 0;
        let pStyle = '';

        // Check paragraph properties
        const pPr = this.findFirstDirectChild(p, ['w:pPr', 'pPr']);
        if (pPr) {
            // Check pageBreakBefore
            if (pPr.getElementsByTagName('w:pageBreakBefore').length > 0) {
                pageBreakBefore = true;
            }

            // Check style
            const pStyleEl = pPr.getElementsByTagName('w:pStyle')[0];
            if (pStyleEl) {
                pStyle = pStyleEl.getAttribute('w:val') || '';
                const styleMeta = stylesMap[pStyle];
                if (styleMeta && styleMeta.headingLevel !== null) {
                    headingLevel = styleMeta.headingLevel;
                } else if (/^heading\s*(\d)/i.test(pStyle) || /^judul\s*(\d)/i.test(pStyle)) {
                    headingLevel = parseInt(pStyle.replace(/\D/g, ''), 10);
                } else if (/title|judul/i.test(pStyle)) {
                    headingLevel = 1;
                } else if (/subtitle|subjudul/i.test(pStyle)) {
                    headingLevel = 2;
                }
            }

            // Check alignment
            const jcEl = pPr.getElementsByTagName('w:jc')[0];
            if (jcEl) {
                const jcVal = (jcEl.getAttribute('w:val') || '').toLowerCase();
                if (jcVal === 'center') align = 'center';
                else if (jcVal === 'right' || jcVal === 'end') align = 'right';
                else if (jcVal === 'both' || jcVal === 'justify') align = 'justify';
                else if (jcVal === 'left' || jcVal === 'start') align = 'left';
            }

            // Check list properties
            const numPr = pPr.getElementsByTagName('w:numPr')[0];
            if (numPr) {
                const numIdEl = numPr.getElementsByTagName('w:numId')[0];
                const ilvlEl = numPr.getElementsByTagName('w:ilvl')[0];
                const numId = numIdEl ? numIdEl.getAttribute('w:val') : '1';
                listLevel = ilvlEl ? parseInt(ilvlEl.getAttribute('w:val'), 10) : 0;
                const fmt = numberingMap[numId] ? (numberingMap[numId][listLevel] || 'bullet') : 'bullet';
                isList = true;
                listType = (fmt === 'bullet') ? 'ul' : 'ol';
            }
        }

        // Parse runs and inline children
        const inlines = [];
        const childNodes = Array.from(p.childNodes);
        for (const child of childNodes) {
            if (child.nodeType !== Node.ELEMENT_NODE) continue;
            const childName = child.nodeName.toLowerCase();

            if (childName === 'w:r') {
                const rRes = await this.parseRun(child, zip, rels);
                if (rRes.isPageBreak) pageBreakAfter = true;
                if (rRes.html) inlines.push(rRes.html);
            } else if (childName === 'w:hyperlink') {
                const rId = child.getAttribute('r:id') || child.getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id');
                const targetUrl = (rels[rId] && rels[rId].target) ? rels[rId].target : '#';
                const linkParts = [];
                const subRuns = Array.from(child.getElementsByTagName('w:r'));
                for (const sr of subRuns) {
                    const srRes = await this.parseRun(sr, zip, rels);
                    if (srRes.html) linkParts.push(srRes.html);
                }
                const linkText = linkParts.join('');
                if (linkText) {
                    inlines.push(`<a href="${this.escapeHtml(targetUrl)}" target="_blank" class="text-indigo-600 dark:text-indigo-400 underline">${linkText}</a>`);
                }
            }
        }

        const innerHtml = inlines.join('').trim();
        const classes = [];
        const styles = [];

        if (align === 'center') {
            classes.push('text-center');
            styles.push('text-align: center;');
        } else if (align === 'right') {
            classes.push('text-right');
            styles.push('text-align: right;');
        } else if (align === 'justify') {
            classes.push('text-justify');
            styles.push('text-align: justify;');
        } else if (align === 'left') {
            classes.push('text-left');
            styles.push('text-align: left;');
        }

        const classStr = classes.length > 0 ? ` class="${classes.join(' ')}"` : '';
        const styleStr = styles.length > 0 ? ` style="${styles.join(' ')}"` : '';

        if (!innerHtml && !innerHtml.includes('<img')) {
            return {
                html: (pageBreakBefore || pageBreakAfter) ? '' : '<p><br /></p>',
                htmlContent: '',
                pageBreakBefore,
                pageBreakAfter,
                isList: false,
                listType: 'ul',
                listLevel: 0,
            };
        }

        if (isList) {
            return {
                html: '',
                htmlContent: innerHtml,
                pageBreakBefore,
                pageBreakAfter,
                isList: true,
                listType,
                listLevel,
            };
        }

        if (headingLevel !== null && headingLevel >= 1 && headingLevel <= 6) {
            const hTag = `h${headingLevel}`;
            const hBaseClass = (headingLevel === 1)
                ? 'heading-chapter font-bold text-xl my-6 ' + (align === 'center' ? 'text-center uppercase' : '')
                : ((headingLevel === 2) ? 'heading-section font-bold text-lg my-4' : 'heading-subsection font-semibold text-base my-3');

            const fullClass = `${hBaseClass} ${classes.join(' ')}`.trim();
            const hHtml = `<${hTag} class="${fullClass}"${styleStr}>${innerHtml}</${hTag}>`;
            return {
                html: hHtml,
                htmlContent: innerHtml,
                pageBreakBefore,
                pageBreakAfter,
                isList: false,
                listType: 'ul',
                listLevel: 0,
            };
        }

        // Table of contents style
        if (/toc|daftar isi/i.test(pStyle)) {
            const tocHtml = `<p class="toc-entry py-0.5 text-sm font-serif text-gray-800 dark:text-gray-200${classStr}"${styleStr}>${innerHtml}</p>`;
            return {
                html: tocHtml,
                htmlContent: innerHtml,
                pageBreakBefore,
                pageBreakAfter,
                isList: false,
                listType: 'ul',
                listLevel: 0,
            };
        }

        // Standard paragraph
        const pHtml = `<p${classStr}${styleStr}>${innerHtml}</p>`;
        return {
            html: pHtml,
            htmlContent: innerHtml,
            pageBreakBefore,
            pageBreakAfter,
            isList: false,
            listType: 'ul',
            listLevel: 0,
        };
    }

    async parseRun(r, zip, rels) {
        let isPageBreak = false;
        const htmlParts = [];

        let isBold = false;
        let isItalic = false;
        let isUnderline = false;
        let isStrike = false;
        let vertAlign = null;
        let color = null;
        let highlight = null;
        let fontSize = null;
        let fontFamily = null;

        const rPr = this.findFirstDirectChild(r, ['w:rPr', 'rPr']);
        if (rPr) {
            // Bold
            const bEl = rPr.getElementsByTagName('w:b')[0];
            if (bEl && bEl.getAttribute('w:val') !== '0' && bEl.getAttribute('w:val') !== 'false') {
                isBold = true;
            }
            // Italic
            const iEl = rPr.getElementsByTagName('w:i')[0];
            if (iEl && iEl.getAttribute('w:val') !== '0' && iEl.getAttribute('w:val') !== 'false') {
                isItalic = true;
            }
            // Underline
            const uEl = rPr.getElementsByTagName('w:u')[0];
            if (uEl && uEl.getAttribute('w:val') !== 'none') {
                isUnderline = true;
            }
            // Strike
            const sEl = rPr.getElementsByTagName('w:strike')[0] || rPr.getElementsByTagName('w:dstrike')[0];
            if (sEl && sEl.getAttribute('w:val') !== '0' && sEl.getAttribute('w:val') !== 'false') {
                isStrike = true;
            }
            // VertAlign
            const vaEl = rPr.getElementsByTagName('w:vertAlign')[0];
            if (vaEl) {
                vertAlign = (vaEl.getAttribute('w:val') || '').toLowerCase();
            }
            // Color
            const colEl = rPr.getElementsByTagName('w:color')[0];
            if (colEl) {
                const cVal = colEl.getAttribute('w:val') || '';
                if (cVal && cVal.toLowerCase() !== 'auto') {
                    color = '#' + cVal.replace(/^#/, '');
                }
            }
            // Highlight
            const hlEl = rPr.getElementsByTagName('w:highlight')[0];
            if (hlEl) {
                const hlVal = hlEl.getAttribute('w:val') || '';
                if (hlVal && hlVal.toLowerCase() !== 'none') {
                    highlight = hlVal.toLowerCase();
                }
            }
            // Font size (half-points)
            const szEl = rPr.getElementsByTagName('w:sz')[0];
            if (szEl) {
                const halfPts = parseInt(szEl.getAttribute('w:val') || '0', 10);
                if (halfPts >= 16 && halfPts <= 144) {
                    fontSize = `${halfPts / 2}pt`;
                }
            }
            // Font family
            const rfEl = rPr.getElementsByTagName('w:rFonts')[0];
            if (rfEl) {
                fontFamily = rfEl.getAttribute('w:ascii') || rfEl.getAttribute('w:hAnsi');
            }
        }

        // Child elements
        const childNodes = Array.from(r.childNodes);
        for (const child of childNodes) {
            if (child.nodeType !== Node.ELEMENT_NODE) continue;
            const childName = child.nodeName.toLowerCase();

            if (childName === 'w:t') {
                htmlParts.push(this.escapeHtml(child.textContent));
            } else if (childName === 'w:br') {
                const brType = child.getAttribute('w:type');
                if (brType === 'page') {
                    isPageBreak = true;
                } else {
                    htmlParts.push('<br />');
                }
            } else if (childName === 'w:tab') {
                htmlParts.push('&emsp;');
            } else if (childName === 'w:drawing' || childName === 'w:pict') {
                const imgHtml = await this.parseImage(child, zip, rels);
                if (imgHtml) htmlParts.push(imgHtml);
            }
        }

        let content = htmlParts.join('');
        if (!content) {
            return { html: '', isPageBreak };
        }

        if (isUnderline) content = `<u>${content}</u>`;
        if (isStrike) content = `<s>${content}</s>`;
        if (isItalic) content = `<em>${content}</em>`;
        if (isBold) content = `<strong>${content}</strong>`;
        if (vertAlign === 'superscript') content = `<sup>${content}</sup>`;
        else if (vertAlign === 'subscript') content = `<sub>${content}</sub>`;

        const styles = [];
        if (color) styles.push(`color: ${color};`);
        if (highlight) styles.push(`background-color: ${highlight};`);
        if (fontSize) styles.push(`font-size: ${fontSize};`);
        if (fontFamily) styles.push(`font-family: '${fontFamily}', serif;`);

        if (styles.length > 0) {
            content = `<span style="${styles.join(' ')}">${content}</span>`;
        }

        return { html: content, isPageBreak };
    }

    async parseTable(tbl, zip, rels, numberingMap, stylesMap) {
        const trNodes = Array.from(tbl.getElementsByTagName('w:tr'));
        if (trNodes.length === 0) return '';

        let tblAlign = '';
        const tblJc = tbl.getElementsByTagName('w:jc')[0];
        if (tblJc) {
            const alignVal = (tblJc.getAttribute('w:val') || '').toLowerCase();
            if (alignVal === 'center') tblAlign = ' mx-auto';
            else if (alignVal === 'right') tblAlign = ' ml-auto';
        }

        const headRows = [];
        const bodyRows = [];

        for (const tr of trNodes) {
            const isHeader = tr.getElementsByTagName('w:tblHeader').length > 0;
            const tcNodes = Array.from(tr.getElementsByTagName('w:tc'));
            const cellsHtml = [];

            for (const tc of tcNodes) {
                const cellTag = isHeader ? 'th' : 'td';
                const attrs = [];
                const classes = ['border', 'border-gray-300', 'dark:border-gray-700', 'p-2'];
                const styles = [];

                if (isHeader) {
                    classes.push('bg-gray-100', 'dark:bg-gray-800', 'font-semibold', 'text-center');
                }

                const tcPr = tc.getElementsByTagName('w:tcPr')[0];
                if (tcPr) {
                    // Colspan
                    const gridSpan = tcPr.getElementsByTagName('w:gridSpan')[0];
                    if (gridSpan) {
                        attrs.push(`colspan="${parseInt(gridSpan.getAttribute('w:val'), 10)}"`);
                    }

                    // Shading (background color)
                    const shd = tcPr.getElementsByTagName('w:shd')[0];
                    if (shd) {
                        const fill = (shd.getAttribute('w:fill') || '').toLowerCase();
                        if (fill && fill !== 'auto' && fill !== 'none' && fill !== 'ffffff') {
                            styles.push(`background-color: #${fill};`);
                        }
                    }

                    // Vertical align
                    const vAlign = tcPr.getElementsByTagName('w:vAlign')[0];
                    if (vAlign) {
                        styles.push(`vertical-align: ${vAlign.getAttribute('w:val').toLowerCase()};`);
                    }
                }

                // Parse cell paragraphs
                const cellBlocks = [];
                const tcParas = Array.from(tc.getElementsByTagName('w:p'));
                for (const tcp of tcParas) {
                    const pRes = await this.parseParagraph(tcp, zip, rels, numberingMap, stylesMap);
                    if (pRes.html) cellBlocks.push(pRes.html);
                }

                const cellContent = cellBlocks.length > 0 ? cellBlocks.join('') : '&nbsp;';
                const attrStr = attrs.length > 0 ? ` ${attrs.join(' ')}` : '';
                const classStr = ` class="${classes.join(' ')}"`;
                const styleStr = styles.length > 0 ? ` style="${styles.join(' ')}"` : '';

                cellsHtml.push(`<${cellTag}${attrStr}${classStr}${styleStr}>${cellContent}</${cellTag}>`);
            }

            const rowHtml = `<tr>\n${cellsHtml.join('\n')}\n</tr>`;
            if (isHeader) {
                headRows.push(rowHtml);
            } else {
                bodyRows.push(rowHtml);
            }
        }

        let tableHtml = `<table class="w-full border-collapse my-4 text-sm border border-gray-300 dark:border-gray-700${tblAlign}">\n`;
        if (headRows.length > 0) {
            tableHtml += `<thead>\n${headRows.join('\n')}\n</thead>\n`;
        }
        if (bodyRows.length > 0) {
            tableHtml += `<tbody>\n${bodyRows.join('\n')}\n</tbody>\n`;
        }
        tableHtml += '</table>';

        return tableHtml;
    }

    async parseImage(container, zip, rels) {
        let rId = null;

        // DrawingML
        const blip = container.getElementsByTagName('a:blip')[0];
        if (blip) {
            rId = blip.getAttribute('r:embed') || blip.getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'embed');
        }

        // VML
        if (!rId) {
            const vmlImg = container.getElementsByTagName('v:imagedata')[0];
            if (vmlImg) {
                rId = vmlImg.getAttribute('r:id') || vmlImg.getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id');
            }
        }

        if (!rId || !rels[rId]) return '';

        const target = rels[rId].target;
        const zipPath = target.startsWith('word/') ? target : `word/${target.replace(/^\//, '')}`;
        const imgFile = zip.file(zipPath);
        if (!imgFile) return '';

        const ext = target.split('.').pop().toLowerCase();
        const mime = {
            png: 'image/png',
            jpg: 'image/jpeg',
            jpeg: 'image/jpeg',
            gif: 'image/gif',
            webp: 'image/webp',
            svg: 'image/svg+xml',
        }[ext] || 'image/png';

        const base64 = await imgFile.async('base64');
        return `<img src="data:${mime};base64,${base64}" class="max-w-full h-auto my-2 inline-block rounded shadow-xs" alt="Dokumen Gambar" />`;
    }

    renderPageBreak() {
        return '<div class="page-break my-8 border-b-2 border-dashed border-gray-400 dark:border-gray-600 text-center py-2 select-none" data-page-break="true"><span class="bg-gray-100 dark:bg-gray-800 px-3 py-1 rounded-full text-xs text-gray-500 font-mono">Pemisah Halaman</span></div>';
    }

    findFirstDirectChild(element, tagNames) {
        for (const child of Array.from(element.childNodes)) {
            if (child.nodeType === Node.ELEMENT_NODE && tagNames.includes(child.nodeName)) {
                return child;
            }
        }
        return null;
    }

    escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
}
