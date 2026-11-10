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

        this.defaultFontFamily = null;

        // 1. Relationships (hyperlinks & images)
        const rels = await this.parseRelationships(zip);

        // 2. Theme colors
        const themeColors = await this.parseThemeColors(zip);

        // 3. Numbering (bullet & ordered list definitions)
        const numberingMap = await this.parseNumbering(zip);

        // 4. Styles (headings, titles, font, colors)
        const stylesMap = await this.parseStyles(zip, themeColors);

        // 5. Extract Header & Footer text from Word archive
        const { headerText, footerText } = await this.parseHeadersAndFooters(zip, rels, docXml);

        // 6. Extract "Different First Page" setting from Word section properties
        const differentFirstPage = /<w:titlePg\s*\/?>/i.test(docXml);

        // 6b. Extract Section Page Setup (Paper size, Orientation, Margins)
        const pageSetup = this.parsePageSetup(docXml);

        // 7. Parse Document DOM
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
                const pResult = await this.parseParagraph(node, zip, rels, numberingMap, stylesMap, themeColors);

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
                const tblHtml = await this.parseTable(node, zip, rels, numberingMap, stylesMap, themeColors);
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
                            const pResult = await this.parseParagraph(sc, zip, rels, numberingMap, stylesMap, themeColors);
                            flushList();
                            if (pResult.html) blocks.push(pResult.html);
                        } else if (scName === 'w:tbl') {
                            flushList();
                            const tblHtml = await this.parseTable(sc, zip, rels, numberingMap, stylesMap, themeColors);
                            if (tblHtml) blocks.push(tblHtml);
                        }
                    }
                }
            }
        }

        flushList();

        const finalHtml = blocks.length > 0 ? blocks.join('\n') : '<p>Dokumen Microsoft Word berhasil diimpor.</p>';

        return {
            html: finalHtml,
            headerText,
            footerText,
            differentFirstPage,
            defaultFontFamily: this.defaultFontFamily,
            pageSetup,
            toString() {
                return this.html;
            },
        };
    }

    parsePageSetup(docXml = '') {
        const setup = {
            paperSize: 'a4',
            orientation: 'portrait',
            widthMm: 210.0,
            heightMm: 297.0,
            marginPreset: 'normal',
            margins: {
                top: 25.4,
                bottom: 25.4,
                left: 25.4,
                right: 25.4,
                gutter: 0,
            },
        };

        const szMatch = docXml.match(/<w:pgSz\b([^>]*)\/?>/i);
        if (szMatch) {
            const attrs = szMatch[1];
            const wm = attrs.match(/w:w=["'](\d+)["']/i);
            const hm = attrs.match(/w:h=["'](\d+)["']/i);
            const om = attrs.match(/w:orient=["'](landscape|portrait)["']/i);

            if (om) setup.orientation = om[1].toLowerCase();

            if (wm && hm) {
                const wMm = Math.round(((parseInt(wm[1], 10) * 25.4) / 1440) * 10) / 10;
                const hMm = Math.round(((parseInt(hm[1], 10) * 25.4) / 1440) * 10) / 10;
                setup.widthMm = wMm;
                setup.heightMm = hMm;
                if (wMm > hMm && !om) setup.orientation = 'landscape';

                const minDim = Math.min(wMm, hMm);
                const maxDim = Math.max(wMm, hMm);

                if (Math.abs(minDim - 210.0) <= 3 && Math.abs(maxDim - 297.0) <= 3) setup.paperSize = 'a4';
                else if (Math.abs(minDim - 215.9) <= 3 && Math.abs(maxDim - 279.4) <= 3) setup.paperSize = 'letter';
                else if (Math.abs(minDim - 215.9) <= 3 && Math.abs(maxDim - 355.6) <= 3) setup.paperSize = 'legal';
                else if (Math.abs(minDim - 215.0) <= 3 && Math.abs(maxDim - 330.0) <= 3) setup.paperSize = 'folio';
                else if (Math.abs(minDim - 297.0) <= 3 && Math.abs(maxDim - 420.0) <= 3) setup.paperSize = 'a3';
                else if (Math.abs(minDim - 148.0) <= 3 && Math.abs(maxDim - 210.0) <= 3) setup.paperSize = 'a5';
                else if (Math.abs(minDim - 182.0) <= 3 && Math.abs(maxDim - 257.0) <= 3) setup.paperSize = 'b5';
                else if (Math.abs(minDim - 184.1) <= 3 && Math.abs(maxDim - 266.7) <= 3) setup.paperSize = 'executive';
                else setup.paperSize = 'custom';
            }
        }

        const marMatch = docXml.match(/<w:pgMar\b([^>]*)\/?>/i);
        if (marMatch) {
            const attrs = marMatch[1];
            const topM = attrs.match(/w:top=["'](\d+)["']/i);
            const botM = attrs.match(/w:bottom=["'](\d+)["']/i);
            const leftM = attrs.match(/w:left=["'](\d+)["']/i);
            const rightM = attrs.match(/w:right=["'](\d+)["']/i);
            const gutM = attrs.match(/w:gutter=["'](\d+)["']/i);

            const top = topM ? Math.round(((parseInt(topM[1], 10) * 25.4) / 1440) * 10) / 10 : 25.4;
            const bottom = botM ? Math.round(((parseInt(botM[1], 10) * 25.4) / 1440) * 10) / 10 : 25.4;
            const left = leftM ? Math.round(((parseInt(leftM[1], 10) * 25.4) / 1440) * 10) / 10 : 25.4;
            const right = rightM ? Math.round(((parseInt(rightM[1], 10) * 25.4) / 1440) * 10) / 10 : 25.4;
            const gutter = gutM ? Math.round(((parseInt(gutM[1], 10) * 25.4) / 1440) * 10) / 10 : 0;

            setup.margins = { top, bottom, left, right, gutter };

            if (Math.abs(top - 40) <= 2 && Math.abs(left - 40) <= 2 && Math.abs(bottom - 30) <= 2 && Math.abs(right - 30) <= 2) {
                setup.marginPreset = 'skripsi';
            } else if (Math.abs(top - 12.7) <= 2 && Math.abs(bottom - 12.7) <= 2 && Math.abs(left - 12.7) <= 2 && Math.abs(right - 12.7) <= 2) {
                setup.marginPreset = 'narrow';
            } else if (Math.abs(top - 25.4) <= 2 && Math.abs(bottom - 25.4) <= 2 && Math.abs(left - 19.1) <= 2 && Math.abs(right - 19.1) <= 2) {
                setup.marginPreset = 'moderate';
            } else if (Math.abs(top - 25.4) <= 2 && Math.abs(bottom - 25.4) <= 2 && Math.abs(left - 50.8) <= 2 && Math.abs(right - 50.8) <= 2) {
                setup.marginPreset = 'wide';
            } else if (Math.abs(top - 25.4) <= 2 && Math.abs(bottom - 25.4) <= 2 && Math.abs(left - 25.4) <= 2 && Math.abs(right - 25.4) <= 2) {
                setup.marginPreset = 'normal';
            } else {
                setup.marginPreset = 'custom';
            }
        }

        return setup;
    }

    async parseHeadersAndFooters(zip, rels = {}, docXml = '') {
        let headerText = '';
        let footerText = '';

        const resolveTarget = (target) => {
            if (!target) return '';
            let clean = target.replace(/^\//, '').replace(/^\.\//, '');
            if (clean.startsWith('../')) {
                clean = clean.replace(/^\.\.\//, '');
            }
            return clean.startsWith('word/') ? clean : `word/${clean}`;
        };

        const extractCleanText = (xmlStr) => {
            if (!xmlStr) return '';
            try {
                const parser = new DOMParser();
                const dom = parser.parseFromString(xmlStr, 'application/xml');
                const paragraphs = Array.from(dom.querySelectorAll('p, w\\:p'));
                let lines = [];
                if (paragraphs.length > 0) {
                    lines = paragraphs.map(p => {
                        const tNodes = Array.from(p.querySelectorAll('t, w\\:t'));
                        return tNodes.map(n => n.textContent || '').join('');
                    });
                } else {
                    const tNodes = Array.from(dom.querySelectorAll('t, w\\:t'));
                    lines = [tNodes.map(n => n.textContent || '').join('')];
                }

                let full = lines.join(' ')
                    .replace(/\bPAGE\b|\bNUMPAGES\b|\bMERGEFORMAT\b|[\\*]/gi, '')
                    .replace(/\s+/g, ' ')
                    .trim();

                if (full && !/^\d+$/.test(full)) {
                    return full;
                }
            } catch (e) {
                // fallback regex extraction
                const matches = Array.from(xmlStr.matchAll(/<w:t[^>]*>([^<]+)<\/w:t>/g)).map(m => m[1]);
                let full = matches.join(' ')
                    .replace(/\bPAGE\b|\bNUMPAGES\b|\bMERGEFORMAT\b|[\\*]/gi, '')
                    .replace(/\s+/g, ' ')
                    .trim();
                if (full && !/^\d+$/.test(full)) return full;
            }
            return '';
        };

        // 1. Try to resolve referenced default header & footer from section properties (w:sectPr)
        if (docXml && rels) {
            const defaultHeaderMatch = docXml.match(/<w:headerReference[^>]*w:type=["']default["'][^>]*r:id=["']([^"']+)["']/i) ||
                                       docXml.match(/<w:headerReference[^>]*r:id=["']([^"']+)["'][^>]*w:type=["']default["']/i) ||
                                       docXml.match(/<w:headerReference[^>]*r:id=["']([^"']+)["']/i);
            if (defaultHeaderMatch) {
                const rId = defaultHeaderMatch[1];
                const rel = rels[rId];
                if (rel && rel.target) {
                    const targetFile = resolveTarget(rel.target);
                    const f = zip.file(targetFile);
                    if (f) {
                        const xml = await f.async('string');
                        const txt = extractCleanText(xml);
                        if (txt) headerText = txt;
                    }
                }
            }

            const defaultFooterMatch = docXml.match(/<w:footerReference[^>]*w:type=["']default["'][^>]*r:id=["']([^"']+)["']/i) ||
                                       docXml.match(/<w:footerReference[^>]*r:id=["']([^"']+)["'][^>]*w:type=["']default["']/i) ||
                                       docXml.match(/<w:footerReference[^>]*r:id=["']([^"']+)["']/i);
            if (defaultFooterMatch) {
                const rId = defaultFooterMatch[1];
                const rel = rels[rId];
                if (rel && rel.target) {
                    const targetFile = resolveTarget(rel.target);
                    const f = zip.file(targetFile);
                    if (f) {
                        const xml = await f.async('string');
                        const txt = extractCleanText(xml);
                        if (txt) footerText = txt;
                    }
                }
            }
        }

        // 2. Check relationship entries matching header or footer types
        if ((!headerText || !footerText) && rels) {
            for (const rel of Object.values(rels)) {
                if (!headerText && rel.type && rel.type.includes('/header')) {
                    const targetFile = resolveTarget(rel.target);
                    const f = zip.file(targetFile);
                    if (f) {
                        const xml = await f.async('string');
                        const txt = extractCleanText(xml);
                        if (txt) headerText = txt;
                    }
                }
                if (!footerText && rel.type && rel.type.includes('/footer')) {
                    const targetFile = resolveTarget(rel.target);
                    const f = zip.file(targetFile);
                    if (f) {
                        const xml = await f.async('string');
                        const txt = extractCleanText(xml);
                        if (txt) footerText = txt;
                    }
                }
            }
        }

        // 3. Scan all header files in zip if still not found
        if (!headerText) {
            const headerFiles = zip.file(/^word\/header\d*\.xml$/i) || [];
            headerFiles.sort((a, b) => b.name.localeCompare(a.name)); // check header2, header1
            for (const hFile of headerFiles) {
                const xml = await hFile.async('string');
                const txt = extractCleanText(xml);
                if (txt) {
                    headerText = txt;
                    break;
                }
            }
        }

        // 4. Scan all footer files in zip if still not found
        if (!footerText) {
            const footerFiles = zip.file(/^word\/footer\d*\.xml$/i) || [];
            footerFiles.sort((a, b) => b.name.localeCompare(a.name));
            for (const fFile of footerFiles) {
                const xml = await fFile.async('string');
                const txt = extractCleanText(xml);
                if (txt) {
                    footerText = txt;
                    break;
                }
            }
        }

        return { headerText, footerText };
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

    static isNearWhiteColor(color) {
        if (!color) return false;
        const cl = color.toLowerCase().trim();
        if (['#ffffff', '#fff', 'white', '#f8f9fa', '#f3f4f6', '#f1f5f9', '#f9fafb', '#eeece1', '#eee', '#ddd', '#e5e7eb'].includes(cl)) return true;
        if (/^#([0-9a-f]{6})$/i.test(cl)) {
            const r = parseInt(cl.slice(1, 3), 16);
            const g = parseInt(cl.slice(3, 5), 16);
            const b = parseInt(cl.slice(5, 7), 16);
            const brightness = (r * 299 + g * 587 + b * 114) / 1000;
            return brightness > 195 || (r > 205 && g > 205 && b > 205);
        }
        return false;
    }

    static ommlToLatex(node) {
        if (!node || node.nodeType !== Node.ELEMENT_NODE) return '';
        const tag = node.nodeName.toLowerCase().replace(/^m:/, '');

        switch (tag) {
            case 't': {
                let text = node.textContent || '';
                text = text.replace(/(?<!\\)%/g, '\\%');
                return text;
            }
            case 'r': {
                let out = '';
                for (const c of node.childNodes) out += DocxImporter.ommlToLatex(c);
                return out;
            }
            case 'f': {
                let num = '';
                let den = '';
                for (const c of node.childNodes) {
                    const ctag = c.nodeName.toLowerCase().replace(/^m:/, '');
                    if (ctag === 'num') num = DocxImporter.ommlToLatex(c);
                    else if (ctag === 'den') den = DocxImporter.ommlToLatex(c);
                }
                return `\\frac{${num.trim()}}{${den.trim()}}`;
            }
            case 'ssup': {
                let base = '';
                let sup = '';
                for (const c of node.childNodes) {
                    const ctag = c.nodeName.toLowerCase().replace(/^m:/, '');
                    if (ctag === 'e') base = DocxImporter.ommlToLatex(c);
                    else if (ctag === 'sup') sup = DocxImporter.ommlToLatex(c);
                }
                return `{${base.trim()}}^{${sup.trim()}}`;
            }
            case 'ssub': {
                let base = '';
                let sub = '';
                for (const c of node.childNodes) {
                    const ctag = c.nodeName.toLowerCase().replace(/^m:/, '');
                    if (ctag === 'e') base = DocxImporter.ommlToLatex(c);
                    else if (ctag === 'sub') sub = DocxImporter.ommlToLatex(c);
                }
                return `{${base.trim()}}_{${sub.trim()}}`;
            }
            case 'ssubsup': {
                let base = '';
                let sub = '';
                let sup = '';
                for (const c of node.childNodes) {
                    const ctag = c.nodeName.toLowerCase().replace(/^m:/, '');
                    if (ctag === 'e') base = DocxImporter.ommlToLatex(c);
                    else if (ctag === 'sub') sub = DocxImporter.ommlToLatex(c);
                    else if (ctag === 'sup') sup = DocxImporter.ommlToLatex(c);
                }
                return `{${base.trim()}}_{${sub.trim()}}^{${sup.trim()}}`;
            }
            case 'rad': {
                let deg = '';
                let e = '';
                for (const c of node.childNodes) {
                    const ctag = c.nodeName.toLowerCase().replace(/^m:/, '');
                    if (ctag === 'deg') deg = DocxImporter.ommlToLatex(c);
                    else if (ctag === 'e') e = DocxImporter.ommlToLatex(c);
                }
                return deg ? `\\sqrt[${deg.trim()}]{${e.trim()}}` : `\\sqrt{${e.trim()}}`;
            }
            case 'nary': {
                let chr = '\\sum';
                let sub = '';
                let sup = '';
                let e = '';
                for (const c of node.childNodes) {
                    const ctag = c.nodeName.toLowerCase().replace(/^m:/, '');
                    if (ctag === 'narypr') {
                        const chrEl = c.querySelector('chr, [m\\:val]');
                        if (chrEl) {
                            const v = chrEl.getAttribute('m:val') || chrEl.getAttribute('val');
                            if (v === '∫' || v === 'int') chr = '\\int';
                            else if (v === '∏' || v === 'prod') chr = '\\prod';
                            else if (v === '∑' || v === 'sum') chr = '\\sum';
                            else if (v) chr = v;
                        }
                    } else if (ctag === 'sub') sub = DocxImporter.ommlToLatex(c);
                    else if (ctag === 'sup') sup = DocxImporter.ommlToLatex(c);
                    else if (ctag === 'e') e = DocxImporter.ommlToLatex(c);
                }
                let limits = '';
                if (sub) limits += `_{${sub.trim()}}`;
                if (sup) limits += `^{${sup.trim()}}`;
                return `${chr}${limits}{${e.trim()}}`;
            }
            case 'd': {
                let e = '';
                for (const c of node.childNodes) {
                    const ctag = c.nodeName.toLowerCase().replace(/^m:/, '');
                    if (ctag === 'e') e += DocxImporter.ommlToLatex(c);
                }
                return `\\left( ${e.trim()} \\right)`;
            }
            default: {
                let result = '';
                for (const c of node.childNodes) result += DocxImporter.ommlToLatex(c);
                return result;
            }
        }
    }

    static formatFontFamily(fontName) {
        if (!fontName) return '';
        const clean = fontName.replace(/['"]/g, '').trim();
        if (!clean) return '';
        const lower = clean.toLowerCase();
        const sansFonts = [
            'arial', 'calibri', 'helvetica', 'segoe ui', 'tahoma', 'verdana',
            'trebuchet ms', 'open sans', 'roboto', 'century gothic', 'instrument sans',
            'aptos', 'sans-serif',
        ];
        const monoFonts = [
            'courier', 'courier new', 'consolas', 'monaco', 'menlo',
            'liberation mono', 'source code pro', 'monospace',
        ];

        let fallback = 'serif';
        for (const sans of sansFonts) {
            if (lower.includes(sans)) {
                fallback = 'sans-serif';
                break;
            }
        }
        if ($fallback === 'serif') {
            for (const mono of monoFonts) {
                if (lower.includes(mono)) {
                    fallback = 'monospace';
                    break;
                }
            }
        }
        return `'${clean}', ${fallback}`;
    }

    async parseThemeColors(zip) {
        const colors = {
            dark1: '#000000',
            light1: '#ffffff',
            dark2: '#1f497d',
            light2: '#eeece1',
            accent1: '#4f81bd',
            accent2: '#c0504d',
            accent3: '#9bbb59',
            accent4: '#8064a2',
            accent5: '#4bacc6',
            accent6: '#f79646',
            hlink: '#0563c1',
            folHlink: '#954f72',
        };

        const themeFile = zip.file('word/theme/theme1.xml');
        if (themeFile) {
            try {
                const xml = await themeFile.async('string');
                const parser = new DOMParser();
                const dom = parser.parseFromString(xml, 'application/xml');
                const schemeNodes = dom.querySelectorAll('clrScheme > *');
                for (const node of schemeNodes) {
                    const colorName = node.localName;
                    const srgb = node.querySelector('srgbClr');
                    if (srgb && srgb.getAttribute('val')) {
                        colors[colorName] = `#${srgb.getAttribute('val')}`;
                    } else {
                        const sys = node.querySelector('sysClr');
                        if (sys && sys.getAttribute('lastClr')) {
                            colors[colorName] = `#${sys.getAttribute('lastClr')}`;
                        }
                    }
                }
            } catch (e) {
                console.warn('Failed parsing theme colors:', e);
            }
        }
        return colors;
    }

    resolveColor(val, themeColor, themeColors = {}) {
        if (val && val.toLowerCase() !== 'auto') {
            return `#${val.replace(/^#/, '')}`;
        }
        if (themeColor && themeColors[themeColor]) {
            return themeColors[themeColor];
        }
        return null;
    }

    async parseStyles(zip, themeColors = {}) {
        const map = {};
        const styleFile = zip.file('word/styles.xml');
        if (!styleFile) return map;

        const xml = await styleFile.async('string');
        const parser = new DOMParser();
        const dom = parser.parseFromString(xml, 'application/xml');

        // Document defaults
        const docDefFontEl = dom.querySelector('docDefaults rFonts');
        if (docDefFontEl) {
            const fontName = docDefFontEl.getAttribute('w:ascii') || docDefFontEl.getAttribute('w:hAnsi');
            if (fontName) {
                this.defaultFontFamily = DocxImporter.formatFontFamily(fontName);
            }
        }

        const styles = Array.from(dom.getElementsByTagName('w:style'));
        for (const style of styles) {
            const type = style.getAttribute('w:type');
            if (type !== 'paragraph' && type !== 'character') continue;

            const styleId = style.getAttribute('w:styleId');
            const nameEl = style.getElementsByTagName('w:name')[0];
            const name = (nameEl ? nameEl.getAttribute('w:val') : styleId || '').toLowerCase();

            let headingLevel = null;
            if (type === 'paragraph') {
                const hMatch = name.match(/heading\s*(\d)/i) || name.match(/judul\s*(\d)/i);
                if (hMatch) {
                    headingLevel = parseInt(hMatch[1], 10);
                } else if (name === 'title' || styleId === 'Title' || name === 'judul') {
                    headingLevel = 1;
                } else if (name === 'subtitle' || styleId === 'Subtitle' || name === 'subjudul') {
                    headingLevel = 2;
                }
            }

            // Extract rPr from style
            let font = null;
            const rfEl = style.getElementsByTagName('w:rFonts')[0];
            if (rfEl) {
                font = rfEl.getAttribute('w:ascii') || rfEl.getAttribute('w:hAnsi');
                if (!this.defaultFontFamily && (styleId === 'Normal' || name === 'normal')) {
                    this.defaultFontFamily = DocxImporter.formatFontFamily(font);
                }
            }

            let color = null;
            const colEl = style.getElementsByTagName('w:color')[0];
            if (colEl) {
                color = this.resolveColor(colEl.getAttribute('w:val'), colEl.getAttribute('w:themeColor'), themeColors);
            }

            let fontSize = null;
            const szEl = style.getElementsByTagName('w:sz')[0];
            if (szEl) {
                const halfPts = parseInt(szEl.getAttribute('w:val') || '0', 10);
                if (halfPts >= 16 && halfPts <= 144) {
                    fontSize = `${halfPts / 2}pt`;
                }
            }

            const bEl = style.getElementsByTagName('w:b')[0];
            const isBold = bEl && bEl.getAttribute('w:val') !== '0' && bEl.getAttribute('w:val') !== 'false';

            const iEl = style.getElementsByTagName('w:i')[0];
            const isItalic = iEl && iEl.getAttribute('w:val') !== '0' && iEl.getAttribute('w:val') !== 'false';

            map[styleId] = {
                type,
                name,
                headingLevel,
                fontFamily: font,
                color,
                fontSize,
                isBold: Boolean(isBold),
                isItalic: Boolean(isItalic),
            };
        }

        return map;
    }

    async parseParagraph(p, zip, rels, numberingMap, stylesMap, themeColors = {}) {
        let pageBreakBefore = false;
        let pageBreakAfter = false;
        let headingLevel = null;
        let align = null; // 'center', 'right', 'justify', 'left'
        let isList = false;
        let listType = 'ul';
        let listLevel = 0;
        let pStyle = '';

        const pDefaults = {
            fontFamily: null,
            color: null,
            fontSize: null,
            isBold: false,
            isItalic: false,
        };

        let pSpacing = null;
        let pInd = null;

        // Check paragraph properties
        const pPr = this.findFirstDirectChild(p, ['w:pPr', 'pPr']);
        if (pPr) {
            pSpacing = pPr.getElementsByTagName('w:spacing')[0] || null;
            pInd = pPr.getElementsByTagName('w:ind')[0] || null;
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

        // If style defines font, color, etc., seed pDefaults
        if (pStyle && stylesMap[pStyle]) {
            const sMeta = stylesMap[pStyle];
            if (sMeta.fontFamily) pDefaults.fontFamily = sMeta.fontFamily;
            if (sMeta.color) pDefaults.color = sMeta.color;
            if (sMeta.fontSize) pDefaults.fontSize = sMeta.fontSize;
            if (sMeta.isBold) pDefaults.isBold = true;
            if (sMeta.isItalic) pDefaults.isItalic = true;
        }

        // Check paragraph-level run properties <w:pPr><w:rPr>
        if (pPr) {
            const pPrRPr = pPr.getElementsByTagName('w:rPr')[0] || pPr.getElementsByTagName('rPr')[0];
            if (pPrRPr) {
                const pRf = pPrRPr.getElementsByTagName('w:rFonts')[0] || pPrRPr.getElementsByTagName('rFonts')[0];
                if (pRf) {
                    const pFont = pRf.getAttribute('w:ascii') || pRf.getAttribute('w:hAnsi');
                    if (pFont) pDefaults.fontFamily = pFont;
                }
                const pCol = pPrRPr.getElementsByTagName('w:color')[0] || pPrRPr.getElementsByTagName('color')[0];
                if (pCol) {
                    const cVal = pCol.getAttribute('w:val');
                    const tCol = pCol.getAttribute('w:themeColor');
                    const resolved = this.resolveColor(cVal, tCol, themeColors);
                    if (resolved) pDefaults.color = resolved;
                }
                const pSz = pPrRPr.getElementsByTagName('w:sz')[0] || pPrRPr.getElementsByTagName('sz')[0];
                if (pSz) {
                    const halfPts = parseInt(pSz.getAttribute('w:val') || '0', 10);
                    if (halfPts >= 16 && halfPts <= 144) {
                        pDefaults.fontSize = `${halfPts / 2}pt`;
                    }
                }
            }
        }

        // Parse runs and inline children
        const inlines = [];
        const childNodes = Array.from(p.childNodes);
        for (const child of childNodes) {
            if (child.nodeType !== Node.ELEMENT_NODE) continue;
            const childName = child.nodeName.toLowerCase();

            if (childName === 'w:r') {
                const rRes = await this.parseRun(child, zip, rels, pDefaults, themeColors);
                if (rRes.isPageBreak) pageBreakAfter = true;
                if (rRes.html) inlines.push(rRes.html);
            } else if (childName === 'w:hyperlink') {
                const rId = child.getAttribute('r:id') || child.getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id');
                const targetUrl = (rels[rId] && rels[rId].target) ? rels[rId].target : '#';
                const linkParts = [];
                const subRuns = Array.from(child.getElementsByTagName('w:r'));
                for (const sr of subRuns) {
                    const srRes = await this.parseRun(sr, zip, rels, pDefaults, themeColors);
                    if (srRes.html) linkParts.push(srRes.html);
                }
                const linkText = linkParts.join('');
                if (linkText) {
                    inlines.push(`<a href="${this.escapeHtml(targetUrl)}" target="_blank" class="text-indigo-600 dark:text-indigo-400 underline">${linkText}</a>`);
                }
            } else if (childName === 'm:omath' || childName === 'm:omathpara' || childName === 'omath' || childName === 'omathpara') {
                let latex = DocxImporter.ommlToLatex(child).trim();
                if (latex) {
                    latex = latex.replace(/(?<!\\)%/g, '\\%');
                    inlines.push(` $$${latex}$$ `);
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

        if (pSpacing) {
            const bTwips = pSpacing.getAttribute('w:before');
            if (bTwips && !isNaN(bTwips)) {
                const bPt = Math.round((parseInt(bTwips, 10) / 20) * 10) / 10;
                if (bPt > 0) styles.push(`margin-top: ${bPt}pt;`);
            }
            const aTwips = pSpacing.getAttribute('w:after');
            if (aTwips && !isNaN(aTwips)) {
                const aPt = Math.round((parseInt(aTwips, 10) / 20) * 10) / 10;
                if (aPt > 0) styles.push(`margin-bottom: ${aPt}pt;`);
            }
            const lTwips = pSpacing.getAttribute('w:line');
            const lRule = pSpacing.getAttribute('w:lineRule');
            if (lTwips && !isNaN(lTwips)) {
                if (lRule === 'exact' || lRule === 'atLeast') {
                    const lPt = Math.round((parseInt(lTwips, 10) / 20) * 10) / 10;
                    styles.push(`line-height: ${lPt}pt;`);
                } else {
                    const lh = Math.round((parseInt(lTwips, 10) / 240) * 100) / 100;
                    if (lh >= 0.8 && lh <= 4.0) styles.push(`line-height: ${lh};`);
                }
            }
        }

        if (pInd) {
            const lTwips = pInd.getAttribute('w:left');
            if (lTwips && !isNaN(lTwips) && parseInt(lTwips, 10) > 0) {
                const lCm = Math.round(((parseInt(lTwips, 10) * 2.54) / 1440) * 100) / 100;
                styles.push(`margin-left: ${lCm}cm;`);
            }
            const rTwips = pInd.getAttribute('w:right');
            if (rTwips && !isNaN(rTwips) && parseInt(rTwips, 10) > 0) {
                const rCm = Math.round(((parseInt(rTwips, 10) * 2.54) / 1440) * 100) / 100;
                styles.push(`margin-right: ${rCm}cm;`);
            }
            const hTwips = pInd.getAttribute('w:hanging');
            const flTwips = pInd.getAttribute('w:firstLine');
            if (hTwips && !isNaN(hTwips) && parseInt(hTwips, 10) > 0) {
                const hCm = Math.round(((parseInt(hTwips, 10) * 2.54) / 1440) * 100) / 100;
                classes.push('indent-hanging');
                styles.push(`text-indent: -${hCm}cm; padding-left: ${hCm}cm;`);
            } else if (flTwips && !isNaN(flTwips) && parseInt(flTwips, 10) > 0) {
                const flCm = Math.round(((parseInt(flTwips, 10) * 2.54) / 1440) * 100) / 100;
                classes.push('indent-first-line');
                styles.push(`text-indent: ${flCm}cm;`);
            }
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

            const hStyles = [...styles];
            if (pDefaults.color && pDefaults.color !== '#000000' && pDefaults.color !== '#111827' && !DocxImporter.isNearWhiteColor(pDefaults.color)) {
                hStyles.push(`color: ${pDefaults.color};`);
            }
            if (pDefaults.fontFamily) {
                hStyles.push(`font-family: ${DocxImporter.formatFontFamily(pDefaults.fontFamily)};`);
            }

            const fullClass = `${hBaseClass} ${classes.join(' ')}`.trim();
            const hStyleStr = hStyles.length > 0 ? ` style="${hStyles.join(' ')}"` : '';
            const hHtml = `<${hTag} class="${fullClass}"${hStyleStr}>${innerHtml}</${hTag}>`;
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

    async parseRun(r, zip, rels, pDefaults = {}, themeColors = {}) {
        let isPageBreak = false;
        const htmlParts = [];

        let isBold = Boolean(pDefaults.isBold);
        let isItalic = Boolean(pDefaults.isItalic);
        let isUnderline = false;
        let isStrike = false;
        let vertAlign = null;
        let color = pDefaults.color || null;
        let highlight = null;
        let fontSize = pDefaults.fontSize || null;
        let fontFamily = pDefaults.fontFamily || null;

        const rPr = this.findFirstDirectChild(r, ['w:rPr', 'rPr']);
        if (rPr) {
            // Bold
            const bEl = rPr.getElementsByTagName('w:b')[0];
            if (bEl) {
                const bVal = bEl.getAttribute('w:val');
                isBold = (bVal !== '0' && bVal !== 'false');
            }
            // Italic
            const iEl = rPr.getElementsByTagName('w:i')[0];
            if (iEl) {
                const iVal = iEl.getAttribute('w:val');
                isItalic = (iVal !== '0' && iVal !== 'false');
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
                const tCol = colEl.getAttribute('w:themeColor') || '';
                const resolved = this.resolveColor(cVal, tCol, themeColors);
                if (resolved) {
                    color = resolved;
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
                fontFamily = rfEl.getAttribute('w:ascii') || rfEl.getAttribute('w:hAnsi') || fontFamily;
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
        if (color && color !== '#000000' && color !== '#111827') {
            if (!DocxImporter.isNearWhiteColor(color) || highlight) {
                styles.push(`color: ${color};`);
            }
        }
        if (highlight) styles.push(`background-color: ${highlight};`);
        if (fontSize) styles.push(`font-size: ${fontSize};`);
        if (fontFamily) {
            styles.push(`font-family: ${DocxImporter.formatFontFamily(fontFamily)};`);
        }

        if (styles.length > 0) {
            content = `<span style="${styles.join(' ')}">${content}</span>`;
        }

        return { html: content, isPageBreak };
    }

    async parseTable(tbl, zip, rels, numberingMap, stylesMap, themeColors = {}) {
        const trNodes = Array.from(tbl.getElementsByTagName('w:tr'));
        if (trNodes.length === 0) return '';

        let tblAlign = '';
        const tblJc = tbl.getElementsByTagName('w:jc')[0];
        if (tblJc) {
            const alignVal = (tblJc.getAttribute('w:val') || '').toLowerCase();
            if (alignVal === 'center') tblAlign = ' mx-auto';
            else if (alignVal === 'right') tblAlign = ' ml-auto';
        }

        // Check if table borders are explicitly none or nil (borderless layout/signature table)
        let hasTableBorders = true;
        const tblBorders = tbl.getElementsByTagName('w:tblBorders')[0];
        if (tblBorders) {
            const topVal = (tblBorders.getElementsByTagName('w:top')[0]?.getAttribute('w:val') || '').toLowerCase();
            const bottomVal = (tblBorders.getElementsByTagName('w:bottom')[0]?.getAttribute('w:val') || '').toLowerCase();
            const insideHVal = (tblBorders.getElementsByTagName('w:insideH')[0]?.getAttribute('w:val') || '').toLowerCase();
            const insideVVal = (tblBorders.getElementsByTagName('w:insideV')[0]?.getAttribute('w:val') || '').toLowerCase();

            const isNoBorder = (v) => !v || v === 'none' || v === 'nil';
            if (isNoBorder(topVal) && isNoBorder(bottomVal) && isNoBorder(insideHVal) && isNoBorder(insideVVal)) {
                hasTableBorders = false;
            }
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
                const classes = hasTableBorders ? ['border', 'border-gray-300', 'dark:border-gray-700', 'p-2'] : ['p-2'];
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
                    const pRes = await this.parseParagraph(tcp, zip, rels, numberingMap, stylesMap, themeColors);
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

        const tableBorderClass = hasTableBorders ? 'border border-gray-300 dark:border-gray-700' : 'border-none';
        let tableHtml = `<table class="w-full border-collapse my-4 text-sm ${tableBorderClass}${tblAlign}">\n`;
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
