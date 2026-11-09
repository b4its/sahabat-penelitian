/**
 * VelScribe Document Engine
 * Comprehensive Word Processor Engine inspired by Microsoft Word & Google Documents.
 * Features:
 * - Intelligent local file ingestion (.docx, .md, .html, .txt, .json)
 * - Automatic document structure adaptation (Academic/Skripsi 4-4-3-3 margins, Times New Roman 12pt, justified text)
 * - True multi-page A4 physical sheet pagination with headers, footers (Halaman X dari Y), and page gaps
 * - Semantic chapter & heading promotion (BAB I-V, Preliminaries, 1.1 subheadings)
 * - Cover page centering and auto page-break
 * - Dynamic Left Outline synchronization
 * - Table of Contents generator (Daftar Isi Otomatis)
 * - Rich table and image editing tools
 * - Undo/Redo history stack, auto-save to localStorage
 * - Multi-format export (DOCX, PDF, MD, HTML, TXT, JSON)
 */

export class DocumentEngine {
    constructor(options = {}) {
        this.pagesWrapper = options.pagesWrapper || document.getElementById('document-pages-wrapper') || document.getElementById('document-canvas-container');
        this.editorEl = options.editorEl;
        this.canvasEl = options.canvasEl || document.getElementById('document-page-canvas');
        this.titleInput = options.titleInput;
        this.formatBadge = options.formatBadge;
        this.onStatsChange = options.onStatsChange || (() => {});
        this.onOutlineChange = options.onOutlineChange || (() => {});
        this.onSaveStateChange = options.onSaveStateChange || (() => {});
        this.onSelectionChange = options.onSelectionChange || (() => {});
        this.onAdaptationNotice = options.onAdaptationNotice || (() => {});

        this.currentFormat = 'docx';
        this.currentMarginPreset = 'normal';
        this.currentPaperSize = 'a4';
        this.currentOrientation = 'portrait';
        this.activePageIndex = 1;
        this.history = [];
        this.historyIndex = -1;
        this.maxHistory = 50;
        this.autoSaveTimer = null;
        this.isTyping = false;
        this.isPaginating = false;
        this.activeTable = null;
        this.activeImage = null;

        this.init();
    }

    init() {
        if (!this.pagesWrapper && !this.editorEl) return;

        // Check if initial content should be paginated into multiple sheets
        const initialHtml = this.getContent();
        if (initialHtml && (
            initialHtml.includes('heading-chapter') ||
            initialHtml.includes('page-break') ||
            initialHtml.includes('data-page-break') ||
            initialHtml.length > 1500
        )) {
            this.renderPaginatedDocument(initialHtml);
        } else {
            this.renumberPages();
        }

        // Record initial state
        this.recordHistory();

        // Event delegation on pages wrapper
        const targetContainer = this.pagesWrapper || this.editorEl;
        if (targetContainer) {
            targetContainer.addEventListener('input', (e) => {
                if (e.target.closest('.page-sheet-body') || e.target.id === 'document-editor') {
                    this.handleInput();
                }
            });

            targetContainer.addEventListener('click', (e) => {
                const table = e.target.closest('table');
                const img = e.target.closest('img');

                if (table) {
                    this.activeTable = table;
                    this.showTableToolbar(table, e);
                } else {
                    this.hideTableToolbar();
                }

                if (img) {
                    this.activeImage = img;
                    this.showImageToolbar(img, e);
                } else {
                    this.hideImageToolbar();
                }

                // Update active page index on click
                const clickedSheet = e.target.closest('.document-page-sheet');
                if (clickedSheet) {
                    const pageNum = parseInt(clickedSheet.getAttribute('data-page-index') || '1', 10);
                    if (pageNum !== this.activePageIndex) {
                        this.activePageIndex = pageNum;
                        this.updateStats();
                    }
                }
            });

            targetContainer.addEventListener('paste', (e) => {
                this.handlePaste(e);
            });

            targetContainer.addEventListener('keydown', (e) => {
                this.handleKeydown(e);
            });
        }

        // Selection listeners
        document.addEventListener('selectionchange', () => {
            if (this.isEditorFocused()) {
                const activeEditor = this.getActiveEditor();
                const sheet = activeEditor?.closest('.document-page-sheet');
                if (sheet) {
                    const pageNum = parseInt(sheet.getAttribute('data-page-index') || '1', 10);
                    if (pageNum !== this.activePageIndex) {
                        this.activePageIndex = pageNum;
                        this.updateStats();
                    }
                }
                this.onSelectionChange(this.getSelectionState());
            }
        });

        // Scroll listener on container for tracking active page
        const scrollContainer = document.getElementById('document-canvas-container');
        if (scrollContainer) {
            let scrollTimer = null;
            scrollContainer.addEventListener('scroll', () => {
                clearTimeout(scrollTimer);
                scrollTimer = setTimeout(() => {
                    this.updateActivePageOnScroll();
                }, 80);
            }, { passive: true });
        }

        // Initial stats and outline
        this.updateStats();
        this.updateOutline();
    }

    isEditorFocused() {
        const sel = window.getSelection();
        if (!sel || sel.rangeCount === 0) return false;
        const node = sel.anchorNode;
        if (!node) return false;
        if (this.pagesWrapper && this.pagesWrapper.contains(node)) return true;
        if (this.editorEl && this.editorEl.contains(node)) return true;
        return false;
    }

    getActiveEditor() {
        const sel = window.getSelection();
        if (sel && sel.rangeCount > 0) {
            const el = sel.anchorNode?.nodeType === Node.ELEMENT_NODE
                ? sel.anchorNode
                : sel.anchorNode?.parentElement;
            const body = el?.closest('.page-sheet-body');
            if (body) return body;
        }
        const bodies = this.getAllEditorBodies();
        return bodies[0] || this.editorEl;
    }

    getAllEditorBodies() {
        if (this.pagesWrapper) {
            const bodies = this.pagesWrapper.querySelectorAll('.page-sheet-body');
            if (bodies.length > 0) return Array.from(bodies);
        }
        return this.editorEl ? [this.editorEl] : [];
    }

    getAllPageSheets() {
        if (this.pagesWrapper) {
            const sheets = this.pagesWrapper.querySelectorAll('.document-page-sheet');
            if (sheets.length > 0) return Array.from(sheets);
        }
        const canvas = document.getElementById('document-page-canvas');
        return canvas ? [canvas] : [];
    }

    updateActivePageOnScroll() {
        const container = document.getElementById('document-canvas-container');
        if (!container) return;

        const containerRect = container.getBoundingClientRect();
        const midY = containerRect.top + containerRect.height / 3;

        const sheets = this.getAllPageSheets();
        let closestPage = this.activePageIndex;
        let minDistance = Infinity;

        sheets.forEach(sheet => {
            const rect = sheet.getBoundingClientRect();
            const sheetMidY = rect.top + rect.height / 2;
            const dist = Math.abs(sheetMidY - midY);
            if (dist < minDistance) {
                minDistance = dist;
                closestPage = parseInt(sheet.getAttribute('data-page-index') || '1', 10);
            }
        });

        if (closestPage !== this.activePageIndex) {
            this.activePageIndex = closestPage;
            this.updateStats();
        }
    }

    handleInput() {
        this.isTyping = true;
        this.onSaveStateChange('Menyimpan perubahan...');

        clearTimeout(this.autoSaveTimer);
        this.autoSaveTimer = setTimeout(() => {
            this.recordHistory();
            this.saveToLocalStorage();
            this.updateStats();
            this.updateOutline();
            this.isTyping = false;
            this.onSaveStateChange('Tersimpan di lokal');
        }, 600);
    }

    handleKeydown(e) {
        // Tab indentation
        if (e.key === 'Tab') {
            e.preventDefault();
            if (e.shiftKey) {
                document.execCommand('outdent', false, null);
            } else {
                document.execCommand('indent', false, null);
            }
            this.handleInput();
        }

        // Ctrl + Enter = Insert Page Break
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            e.preventDefault();
            this.insertPageBreak();
        }
    }

    handlePaste(e) {
        const items = (e.clipboardData || e.originalEvent?.clipboardData)?.items;
        if (!items) return;

        for (let item of items) {
            if (item.type.indexOf('image') === 0) {
                e.preventDefault();
                const blob = item.getAsFile();
                const reader = new FileReader();
                reader.onload = (event) => {
                    this.insertImage(event.target.result, 'Gambar ditempel');
                };
                reader.readAsDataURL(blob);
                return;
            }
        }
    }

    /* -------------------------------------------------------------
     * True Multi-Page A4 Pagination Engine
     * ------------------------------------------------------------- */

    /**
     * Intelligently partition HTML content into individual A4 page sheets.
     * Takes into account explicit page breaks, chapter headings, academic preliminaries,
     * and vertical content block height budgets.
     */
    paginateHtml(html) {
        if (!html || !html.trim()) {
            return [{
                pageNumber: 1,
                chapterName: '',
                contentHtml: '<p><br></p>',
            }];
        }

        const isTableOfContentsLine = (str) => {
            if (!str) return false;
            const clean = str.trim();
            if (!clean) return false;
            if (/(\.{2,}|…|·{2,}|\.\s*\.\s*\.)/.test(clean)) return true;
            if (/[\s.\t]{2,}(\d+|[ivxlcdm]+)$/i.test(clean)) return true;
            if (/\.\s*(\d+|[ivxlcdm]+)$/i.test(clean) && !/^\d+(\.\d+)+$/.test(clean)) return true;
            return false;
        };

        const parser = new DOMParser();
        const doc = parser.parseFromString(`<div>${html}</div>`, 'text/html');
        const container = doc.body.firstElementChild;

        const children = Array.from(container.children);
        if (children.length === 0 && container.textContent.trim()) {
            return [{
                pageNumber: 1,
                chapterName: '',
                contentHtml: `<p>${container.innerHTML}</p>`,
            }];
        }

        const pages = [];
        let currentBlocks = [];
        let currentWeight = 0;
        let currentChapter = '';
        const MAX_PAGE_WEIGHT = 780; // Printable A4 body height budget in px (approx 220mm at 96dpi)

        const finalizeCurrentPage = () => {
            if (currentBlocks.length === 0) return;
            pages.push({
                pageNumber: pages.length + 1,
                chapterName: currentChapter,
                contentHtml: currentBlocks.map(b => b.outerHTML || b).join('\n'),
            });
            currentBlocks = [];
            currentWeight = 0;
        };

        const estimateElementWeight = (el) => {
            const tag = el.tagName ? el.tagName.toUpperCase() : '';
            const text = (el.textContent || '').trim();
            const textLen = text.length;

            if (textLen === 0 && !el.querySelector('img')) return 0;
            if (tag === 'H1') return 80;
            if (tag === 'H2') return 55;
            if (tag === 'H3') return 45;
            if (tag === 'H4' || tag === 'H5' || tag === 'H6') return 35;
            if (tag === 'TABLE') {
                const rowCount = el.querySelectorAll('tr').length || 3;
                return Math.min(600, rowCount * 36 + 28);
            }
            if (tag === 'FIGURE' || tag === 'IMG' || el.querySelector('img')) return 220;
            if (tag === 'UL' || tag === 'OL') {
                const liCount = el.querySelectorAll('li').length || 3;
                return liCount * 28 + 20;
            }
            if (el.classList?.contains('toc-entry')) return 24;
            if (tag === 'P') {
                const lines = Math.max(1, Math.ceil(textLen / 75));
                return lines * 22 + 14;
            }
            if (tag === 'PRE' || tag === 'BLOCKQUOTE') {
                const lines = Math.max(2, (text.match(/\n/g) || []).length + 1);
                return lines * 20 + 30;
            }
            return Math.max(24, Math.ceil(textLen / 75) * 22 + 14);
        };

        const isChapterHeading = (el) => {
            const tag = (el.tagName || '').toUpperCase();
            const text = (el.textContent || '').trim();
            if (isTableOfContentsLine(text)) return false;
            const isHeadingTag = /^H[1-6]$/.test(tag) || el.classList?.contains('heading-chapter');
            const isShortTitle = text.length <= 80 && !/[.!?]$/.test(text);
            if (!isHeadingTag && !isShortTitle) return false;

            return /^(BAB\s+[IVXLCDM]+|\bBAB\s+\d+)/i.test(text);
        };

        const isPreliminaryHeading = (el) => {
            const tag = (el.tagName || '').toUpperCase();
            const text = (el.textContent || '').trim();
            if (isTableOfContentsLine(text)) return false;
            const isHeadingTag = /^H[1-6]$/.test(tag) || el.classList?.contains('heading-preliminary');
            const isShortTitle = text.length <= 60 && !/[.!?]$/.test(text);
            if (!isHeadingTag && !isShortTitle) return false;

            return /^(ABSTRAK|ABSTRACT|KATA\s+PENGANTAR|PRAKATA|LEMBAR\s+PENGESAHAN|HALAMAN\s+PENGESAHAN|LEMBAR\s+PERSETUJUAN|HALAMAN\s+PERSETUJUAN|PERNYATAAN\s+KEASLIAN|PERNYATAAN\s+ORISINALITAS|DAFTAR\s+ISI|DAFTAR\s+TABEL|DAFTAR\s+GAMBAR|DAFTAR\s+LAMPIRAN|DAFTAR\s+PUSTAKA|RIWAYAT\s+HIDUP|BIODATA\s+PENULIS|LAMPIRAN)(\s+.*)?$/i.test(text);
        };

        const isExplicitPageBreak = (el) => {
            if (!el.tagName) return false;
            return el.classList?.contains('page-break') ||
                   el.hasAttribute('data-page-break') ||
                   el.getAttribute('style')?.includes('page-break-after: always') ||
                   el.getAttribute('style')?.includes('break-after: page');
        };

        for (let i = 0; i < children.length; i++) {
            const el = children[i];

            // 1. Explicit Page Break
            if (isExplicitPageBreak(el)) {
                if (currentBlocks.length > 0) {
                    finalizeCurrentPage();
                }
                continue;
            }

            // 2. Chapter Heading
            if (isChapterHeading(el)) {
                if (currentBlocks.length > 0) {
                    finalizeCurrentPage();
                }
                currentChapter = el.textContent.trim().toUpperCase();
                currentBlocks.push(el);
                currentWeight += estimateElementWeight(el);
                continue;
            }

            // 3. Academic Preliminary Heading (ABSTRAK, LEMBAR PENGESAHAN, DAFTAR ISI, etc.)
            if (isPreliminaryHeading(el)) {
                if (currentBlocks.length > 0) {
                    finalizeCurrentPage();
                }
                currentChapter = el.textContent.trim().toUpperCase();
                currentBlocks.push(el);
                currentWeight += estimateElementWeight(el);
                continue;
            }

            // 4. Regular Element (p, table, image, list, etc.)
            const weight = estimateElementWeight(el);

            // Check if adding this element overflows the page budget
            if (currentWeight + weight > MAX_PAGE_WEIGHT && currentBlocks.length > 0) {
                // If element is a massive paragraph (>700 chars), split gracefully at sentence boundary
                if (el.tagName === 'P' && el.textContent.length > 700) {
                    const text = el.textContent;
                    const sentences = text.match(/[^.!?]+[.!?]+(\s+|$)/g) || [text];
                    if (sentences.length >= 2) {
                        let halfway = Math.floor(sentences.length / 2);
                        const part1 = sentences.slice(0, halfway).join('').trim();
                        const part2 = sentences.slice(halfway).join('').trim();

                        const p1 = el.cloneNode(true);
                        p1.innerHTML = part1;
                        currentBlocks.push(p1);
                        finalizeCurrentPage();

                        const p2 = el.cloneNode(true);
                        p2.innerHTML = part2;
                        currentBlocks.push(p2);
                        currentWeight += estimateElementWeight(p2);
                        continue;
                    }
                }

                // Normal element overflow: finish page and put on next page
                finalizeCurrentPage();
            }

            currentBlocks.push(el);
            currentWeight += weight;
        }

        finalizeCurrentPage();

        if (pages.length === 0) {
            pages.push({
                pageNumber: 1,
                chapterName: '',
                contentHtml: '<p><br></p>',
            });
        }

        return pages;
    }

    /**
     * Render paginated pages into this.pagesWrapper.
     * Each page is a physical A4 sheet with header, editable body, footer, and page gaps.
     */
    renderPaginatedDocument(html) {
        if (!this.pagesWrapper) return;
        this.isPaginating = true;

        const pages = this.paginateHtml(html);
        const totalPages = pages.length;

        // Clear existing sheets and gaps
        this.pagesWrapper.innerHTML = '';

        const docTitle = this.getTitle() || 'Dokumen VelScribe Wordcraft';

        pages.forEach((p, idx) => {
            const pageNum = idx + 1;
            const sheet = document.createElement('article');
            sheet.className = 'document-page-sheet page-a4 relative flex flex-col justify-between shadow-2xl';
            sheet.setAttribute('data-page-index', pageNum);
            sheet.setAttribute('id', pageNum === 1 ? 'document-page-canvas' : `document-page-${pageNum}`);

            // Apply margin preset
            this.applyMarginsToSheet(sheet, this.currentMarginPreset);

            // Apply dark mode sheet if active
            const isDark = document.getElementById('btn-toggle-dark-sheet')?.classList.contains('active-dark') || false;
            if (isDark) {
                sheet.classList.add('canvas-dark-sheet');
            }

            // Header (Cover page has no running header, just like Word's "Different First Page")
            const isCoverPage = pageNum === 1;
            const header = document.createElement('header');
            header.className = `page-sheet-header flex items-center justify-between text-[11px] text-gray-400 pb-2 border-b border-gray-100 dark:border-gray-800 select-none no-print ${isCoverPage ? 'invisible' : ''}`;
            header.innerHTML = `
                <span class="header-doc-title truncate max-w-[60%] font-medium">${this.escapeHtml(docTitle)}</span>
                <span class="header-chapter-name text-gray-400 font-serif italic text-[11px]">${this.escapeHtml(p.chapterName || '')}</span>
            `;
            sheet.appendChild(header);

            // Body
            const body = document.createElement('div');
            body.className = 'page-sheet-body flex-1 outline-none';
            body.setAttribute('contenteditable', 'true');
            body.setAttribute('spellcheck', 'true');
            body.setAttribute('role', 'textbox');
            body.setAttribute('aria-multiline', 'true');
            body.setAttribute('data-page-number', pageNum);
            if (pageNum === 1) {
                body.setAttribute('id', 'document-editor');
                this.editorEl = body;
                this.canvasEl = sheet;
            }
            body.innerHTML = p.contentHtml;
            sheet.appendChild(body);

            // Footer (Cover page has no page number footer)
            const footer = document.createElement('footer');
            footer.className = `page-sheet-footer flex items-center justify-between text-xs text-gray-400 pt-2 border-t border-gray-100 dark:border-gray-800 select-none no-print ${isCoverPage ? 'invisible' : ''}`;
            footer.innerHTML = `
                <span class="text-[11px] text-gray-400 font-mono">Dokumen VelScribe Wordcraft</span>
                <span class="footer-page-num text-xs font-semibold text-gray-500 font-mono">Halaman ${pageNum} dari ${totalPages}</span>
            `;
            sheet.appendChild(footer);

            this.pagesWrapper.appendChild(sheet);

            // Page gap between sheets
            if (pageNum < totalPages) {
                const gap = document.createElement('div');
                gap.className = 'page-sheet-gap select-none no-print';
                gap.innerHTML = `<span class="page-gap-indicator">Pemisah Lembar A4 (Halaman ${pageNum + 1})</span>`;
                this.pagesWrapper.appendChild(gap);
            }
        });

        // Ensure ruler markers match preset
        this.updateRulerMarkersForPreset(this.currentMarginPreset);

        this.isPaginating = false;

        // Update stats and outline
        this.updateStats();
        this.updateOutline();
    }

    renumberPages() {
        const sheets = this.getAllPageSheets();
        const total = sheets.length;
        const docTitle = this.getTitle() || 'Dokumen VelScribe Wordcraft';

        sheets.forEach((sheet, idx) => {
            const pageNum = idx + 1;
            sheet.setAttribute('data-page-index', pageNum);
            if (pageNum === 1) {
                sheet.setAttribute('id', 'document-page-canvas');
            } else {
                sheet.setAttribute('id', `document-page-${pageNum}`);
            }

            const headerTitle = sheet.querySelector('.header-doc-title');
            if (headerTitle) {
                headerTitle.textContent = docTitle;
            }

            const footerNum = sheet.querySelector('.footer-page-num');
            if (footerNum) {
                footerNum.textContent = `Halaman ${pageNum} dari ${total}`;
            }

            const body = sheet.querySelector('.page-sheet-body');
            if (body) {
                body.setAttribute('data-page-number', pageNum);
            }
        });

        if (this.pagesWrapper) {
            const gaps = this.pagesWrapper.querySelectorAll('.page-sheet-gap');
            gaps.forEach((gap, idx) => {
                const indicator = gap.querySelector('.page-gap-indicator');
                if (indicator) {
                    indicator.textContent = `Pemisah Lembar A4 (Halaman ${idx + 2})`;
                }
            });
        }
    }

    applyMarginsToSheet(sheet, preset) {
        if (!sheet) return;
        if (preset === 'skripsi') {
            // Skripsi standard: Top 4cm, Left 4cm, Right 3cm, Bottom 3cm
            sheet.style.paddingTop = '40mm';
            sheet.style.paddingLeft = '40mm';
            sheet.style.paddingRight = '30mm';
            sheet.style.paddingBottom = '30mm';
        } else if (preset === 'narrow') {
            sheet.style.padding = '12.7mm';
        } else if (preset === 'wide') {
            sheet.style.paddingTop = '25.4mm';
            sheet.style.paddingBottom = '25.4mm';
            sheet.style.paddingLeft = '38.1mm';
            sheet.style.paddingRight = '38.1mm';
        } else {
            sheet.style.padding = '25.4mm';
        }
    }

    /* -------------------------------------------------------------
     * Local File Ingestion & Intelligent Auto-Adaptation
     * ------------------------------------------------------------- */

    async loadLocalFile(file) {
        const filename = file.name;
        const extension = filename.split('.').pop().toLowerCase();
        let contentHtml = '';
        let detectedFormat = extension;

        try {
            if (['docx', 'doc'].includes(extension)) {
                detectedFormat = 'docx';
                const arrayBuffer = await file.arrayBuffer();

                if (window.mammoth) {
                    const styleMap = [
                        "p[style-name='Title'] => h1.doc-title.text-center:fresh",
                        "p[style-name='Subtitle'] => p.doc-subtitle.text-center:fresh",
                        "p[style-name='Heading 1'] => h1.heading-chapter:fresh",
                        "p[style-name='Heading 2'] => h2.heading-section:fresh",
                        "p[style-name='Heading 3'] => h3.heading-subsection:fresh",
                        "p[style-name='Heading 4'] => h4:fresh",
                        "p[style-name='Heading 5'] => h5:fresh",
                        "p[style-name='Heading 6'] => h6:fresh",
                        "r[style-name='Strong'] => strong",
                        "p[style-name^='toc'] => p.toc-entry:fresh",
                        "p[style-name^='TOC'] => p.toc-entry:fresh",
                        "p[style-name='Table of Figures'] => p.toc-entry:fresh",
                        "p[style-name='Daftar Isi'] => p.toc-entry:fresh",
                        "p:has(w:jc[w:val='center']) => p.text-center:fresh",
                        "p:has(w:jc[w:val='right']) => p.text-right:fresh",
                        "p:has(w:jc[w:val='both']) => p.text-justify:fresh",
                    ];

                    const result = await window.mammoth.convertToHtml({
                        arrayBuffer,
                        styleMap,
                    });
                    contentHtml = result.value;
                } else {
                    const backendResult = await this.uploadToBackend(file);
                    contentHtml = backendResult.content_html;
                }
            } else if (['md', 'markdown'].includes(extension)) {
                detectedFormat = 'markdown';
                const text = await file.text();
                if (window.marked) {
                    contentHtml = window.marked.parse(text);
                } else {
                    contentHtml = text.replace(/\n/g, '<br>');
                }
            } else if (['html', 'htm'].includes(extension)) {
                detectedFormat = 'html';
                contentHtml = await file.text();
            } else if (extension === 'json') {
                detectedFormat = 'json';
                const text = await file.text();
                const data = JSON.parse(text);
                contentHtml = data.content || data.html || text;
                if (data.title) {
                    this.setTitle(data.title);
                }
            } else {
                detectedFormat = 'txt';
                const text = await file.text();
                const paragraphs = text.split(/\n\s*\n/);
                contentHtml = paragraphs
                    .map(p => `<p class="text-justify my-3">${p.trim().replace(/\n/g, '<br>')}</p>`)
                    .join('');
            }

            // Set document title
            if (extension !== 'json' || !this.titleInput?.value) {
                const baseTitle = filename.replace(/\.[^/.]+$/, '').replace(/[-_]/g, ' ');
                this.setTitle(this.capitalizeWords(baseTitle));
            }

            // Run semantic document structure & formatting adapter
            const adapted = this.adaptDocumentStructure(contentHtml, filename);

            this.setContent(adapted.html);
            this.setFormatBadge(detectedFormat);

            // Apply adapted typography and margins
            if (adapted.isAcademic) {
                this.setMarginPreset('skripsi');
                this.setFontFamily("'Times New Roman', serif");
                this.setFontSize('12pt');
                this.setLineSpacing('1.5');
                this.onAdaptationNotice({
                    type: 'academic',
                    message: `Format Skripsi Terdeteksi: Margin 4-4-3-3 cm, Font Times New Roman 12pt, Spasi 1.5, dan ${adapted.headingCount} Bab/Subbab berhasil dipecah menjadi lembar halaman A4.`,
                });
            } else {
                this.setMarginPreset('normal');
                this.onAdaptationNotice({
                    type: 'normal',
                    message: `Format ${detectedFormat.toUpperCase()} berhasil dimuat dan diselaraskan ke dalam lembar halaman A4.`,
                });
            }

            return {
                success: true,
                filename,
                format: detectedFormat,
                isAcademic: adapted.isAcademic,
                message: `Format ${detectedFormat.toUpperCase()} otomatis disesuaikan.`,
            };
        } catch (err) {
            console.error('Error loading local file:', err);
            // Fallback to backend parser
            try {
                const backendResult = await this.uploadToBackend(file);
                this.setContent(backendResult.content_html);
                this.setTitle(backendResult.title);
                this.setFormatBadge(backendResult.format);
                return {
                    success: true,
                    filename,
                    format: backendResult.format,
                    message: backendResult.message,
                };
            } catch (bErr) {
                throw new Error('Gagal memproses berkas lokal: ' + (err.message || bErr.message));
            }
        }
    }

    adaptDocumentStructure(html, filename = '') {
        const parser = new DOMParser();
        const doc = parser.parseFromString(`<div>${html}</div>`, 'text/html');
        const container = doc.body.firstElementChild;

        const isTableOfContentsLine = (str) => {
            if (!str) return false;
            const clean = str.trim();
            if (!clean) return false;
            if (/(\.{2,}|…|·{2,}|\.\s*\.\s*\.)/.test(clean)) return true;
            if (/[\s.\t]{2,}(\d+|[ivxlcdm]+)$/i.test(clean)) return true;
            if (/\.\s*(\d+|[ivxlcdm]+)$/i.test(clean) && !/^\d+(\.\d+)+$/.test(clean)) return true;
            return false;
        };

        const allText = container.textContent.toLowerCase();
        const isAcademic =
            allText.includes('skripsi') ||
            allText.includes('tesis') ||
            allText.includes('thesis') ||
            allText.includes('disertasi') ||
            allText.includes('politeknik') ||
            allText.includes('universitas') ||
            allText.includes('nim.') ||
            allText.includes('bab i') ||
            filename.toLowerCase().includes('skripsi');

        let inCover = isAcademic;
        let coverCount = 0;
        let headingCount = 0;
        let consecutiveEmpty = 0;

        const children = Array.from(container.children);
        for (let el of children) {
            const text = el.textContent.trim();

            // Collapse excessive empty paragraphs
            if (text === '' && !el.querySelector('img')) {
                consecutiveEmpty++;
                if (consecutiveEmpty > 1) {
                    el.remove();
                    continue;
                }
            } else {
                consecutiveEmpty = 0;
            }

            // Table of Contents entries: format as clean dot-leader rows (never headings or page breaks)
            if (isTableOfContentsLine(text)) {
                const match = text.match(/^(.*?)(?:[\s.·…]{2,})([ivxlcdm\d]+)$/i);
                if (match) {
                    const itemTitle = match[1].trim();
                    const itemPage = match[2].trim();
                    const tocDiv = doc.createElement('div');
                    tocDiv.className = 'toc-entry flex items-baseline justify-between py-0.5 text-sm leading-snug font-serif text-gray-800 dark:text-gray-200';
                    tocDiv.innerHTML = `<span class="toc-title truncate max-w-[80%]">${this.escapeHtml(itemTitle)}</span><span class="flex-1 mx-2 border-b border-dotted border-gray-400 dark:border-gray-600"></span><span class="toc-page font-mono text-xs text-gray-600 dark:text-gray-400">${this.escapeHtml(itemPage)}</span>`;
                    el.replaceWith(tocDiv);
                    continue;
                } else {
                    const cleanTitle = text.replace(/[\s.·…]+$/, '').trim();
                    const tocDiv = doc.createElement('div');
                    tocDiv.className = 'toc-entry flex items-baseline justify-between py-0.5 text-sm leading-snug font-serif text-gray-800 dark:text-gray-200';
                    tocDiv.innerHTML = `<span class="toc-title truncate max-w-[80%]">${this.escapeHtml(cleanTitle)}</span><span class="flex-1 mx-2 border-b border-dotted border-gray-400 dark:border-gray-600"></span>`;
                    el.replaceWith(tocDiv);
                    continue;
                }
            }

            // End cover page when next section begins
            if (inCover && (
                /^(BAB\s+[IVXLCDM]+|\bBAB\s+\d+)/i.test(text) ||
                /^(ABSTRAK|ABSTRACT|KATA\s+PENGANTAR|PRAKATA|LEMBAR\s+PENGESAHAN|HALAMAN\s+PENGESAHAN|LEMBAR\s+PERSETUJUAN|HALAMAN\s+PERSETUJUAN|PERNYATAAN\s+KEASLIAN|PERNYATAAN\s+ORISINALITAS|DAFTAR\s+ISI|DAFTAR\s+TABEL|DAFTAR\s+GAMBAR|DAFTAR\s+PUSTAKA)(\s+.*)?$/i.test(text)
            )) {
                inCover = false;
                if (coverCount > 0) {
                    const pb = doc.createElement('div');
                    pb.className = 'page-break my-8 border-b-2 border-dashed border-gray-400 dark:border-gray-600 text-center py-2 select-none';
                    pb.setAttribute('data-page-break', 'true');
                    pb.innerHTML = '<span class="bg-gray-100 dark:bg-gray-800 px-3 py-1 rounded-full text-xs text-gray-500 font-mono">Pemisah Halaman (Akhir Sampul)</span>';
                    el.before(pb);
                }
            }

            // Cover Page centering
            if (inCover) {
                coverCount++;
                const img = el.querySelector('img') || (el.tagName === 'IMG' ? el : null);
                if (img) {
                    img.className = 'cover-logo mx-auto block my-6 max-h-40 object-contain';
                    el.className = 'text-center my-4';
                } else if (text) {
                    if (/^(OLEH|DISUSUN\s+OLEH|BY)\s*:/i.test(text)) {
                        el.className = 'cover-author-label text-center text-sm font-medium text-gray-600 dark:text-gray-400 my-2';
                    } else if (/^NIM\b/i.test(text)) {
                        el.className = 'cover-author-nim text-center font-bold text-base text-gray-900 dark:text-gray-100 my-1';
                    } else if (/^(PROGRAM\s+STUDI|JURUSAN|FAKULTAS|POLITEKNIK|UNIVERSITAS|DEPARTMENT|STUDY\s+PROGRAM)/i.test(text)) {
                        el.className = 'cover-institution text-center font-bold text-sm uppercase text-gray-900 dark:text-gray-100 my-1 leading-snug';
                    } else {
                        el.className = 'cover-title text-center font-bold text-gray-900 dark:text-gray-100 my-2 leading-snug';
                    }
                }

                if (/\b(19|20)\d{2}\b/.test(text) && coverCount >= 4) {
                    inCover = false;
                    const pb = doc.createElement('div');
                    pb.className = 'page-break my-8 border-b-2 border-dashed border-gray-400 dark:border-gray-600 text-center py-2 select-none';
                    pb.setAttribute('data-page-break', 'true');
                    pb.innerHTML = '<span class="bg-gray-100 dark:bg-gray-800 px-3 py-1 rounded-full text-xs text-gray-500 font-mono">Pemisah Halaman (Akhir Sampul)</span>';
                    el.after(pb);
                }
                continue;
            }

            // Chapter Headings: BAB I, BAB II, etc.
            const babMatch = text.match(/^(BAB\s+[IVXLCDM]+|\bBAB\s+\d+)(.*)$/i);
            if (!isTableOfContentsLine(text) && babMatch) {
                headingCount++;
                let titleText = text;
                const nextEl = el.nextElementSibling;
                if ((!babMatch[2] || babMatch[2].trim() === '') && nextEl) {
                    const nextText = nextEl.textContent.trim();
                    if (nextText && nextText.length < 100 && !/^\d+\.\d+/.test(nextText)) {
                        titleText = `${babMatch[1]} ${nextText}`;
                        nextEl.remove();
                    }
                }

                const h1 = doc.createElement('h1');
                h1.className = 'heading-chapter text-center font-bold text-xl my-6 text-gray-900 dark:text-gray-100 uppercase';
                h1.textContent = titleText;

                const pb = doc.createElement('div');
                pb.className = 'page-break my-6 border-b border-dashed border-gray-300 dark:border-gray-700 text-center py-1 select-none';
                pb.setAttribute('data-page-break', 'true');
                pb.innerHTML = '<span class="text-[10px] text-gray-400 font-mono">Awal Bab Baru</span>';

                el.before(pb);
                el.replaceWith(h1);
                continue;
            }

            // Academic Preliminaries: ABSTRAK, DAFTAR ISI, LEMBAR PENGESAHAN, etc.
            if (
                !isTableOfContentsLine(text) &&
                /^(ABSTRAK|ABSTRACT|KATA\s+PENGANTAR|PRAKATA|LEMBAR\s+PENGESAHAN|HALAMAN\s+PENGESAHAN|LEMBAR\s+PERSETUJUAN|HALAMAN\s+PERSETUJUAN|PERNYATAAN\s+KEASLIAN|PERNYATAAN\s+ORISINALITAS|DAFTAR\s+ISI|DAFTAR\s+TABEL|DAFTAR\s+GAMBAR|DAFTAR\s+LAMPIRAN|DAFTAR\s+PUSTAKA|RIWAYAT\s+HIDUP|BIODATA\s+PENULIS|LAMPIRAN)(\s+.*)?$/i.test(text) &&
                text.length <= 60 &&
                !/[.!?]$/.test(text)
            ) {
                headingCount++;
                const h1 = doc.createElement('h1');
                h1.className = 'heading-preliminary text-center font-bold text-xl my-6 text-gray-900 dark:text-gray-100 uppercase';
                h1.textContent = text.toUpperCase();

                const pb = doc.createElement('div');
                pb.className = 'page-break my-6 border-b border-dashed border-gray-300 dark:border-gray-700 text-center py-1 select-none';
                pb.setAttribute('data-page-break', 'true');
                pb.innerHTML = '<span class="text-[10px] text-gray-400 font-mono">Halaman Baru</span>';

                el.before(pb);
                el.replaceWith(h1);
                continue;
            }

            // Subsections: 1.1 Latar Belakang, 2.1 Teori
            if (!isTableOfContentsLine(text) && /^(\d+\.\d+)\s+(.+)$/.test(text)) {
                headingCount++;
                const h2 = doc.createElement('h2');
                h2.className = 'heading-section font-bold text-lg my-4 text-gray-900 dark:text-gray-100';
                h2.innerHTML = el.innerHTML;
                el.replaceWith(h2);
                continue;
            }

            // Sub-subsections: 1.1.1, 2.3.1
            if (!isTableOfContentsLine(text) && /^(\d+\.\d+\.\d+)\s+(.+)$/.test(text)) {
                headingCount++;
                const h3 = doc.createElement('h3');
                h3.className = 'heading-subsection font-semibold text-base my-3 text-gray-800 dark:text-gray-200';
                h3.innerHTML = el.innerHTML;
                el.replaceWith(h3);
                continue;
            }

            // Standalone short uppercase lines
            if (
                !inCover &&
                !isTableOfContentsLine(text) &&
                text.length > 5 &&
                text.length < 60 &&
                text === text.toUpperCase() &&
                !/[.,;!?]$/.test(text) &&
                !/^(OLEH|DISUSUN|BY|NIM|NPM|NIS|NIK|PROGRAM\s+STUDI|JURUSAN|FAKULTAS|POLITEKNIK|UNIVERSITAS|DEPARTMENT|STUDY\s+PROGRAM|SEKOLAH\s+TINGGI|INSTITUT|SEBAGAI\s+SALAH\s+SATU\s+SYARAT|SUBMITTED\s+AS|KEMENTERIAN|\d{4})\b/i.test(text) &&
                !el.querySelector('img')
            ) {
                headingCount++;
                const h2 = doc.createElement('h2');
                h2.className = 'heading-section font-bold text-lg my-4 text-center text-gray-900 dark:text-gray-100 uppercase';
                h2.innerHTML = text;
                el.replaceWith(h2);
                continue;
            }

            // Table styling
            if (el.tagName === 'TABLE') {
                el.className = 'w-full border-collapse my-4 text-sm border border-gray-300 dark:border-gray-700';
                el.querySelectorAll('th, td').forEach(cell => {
                    cell.className = 'border border-gray-300 dark:border-gray-700 p-2 text-left';
                });
                el.querySelectorAll('th').forEach(th => {
                    th.className = 'border border-gray-300 dark:border-gray-700 p-2 text-left bg-gray-100 dark:bg-gray-800 font-semibold';
                });
                continue;
            }

            // Normal Body Paragraphs
            if (el.tagName === 'P') {
                if (isAcademic) {
                    el.className = 'text-justify my-3 leading-relaxed text-gray-800 dark:text-gray-200 indent-8';
                } else {
                    el.className = 'my-2 leading-relaxed text-gray-800 dark:text-gray-200';
                }
            }
        }

        return {
            html: container.innerHTML,
            isAcademic,
            headingCount,
        };
    }

    async uploadToBackend(file) {
        const formData = new FormData();
        formData.append('file', file);

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const res = await fetch('/api/documents/import', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
            },
        });

        if (!res.ok) {
            const error = await res.json();
            throw new Error(error.message || 'Gagal mengunggah berkas');
        }

        return await res.json();
    }

    /* -------------------------------------------------------------
     * Margins, Layout Presets & Formatting
     * ------------------------------------------------------------- */

    setMarginPreset(preset) {
        this.currentMarginPreset = preset;
        const sheets = this.getAllPageSheets();
        sheets.forEach(sheet => this.applyMarginsToSheet(sheet, preset));
        this.updateRulerMarkersForPreset(preset);

        const marginSelect = document.getElementById('layout-margins');
        if (marginSelect) {
            marginSelect.value = preset;
        }
    }

    updateRulerMarkersForPreset(preset) {
        if (preset === 'skripsi') {
            this.updateRulerMarkers(4, 18);
        } else if (preset === 'narrow') {
            this.updateRulerMarkers(1.3, 19.7);
        } else if (preset === 'wide') {
            this.updateRulerMarkers(3.8, 17.2);
        } else {
            this.updateRulerMarkers(2.5, 18.5);
        }
    }

    updateRulerMarkers(leftCm, rightCm) {
        const leftMarker = document.getElementById('ruler-left-margin');
        const rightMarker = document.getElementById('ruler-right-margin');

        if (leftMarker) {
            leftMarker.style.left = `${(leftCm / 21) * 100}%`;
        }
        if (rightMarker) {
            rightMarker.style.left = `${(rightCm / 21) * 100}%`;
        }
    }

    setFontFamily(fontName) {
        this.getAllEditorBodies().forEach(b => {
            b.style.fontFamily = fontName;
        });

        const active = this.getActiveEditor();
        if (active) active.focus();
        document.execCommand('fontName', false, fontName);

        const fontSelect = document.getElementById('font-family-select');
        if (fontSelect) {
            for (let i = 0; i < fontSelect.options.length; i++) {
                if (fontSelect.options[i].value.includes(fontName) || fontName.includes(fontSelect.options[i].text) || (fontName.includes('Times') && fontSelect.options[i].value.includes('Times'))) {
                    fontSelect.selectedIndex = i;
                    break;
                }
            }
        }
        this.handleInput();
    }

    setFontSize(ptSize) {
        const active = this.getActiveEditor();
        if (active) active.focus();
        const sel = window.getSelection();
        if (sel && sel.rangeCount > 0 && !sel.isCollapsed) {
            const span = document.createElement('span');
            span.style.fontSize = ptSize;
            span.appendChild(sel.getRangeAt(0).extractContents());
            sel.getRangeAt(0).insertNode(span);
            this.handleInput();
        } else {
            this.getAllEditorBodies().forEach(b => {
                b.style.fontSize = ptSize;
            });
        }

        const sizeSelect = document.getElementById('font-size-select');
        if (sizeSelect) {
            sizeSelect.value = ptSize;
        }
    }

    setLineSpacing(val) {
        this.getAllEditorBodies().forEach(b => {
            b.style.lineHeight = val;
            b.querySelectorAll('p').forEach(p => {
                p.style.lineHeight = val;
            });
        });

        const lineSelect = document.getElementById('line-spacing-select');
        if (lineSelect) {
            lineSelect.value = val;
        }
        this.handleInput();
    }

    setTextAlignment(align) {
        const active = this.getActiveEditor();
        if (active) active.focus();
        if (align === 'justify') {
            document.execCommand('justifyFull');
        } else if (align === 'center') {
            document.execCommand('justifyCenter');
        } else if (align === 'right') {
            document.execCommand('justifyRight');
        } else {
            document.execCommand('justifyLeft');
        }
        this.handleInput();
    }

    /* -------------------------------------------------------------
     * Table of Contents Generator (Daftar Isi Otomatis)
     * ------------------------------------------------------------- */

    generateTableOfContents() {
        const headings = [];
        const bodies = this.getAllEditorBodies();
        let pageCounter = 1;

        bodies.forEach((body, bodyIdx) => {
            const hElements = body.querySelectorAll('h1, h2, h3');
            hElements.forEach(h => {
                headings.push({
                    el: h,
                    page: bodyIdx + 1,
                    text: h.textContent.trim(),
                    level: h.tagName.toLowerCase(),
                });
            });
        });

        if (headings.length === 0) {
            return false;
        }

        const tocEntries = [];
        headings.forEach((item, index) => {
            const id = item.el.id || `heading-${index}`;
            item.el.id = id;

            const indentClass = item.level === 'h1' ? 'font-bold mt-2' :
                                item.level === 'h2' ? 'pl-4 text-sm' :
                                'pl-8 text-xs text-gray-600 dark:text-gray-400';

            tocEntries.push(`
                <div class="flex items-baseline justify-between py-1 ${indentClass}">
                    <a href="#${id}" class="hover:text-indigo-600 hover:underline truncate max-w-[80%]">${item.text}</a>
                    <span class="flex-1 mx-2 border-b border-dotted border-gray-400 dark:border-gray-600"></span>
                    <span class="font-mono text-xs">${item.page}</span>
                </div>
            `);
        });

        const tocHtml = `
            <div class="table-of-contents my-8 p-6 border border-gray-300 dark:border-gray-700 bg-gray-50/60 dark:bg-gray-800/40 rounded-xl" contenteditable="false">
                <h2 class="text-center font-bold text-lg mb-4 uppercase tracking-wider text-gray-900 dark:text-gray-100">DAFTAR ISI</h2>
                <div class="space-y-1 font-serif">
                    ${tocEntries.join('')}
                </div>
            </div>
        `;

        this.insertAtCursor(tocHtml);
        return true;
    }

    /* -------------------------------------------------------------
     * Interactive Table Tools
     * ------------------------------------------------------------- */

    showTableToolbar(table, event) {
        let toolbar = document.getElementById('floating-table-toolbar');
        if (!toolbar) return;

        const rect = table.getBoundingClientRect();
        toolbar.style.top = `${Math.max(10, rect.top - 45)}px`;
        toolbar.style.left = `${Math.max(10, rect.left)}px`;
        toolbar.classList.remove('hidden');
        toolbar.classList.add('flex');
    }

    hideTableToolbar() {
        const toolbar = document.getElementById('floating-table-toolbar');
        if (toolbar) {
            toolbar.classList.add('hidden');
            toolbar.classList.remove('flex');
        }
    }

    addRowAbove() {
        if (!this.activeTable) return;
        const row = this.activeTable.querySelector('tr');
        if (!row) return;
        const cols = row.children.length;
        const newRow = document.createElement('tr');
        for (let i = 0; i < cols; i++) {
            const td = document.createElement('td');
            td.className = 'border border-gray-300 dark:border-gray-700 p-2 text-left';
            td.innerHTML = '&nbsp;';
            newRow.appendChild(td);
        }
        row.before(newRow);
        this.handleInput();
    }

    addRowBelow() {
        if (!this.activeTable) return;
        const row = this.activeTable.querySelector('tr:last-child');
        if (!row) return;
        const cols = row.children.length;
        const newRow = document.createElement('tr');
        for (let i = 0; i < cols; i++) {
            const td = document.createElement('td');
            td.className = 'border border-gray-300 dark:border-gray-700 p-2 text-left';
            td.innerHTML = '&nbsp;';
            newRow.appendChild(td);
        }
        row.after(newRow);
        this.handleInput();
    }

    deleteRow() {
        if (!this.activeTable) return;
        const sel = window.getSelection();
        const tr = sel?.anchorNode?.parentElement?.closest('tr') || this.activeTable.querySelector('tr:last-child');
        if (tr && this.activeTable.querySelectorAll('tr').length > 1) {
            tr.remove();
            this.handleInput();
        }
    }

    addColumn() {
        if (!this.activeTable) return;
        this.activeTable.querySelectorAll('tr').forEach((tr, idx) => {
            const cell = idx === 0 && tr.querySelector('th') ? document.createElement('th') : document.createElement('td');
            cell.className = 'border border-gray-300 dark:border-gray-700 p-2 text-left';
            cell.innerHTML = idx === 0 ? 'Kolom Baru' : '&nbsp;';
            tr.appendChild(cell);
        });
        this.handleInput();
    }

    deleteColumn() {
        if (!this.activeTable) return;
        this.activeTable.querySelectorAll('tr').forEach(tr => {
            if (tr.children.length > 1) {
                tr.lastElementChild.remove();
            }
        });
        this.handleInput();
    }

    deleteTable() {
        if (!this.activeTable) return;
        this.activeTable.remove();
        this.activeTable = null;
        this.hideTableToolbar();
        this.handleInput();
    }

    /* -------------------------------------------------------------
     * Interactive Image Tools
     * ------------------------------------------------------------- */

    showImageToolbar(img, event) {
        let toolbar = document.getElementById('floating-image-toolbar');
        if (!toolbar) return;

        const rect = img.getBoundingClientRect();
        toolbar.style.top = `${Math.max(10, rect.top - 45)}px`;
        toolbar.style.left = `${Math.max(10, rect.left)}px`;
        toolbar.classList.remove('hidden');
        toolbar.classList.add('flex');
    }

    hideImageToolbar() {
        const toolbar = document.getElementById('floating-image-toolbar');
        if (toolbar) {
            toolbar.classList.add('hidden');
            toolbar.classList.remove('flex');
        }
    }

    setImageWidth(percentage) {
        if (!this.activeImage) return;
        this.activeImage.style.width = percentage;
        this.handleInput();
    }

    setImageAlignment(align) {
        if (!this.activeImage) return;
        const figure = this.activeImage.closest('figure') || this.activeImage.parentElement;
        if (align === 'center') {
            this.activeImage.className = 'mx-auto block my-4 max-w-full rounded';
            if (figure) figure.className = 'text-center my-4';
        } else if (align === 'right') {
            this.activeImage.className = 'ml-auto block my-4 max-w-full rounded';
            if (figure) figure.className = 'text-right my-4';
        } else {
            this.activeImage.className = 'mr-auto block my-4 max-w-full rounded';
            if (figure) figure.className = 'text-left my-4';
        }
        this.handleInput();
    }

    /* -------------------------------------------------------------
     * Basic Commands, History & Stats
     * ------------------------------------------------------------- */

    execute(command, value = null) {
        const active = this.getActiveEditor();
        if (active) active.focus();
        document.execCommand(command, false, value);
        this.handleInput();
    }

    setTextColor(hexColor) {
        this.execute('foreColor', hexColor);
    }

    setHighlightColor(hexColor) {
        this.execute('hiliteColor', hexColor);
    }

    clearFormatting() {
        this.execute('removeFormat');
    }

    setHeading(tag) {
        if (tag === 'p') {
            this.execute('formatBlock', '<p>');
        } else {
            this.execute('formatBlock', `<${tag}>`);
        }
    }

    insertHorizontalRule() {
        this.execute('insertHorizontalRule');
    }

    insertPageBreak() {
        const activeEditor = this.getActiveEditor();
        const currentSheet = activeEditor?.closest('.document-page-sheet');

        if (!currentSheet || !this.pagesWrapper) {
            if (this.editorEl) {
                const pb = document.createElement('div');
                pb.className = 'page-break my-8 border-b-2 border-dashed border-gray-400 dark:border-gray-600 flex items-center justify-center text-xs text-gray-500 select-none';
                pb.setAttribute('data-page-break', 'true');
                pb.innerHTML = '<span class="bg-gray-100 dark:bg-gray-800 px-3 py-1 rounded-full uppercase tracking-wider font-mono">Pemisah Halaman</span>';
                this.insertAtCursor(pb.outerHTML);
            }
            return;
        }

        // Extract content after cursor
        let nextContent = '<p><br></p>';
        const sel = window.getSelection();
        if (sel && sel.rangeCount > 0 && activeEditor.contains(sel.anchorNode)) {
            const range = sel.getRangeAt(0);
            const afterRange = document.createRange();
            afterRange.setStart(range.endContainer, range.endOffset);
            afterRange.setEnd(activeEditor, activeEditor.childNodes.length);
            const extracted = afterRange.extractContents();
            const temp = document.createElement('div');
            temp.appendChild(extracted);
            if (temp.innerHTML.trim()) {
                nextContent = temp.innerHTML;
            }
        }

        const newSheet = document.createElement('article');
        newSheet.className = 'document-page-sheet page-a4 relative flex flex-col justify-between shadow-2xl';
        this.applyMarginsToSheet(newSheet, this.currentMarginPreset);

        const header = document.createElement('header');
        header.className = 'page-sheet-header flex items-center justify-between text-[11px] text-gray-400 pb-2 border-b border-gray-100 dark:border-gray-800 select-none no-print';
        header.innerHTML = `
            <span class="header-doc-title truncate max-w-[60%] font-medium">${this.escapeHtml(this.getTitle())}</span>
            <span class="header-chapter-name text-gray-400 font-serif italic text-[11px]"></span>
        `;
        newSheet.appendChild(header);

        const body = document.createElement('div');
        body.className = 'page-sheet-body flex-1 outline-none';
        body.setAttribute('contenteditable', 'true');
        body.setAttribute('spellcheck', 'true');
        body.setAttribute('role', 'textbox');
        body.setAttribute('aria-multiline', 'true');
        body.innerHTML = nextContent;
        newSheet.appendChild(body);

        const footer = document.createElement('footer');
        footer.className = 'page-sheet-footer flex items-center justify-between text-xs text-gray-400 pt-2 border-t border-gray-100 dark:border-gray-800 select-none no-print';
        footer.innerHTML = `
            <span class="text-[11px] text-gray-400 font-mono">Dokumen VelScribe Wordcraft</span>
            <span class="footer-page-num text-xs font-semibold text-gray-500 font-mono"></span>
        `;
        newSheet.appendChild(footer);

        const gap = document.createElement('div');
        gap.className = 'page-sheet-gap select-none no-print';
        gap.innerHTML = '<span class="page-gap-indicator">Pemisah Lembar A4</span>';

        const nextEl = currentSheet.nextElementSibling;
        if (nextEl && nextEl.classList.contains('page-sheet-gap')) {
            nextEl.after(newSheet);
            newSheet.before(gap);
        } else {
            currentSheet.after(newSheet);
            currentSheet.after(gap);
        }

        this.renumberPages();

        body.focus();
        const r = document.createRange();
        r.selectNodeContents(body);
        r.collapse(true);
        const s = window.getSelection();
        s.removeAllRanges();
        s.addRange(r);

        this.handleInput();
    }

    insertImage(src, alt = '') {
        if (!src) return;
        const active = this.getActiveEditor();
        if (active) active.focus();

        const figure = document.createElement('figure');
        figure.className = 'my-6 text-center';

        const img = document.createElement('img');
        img.src = src;
        img.alt = alt;
        img.className = 'mx-auto block max-w-full h-auto rounded-lg shadow-sm border border-gray-200 dark:border-gray-700';

        figure.appendChild(img);
        if (alt) {
            const figcaption = document.createElement('figcaption');
            figcaption.className = 'text-xs text-gray-500 mt-2 italic font-serif';
            figcaption.textContent = alt;
            figure.appendChild(figcaption);
        }

        const sel = window.getSelection();
        if (sel && sel.rangeCount > 0 && active?.contains(sel.anchorNode)) {
            sel.getRangeAt(0).insertNode(figure);
        } else if (active) {
            active.appendChild(figure);
        }
        this.handleInput();
    }

    insertTable(rows = 3, cols = 3) {
        const active = this.getActiveEditor();
        if (active) active.focus();

        const table = document.createElement('table');
        table.className = 'w-full border-collapse my-4 border border-gray-300 dark:border-gray-700 text-sm';

        const thead = document.createElement('thead');
        const headerRow = document.createElement('tr');
        for (let c = 1; c <= cols; c++) {
            const th = document.createElement('th');
            th.className = 'border border-gray-300 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 p-2 text-left font-semibold';
            th.innerHTML = `Kolom ${c}`;
            headerRow.appendChild(th);
        }
        thead.appendChild(headerRow);
        table.appendChild(thead);

        const tbody = document.createElement('tbody');
        for (let r = 1; r <= rows - 1; r++) {
            const tr = document.createElement('tr');
            for (let c = 1; c <= cols; c++) {
                const td = document.createElement('td');
                td.className = 'border border-gray-300 dark:border-gray-700 p-2 text-left';
                td.innerHTML = `Data ${r},${c}`;
                tr.appendChild(td);
            }
            tbody.appendChild(tr);
        }
        table.appendChild(tbody);

        const sel = window.getSelection();
        if (sel && sel.rangeCount > 0 && active?.contains(sel.anchorNode)) {
            sel.getRangeAt(0).insertNode(table);
        } else if (active) {
            active.appendChild(table);
        }
        this.handleInput();
    }

    insertLink(url, text = null) {
        if (!url) return;
        const active = this.getActiveEditor();
        if (active) active.focus();
        const sel = window.getSelection();
        if (sel && sel.isCollapsed && text && active) {
            const a = document.createElement('a');
            a.href = url;
            a.textContent = text;
            a.target = '_blank';
            a.className = 'text-blue-600 dark:text-blue-400 underline';
            sel.getRangeAt(0).insertNode(a);
        } else {
            this.execute('createLink', url);
        }
        this.handleInput();
    }

    insertAtCursor(htmlText) {
        const active = this.getActiveEditor();
        if (active) active.focus();
        const sel = window.getSelection();
        if (sel && sel.rangeCount > 0 && active?.contains(sel.anchorNode)) {
            const range = sel.getRangeAt(0);
            const temp = document.createElement('div');
            temp.innerHTML = htmlText;
            const frag = document.createDocumentFragment();
            while (temp.firstChild) {
                frag.appendChild(temp.firstChild);
            }
            range.insertNode(frag);
        } else if (active) {
            active.innerHTML += htmlText;
        }
        this.handleInput();
    }

    replaceSelectedText(newText) {
        const active = this.getActiveEditor();
        if (active) active.focus();
        const sel = window.getSelection();
        if (!sel || sel.rangeCount === 0 || !active?.contains(sel.anchorNode)) return;
        const range = sel.getRangeAt(0);
        range.deleteContents();
        const frag = document.createDocumentFragment();
        const temp = document.createElement('div');
        temp.innerHTML = newText;
        while (temp.firstChild) {
            frag.appendChild(temp.firstChild);
        }
        range.insertNode(frag);
        this.handleInput();
    }

    getSelectionState() {
        const sel = window.getSelection();
        if (!sel || sel.rangeCount === 0) return { hasSelection: false };

        const range = sel.getRangeAt(0);
        const text = sel.toString();
        const hasSelection = text.length > 0;

        let rect = null;
        if (hasSelection) {
            rect = range.getBoundingClientRect();
        }

        return {
            hasSelection,
            text,
            range,
            rect,
            isBold: document.queryCommandState('bold'),
            isItalic: document.queryCommandState('italic'),
            isUnderline: document.queryCommandState('underline'),
            isStrike: document.queryCommandState('strikethrough'),
            alignLeft: document.queryCommandState('justifyLeft'),
            alignCenter: document.queryCommandState('justifyCenter'),
            alignRight: document.queryCommandState('justifyRight'),
            alignJustify: document.queryCommandState('justifyFull'),
        };
    }

    recordHistory() {
        const content = this.getContent();
        if (this.history[this.historyIndex] === content) return;

        this.history = this.history.slice(0, this.historyIndex + 1);
        this.history.push(content);
        if (this.history.length > this.maxHistory) {
            this.history.shift();
        }
        this.historyIndex = this.history.length - 1;
    }

    undo() {
        if (this.historyIndex > 0) {
            this.historyIndex--;
            this.setContent(this.history[this.historyIndex], false);
            this.updateStats();
            this.updateOutline();
        }
    }

    redo() {
        if (this.historyIndex < this.history.length - 1) {
            this.historyIndex++;
            this.setContent(this.history[this.historyIndex], false);
            this.updateStats();
            this.updateOutline();
        }
    }

    updateStats() {
        const bodies = this.getAllEditorBodies();
        let fullText = '';
        bodies.forEach(b => {
            fullText += (b.innerText || b.textContent || '') + '\n\n';
        });

        const cleanText = fullText.trim();
        const charCount = cleanText.length;
        const words = cleanText ? cleanText.split(/\s+/).filter(Boolean) : [];
        const wordCount = words.length;

        const paragraphs = cleanText ? cleanText.split(/\n\s*\n/).filter(Boolean) : [];
        const paragraphCount = paragraphs.length;

        const sentences = cleanText ? cleanText.split(/[.!?]+/).filter(Boolean) : [];
        const sentenceCount = sentences.length;

        const readingTimeMinutes = Math.max(1, Math.ceil(wordCount / 200));

        const pageSheets = this.getAllPageSheets();
        const pageCount = Math.max(pageSheets.length, 1);

        this.onStatsChange({
            wordCount,
            charCount,
            sentenceCount,
            paragraphCount,
            readingTimeMinutes,
            readingTime: `${readingTimeMinutes} min baca`,
            pageCount,
            activePage: this.activePageIndex || 1,
        });
    }

    updateOutline() {
        const headings = [];
        const bodies = this.getAllEditorBodies();
        let headingIndex = 0;

        const isTOC = (text) => {
            if (!text) return true;
            const clean = text.trim();
            if (/(\.{2,}|…|·{2,}|\.\s*\.\s*\.)/.test(clean)) return true;
            if (/[\s.\t]{2,}(\d+|[ivxlcdm]+)$/i.test(clean)) return true;
            if (/\.\s*(\d+|[ivxlcdm]+)$/i.test(clean) && !/^\d+(\.\d+)+$/.test(clean)) return true;
            return false;
        };

        const isCoverMetadata = (text) => {
            if (!text) return true;
            return /^(OLEH|DISUSUN|BY|NIM|NPM|NIS|NIK|PROGRAM\s+STUDI|JURUSAN|FAKULTAS|POLITEKNIK|UNIVERSITAS|DEPARTMENT|STUDY\s+PROGRAM|SEKOLAH\s+TINGGI|INSTITUT|SEBAGAI\s+SALAH\s+SATU\s+SYARAT|SUBMITTED\s+AS|KEMENTERIAN|\d{4})\b/i.test(text.trim());
        };

        bodies.forEach((body, bodyIdx) => {
            const headingEls = body.querySelectorAll('h1, h2, h3, h4');
            headingEls.forEach(el => {
                if (el.classList?.contains('toc-entry') || el.closest('.toc-entry')) return;

                const text = (el.innerText || el.textContent || '').trim();
                if (!text || text.length < 2 || text.length > 120) return;
                if (isTOC(text) || isCoverMetadata(text)) return;

                const id = el.id || `heading-${headingIndex++}`;
                el.id = id;
                headings.push({
                    id,
                    level: parseInt(el.tagName.replace('H', ''), 10),
                    text,
                    pageNumber: bodyIdx + 1,
                });
            });
        });

        this.onOutlineChange(headings);
    }

    async exportAs(format) {
        const title = this.getTitle();
        const content = this.getContent();

        if (format === 'pdf') {
            window.print();
            return;
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        try {
            const response = await fetch('/api/documents/export', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': '*/*',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                },
                body: JSON.stringify({
                    title,
                    content,
                    format,
                }),
            });

            if (!response.ok) {
                throw new Error('Gagal mengekspor dokumen');
            }

            const blob = await response.blob();
            const disposition = response.headers.get('Content-Disposition');
            let filename = `${title.replace(/[^a-zA-Z0-9_\-]/g, '_')}.${format}`;
            if (disposition && disposition.includes('filename=')) {
                const match = disposition.match(/filename="?([^"]+)"?/);
                if (match && match[1]) filename = match[1];
            }

            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        } catch (err) {
            console.error('API export failed, performing client-side fallback:', err);
            this.clientSideExport(format, title, content);
        }
    }

    clientSideExport(format, title, htmlContent) {
        let content = '';
        let mime = 'text/plain';
        let ext = format;

        if (format === 'html') {
            mime = 'text/html; charset=utf-8';
            ext = 'html';
            content = `<!DOCTYPE html><html><head><meta charset="utf-8"><title>${title}</title><style>body{font-family:'Times New Roman',serif;max-width:210mm;margin:40px auto;padding:40mm 30mm 30mm 40mm;line-height:1.5;}h1{text-align:center;}p{text-align:justify;text-indent:1.25cm;}</style></head><body>${htmlContent}</body></html>`;
        } else if (format === 'json') {
            mime = 'application/json; charset=utf-8';
            ext = 'json';
            content = JSON.stringify({
                title,
                content: htmlContent,
                exported_at: new Date().toISOString(),
            }, null, 2);
        } else if (format === 'txt') {
            mime = 'text/plain; charset=utf-8';
            ext = 'txt';
            const bodies = this.getAllEditorBodies();
            let allText = '';
            bodies.forEach(b => {
                allText += (b.innerText || b.textContent || '') + '\n\n';
            });
            content = `${title}\n\n${allText.trim()}`;
        } else {
            mime = 'application/msword; charset=utf-8';
            ext = 'doc';
            content = `<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'><head><meta charset='utf-8'><title>${title}</title><style>body{font-family:'Times New Roman',serif;font-size:12pt;line-height:1.5;}h1{text-align:center;}p{text-align:justify;text-indent:1.25cm;}</style></head><body>${htmlContent}</body></html>`;
        }

        const blob = new Blob([content], { type: mime });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `${title.replace(/[^a-zA-Z0-9_\-]/g, '_')}.${ext}`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    }

    saveToLocalStorage() {
        try {
            const data = {
                title: this.getTitle(),
                content: this.getContent(),
                format: this.currentFormat,
                marginPreset: this.currentMarginPreset,
                savedAt: new Date().toISOString(),
            };
            localStorage.setItem('velscribe_document_state', JSON.stringify(data));
        } catch (e) {
            console.warn('LocalStorage save error:', e);
        }
    }

    loadFromLocalStorage() {
        try {
            const raw = localStorage.getItem('velscribe_document_state');
            if (!raw) return false;
            const data = JSON.parse(raw);
            if (data && data.content) {
                this.setTitle(data.title || 'Dokumen Tanpa Judul');
                this.setContent(data.content);
                this.setFormatBadge(data.format || 'docx');
                if (data.marginPreset) this.setMarginPreset(data.marginPreset);
                return true;
            }
        } catch (e) {
            console.warn('LocalStorage load error:', e);
        }
        return false;
    }

    getTitle() {
        return this.titleInput ? this.titleInput.value.trim() : 'Dokumen Tanpa Judul';
    }

    setTitle(title) {
        if (this.titleInput) {
            this.titleInput.value = title;
        }
        // Also update headers across sheets
        const sheets = this.getAllPageSheets();
        sheets.forEach(sheet => {
            const hTitle = sheet.querySelector('.header-doc-title');
            if (hTitle) hTitle.textContent = title;
        });
    }

    getContent() {
        const bodies = this.getAllEditorBodies();
        if (bodies.length === 0) {
            return this.editorEl ? this.editorEl.innerHTML : '';
        }
        if (bodies.length === 1) {
            return bodies[0].innerHTML;
        }

        return bodies
            .map(body => body.innerHTML.trim())
            .filter(Boolean)
            .join('\n<div class="page-break my-8 border-b-2 border-dashed border-gray-400 dark:border-gray-600 text-center py-2 select-none" data-page-break="true"><span class="bg-gray-100 dark:bg-gray-800 px-3 py-1 rounded-full text-xs text-gray-500 font-mono">Pemisah Halaman</span></div>\n');
    }

    setContent(html, record = true) {
        if (this.pagesWrapper) {
            this.renderPaginatedDocument(html);
            if (record) this.recordHistory();
            this.updateStats();
            this.updateOutline();
        } else if (this.editorEl) {
            this.editorEl.innerHTML = html;
            if (record) this.recordHistory();
            this.updateStats();
            this.updateOutline();
        }
    }

    setFormatBadge(format) {
        this.currentFormat = format;
        if (this.formatBadge) {
            this.formatBadge.textContent = format.toUpperCase();
        }
    }

    capitalizeWords(str) {
        return str.replace(/\b\w/g, l => l.toUpperCase());
    }

    escapeHtml(str) {
        if (!str) return '';
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
}
