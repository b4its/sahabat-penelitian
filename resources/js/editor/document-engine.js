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

import { DocxImporter } from './docx-importer.js';
import katex from 'katex';

export const PAPER_DIMENSIONS = {
    a4: { name: 'A4', width: 210, height: 297 },
    letter: { name: 'Letter', width: 215.9, height: 279.4 },
    legal: { name: 'Legal', width: 215.9, height: 355.6 },
    folio: { name: 'Folio / F4', width: 215, height: 330 },
    executive: { name: 'Executive', width: 184.1, height: 266.7 },
    a3: { name: 'A3', width: 297, height: 420 },
    a5: { name: 'A5', width: 148, height: 210 },
    b5: { name: 'B5', width: 182, height: 257 },
    statement: { name: 'Statement', width: 139.7, height: 215.9 },
    tabloid: { name: 'Tabloid / Ledger', width: 279.4, height: 431.8 },
    custom: { name: 'Custom', width: 210, height: 297 },
};

export const MARGIN_PRESETS = {
    skripsi: { top: 40, bottom: 30, left: 40, right: 30, gutter: 0, gutterPos: 'left' },
    normal: { top: 25.4, bottom: 25.4, left: 25.4, right: 25.4, gutter: 0, gutterPos: 'left' },
    narrow: { top: 12.7, bottom: 12.7, left: 12.7, right: 12.7, gutter: 0, gutterPos: 'left' },
    moderate: { top: 25.4, bottom: 25.4, left: 19.1, right: 19.1, gutter: 0, gutterPos: 'left' },
    wide: { top: 25.4, bottom: 25.4, left: 50.8, right: 50.8, gutter: 0, gutterPos: 'left' },
    mirrored: { top: 25.4, bottom: 25.4, inside: 31.8, outside: 25.4, gutter: 0, gutterPos: 'left' },
    office2003: { top: 25.4, bottom: 25.4, left: 31.8, right: 31.8, gutter: 0, gutterPos: 'left' },
};

export class DocumentEngine {
    static renderLatexHtml(latex, isDisplay = true) {
        if (!latex) return '';
        let clean = String(latex)
            .replace(/<[^>]+>/g, '')
            .replace(/&nbsp;/g, ' ')
            .replace(/&amp;/g, '&')
            .replace(/&lt;/g, '<')
            .replace(/&gt;/g, '>')
            .replace(/&quot;/g, '"')
            .replace(/&#039;/g, "'")
            .trim();
        clean = clean.replace(/(?<!\\)%/g, '\\%');
        try {
            const k = (typeof window !== 'undefined' && window.katex) ? window.katex : katex;
            if (k && typeof k.renderToString === 'function') {
                return k.renderToString(clean, {
                    displayMode: isDisplay,
                    throwOnError: false,
                    strict: 'ignore',
                    output: 'htmlAndMathml',
                });
            }
        } catch (err) {
            console.warn('KaTeX render error:', err);
        }
        return `<span class="katex-fallback font-mono text-sm">${DocumentEngine.escapeHtmlStatic(clean)}</span>`;
    }

    static escapeHtmlStatic(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

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
        this.currentMargins = { top: 25.4, bottom: 25.4, left: 25.4, right: 25.4, gutter: 0, gutterPos: 'left' };
        this.customWidthMm = 210;
        this.customHeightMm = 297;
        this.multiplePagesMode = 'normal';
        this.sectionStart = 'newpage';
        this.differentOddEven = false;
        this.edgeHeader = 1.5;
        this.edgeFooter = 1.5;
        this.verticalAlign = 'top';
        this.columns = 1;
        this.hyphenation = 'none';
        this.tabStops = [{ pos: 1.25, align: 'left', leader: 'none' }];
        this.defaultTabStop = 1.27;
        this.watermark = null;
        this.pageColor = '#ffffff';
        this.pageBorder = null;
        this.lineNumbers = 'none';
        this.lineNumbersOptions = { startAt: 1, countBy: 1 };
        this.paragraphSpacing = { before: 0, after: 6 };
        this.paragraphIndent = { left: 0, right: 0, special: 'none', specialVal: 1.25, mirrorIndents: false };
        this.activePageIndex = 1;
        this.history = [];
        this.historyIndex = -1;
        this.maxHistory = 50;
        this.autoSaveTimer = null;
        this.isTyping = false;
        this.isPaginating = false;
        this.activeTable = null;
        this.activeImage = null;
        this.customHeaderText = '';
        this.customFooterText = '';
        this.differentFirstPage = false;
        this.pageNumberFormat = 'full';
        this.showPageNumber = true;
        this.startPageNumber = 1;
        this.copiedFormat = null;
        this.currentFontFamily = "'Times New Roman', serif";
        this.currentFontSize = '12pt';
        this.currentLineSpacing = '1.5';
        this.citationStyle = 'apa';
        this.sources = this.loadSavedSources();

        window.documentEngine = this;
        this.init();
    }

    init() {
        if (!this.pagesWrapper && !this.editorEl) return;

        const activeFontSelect = document.getElementById('font-family-select');
        if (activeFontSelect && activeFontSelect.value) {
            this.currentFontFamily = activeFontSelect.value;
        }
        const activeSizeSelect = document.getElementById('font-size-select');
        if (activeSizeSelect && activeSizeSelect.value) {
            this.currentFontSize = activeSizeSelect.value;
        }
        const activeLineSelect = document.getElementById('line-spacing-select');
        if (activeLineSelect && activeLineSelect.value) {
            this.currentLineSpacing = activeLineSelect.value;
        }

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

                const eq = e.target.closest('.latex-equation');
                if (eq) {
                    window.dispatchEvent(new CustomEvent('select-latex-equation', { detail: { element: eq } }));
                }

                // Table of Contents click navigation (Jump to heading & highlight)
                const tocLink = e.target.closest('.toc-link, .toc-item a');
                if (tocLink) {
                    e.preventDefault();
                    const targetId = (tocLink.getAttribute('href') || '').replace('#', '') || tocLink.closest('.toc-item')?.getAttribute('data-target');
                    if (targetId) {
                        const targetEl = document.getElementById(targetId);
                        if (targetEl) {
                            targetEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            targetEl.classList.add('ring-2', 'ring-indigo-500', 'bg-indigo-50/60', 'dark:bg-indigo-950/40', 'transition', 'duration-500');
                            setTimeout(() => {
                                targetEl.classList.remove('ring-2', 'ring-indigo-500', 'bg-indigo-50/60', 'dark:bg-indigo-950/40');
                            }, 1600);
                        }
                    }
                    return;
                }

                // Table of Contents Action Bar Buttons
                const btnTocUpdate = e.target.closest('.btn-toc-update');
                if (btnTocUpdate) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.activeTocBlock = btnTocUpdate.closest('.table-of-contents, [data-toc-block="true"]');
                    window.dispatchEvent(new CustomEvent('open-update-toc-dialog', { detail: { element: this.activeTocBlock } }));
                    return;
                }
                const btnTocCustomize = e.target.closest('.btn-toc-customize');
                if (btnTocCustomize) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.activeTocBlock = btnTocCustomize.closest('.table-of-contents, [data-toc-block="true"]');
                    window.dispatchEvent(new CustomEvent('open-custom-toc-dialog', { detail: { element: this.activeTocBlock } }));
                    return;
                }
                const btnTocDelete = e.target.closest('.btn-toc-delete');
                if (btnTocDelete) {
                    e.preventDefault();
                    e.stopPropagation();
                    const tocBlock = btnTocDelete.closest('.table-of-contents, [data-toc-block="true"]');
                    this.removeTableOfContents(tocBlock);
                    return;
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

            targetContainer.addEventListener('dblclick', (e) => {
                const eq = e.target.closest('.latex-equation');
                if (eq) {
                    e.preventDefault();
                    window.dispatchEvent(new CustomEvent('edit-latex-equation', { detail: { element: eq } }));
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
                const state = this.getSelectionState();
                this.updateToolbarSelectionState(state);
                this.onSelectionChange(state);
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

        // Backspace or Delete on focused or selected Table of Contents block
        if (e.key === 'Backspace' || e.key === 'Delete') {
            const activeEl = document.activeElement;
            if (activeEl && (activeEl.classList?.contains('table-of-contents') || activeEl.hasAttribute('data-toc-block'))) {
                e.preventDefault();
                this.removeTableOfContents(activeEl);
                return;
            }
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

        html = this.formatLatexEquationsInHtml(html);

        const isTableOfContentsLine = (str) => {
            if (!str) return false;
            const clean = str.trim().replace(/\s+/g, ' ');
            if (!clean || clean.length < 4 || clean.length > 140) return false;
            if (/^(NIM|NPM|NIS|NIK|OLEH|DISUSUN|BY|PROGRAM\s+STUDI|JURUSAN|FAKULTAS|POLITEKNIK|UNIVERSITAS|DEPARTMENT|STUDY\s+PROGRAM|SEKOLAH\s+TINGGI|INSTITUT|SEBAGAI\s+SALAH\s+SATU\s+SYARAT|SUBMITTED\s+AS)\b/i.test(clean)) {
                return false;
            }
            return /^(.{2,100}?)(?:[\s.·…]{3,}|\t+)([ivxlcdm\d]{1,5})$/i.test(clean);
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

        const isLikelyCoverPage = (blocks) => {
            let totalWords = 0;
            let hasCoverKeyword = false;
            let longParagraphCount = 0;

            blocks.forEach(b => {
                const text = (b.textContent || '').trim();
                const words = text.split(/\s+/).filter(Boolean).length;
                totalWords += words;
                if (words > 60) longParagraphCount++;
                if (/\b(OLEH|DISUSUN\s+OLEH|NIM|NPM|PROGRAM\s+STUDI|FAKULTAS|UNIVERSITAS|POLITEKNIK|INSTITUT|SEKOLAH\s+TINGGI|TUGAS\s+AKHIR|SKRIPSI|THESIS)\b/i.test(text)) {
                    hasCoverKeyword = true;
                }
            });

            if (longParagraphCount > 0) return false;
            return hasCoverKeyword && totalWords < 180;
        };

        const finalizeCurrentPage = () => {
            if (currentBlocks.length === 0) return;
            const pageNum = pages.length + 1;
            const hasHeading = currentBlocks.some(b => {
                if (!b.classList && !b.tagName) return false;
                return b.classList?.contains('heading-preliminary') ||
                       b.classList?.contains('heading-chapter') ||
                       /^H[1-6]$/.test(b.tagName || '');
            });
            const hasCoverExplicit = currentBlocks.some(b => {
                if (!b.classList) return false;
                return b.classList.contains('academic-cover') ||
                       b.classList.contains('cover-title') ||
                       b.classList.contains('cover-badge');
            });
            const isCover = pageNum === 1 && (hasCoverExplicit || (!currentChapter && !hasHeading && isLikelyCoverPage(currentBlocks)));
            pages.push({
                pageNumber: pageNum,
                chapterName: currentChapter,
                contentHtml: currentBlocks.map(b => b.outerHTML || b).join('\n'),
                isCover: isCover,
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

        const isMajorHeading = (el) => {
            const tag = (el.tagName || '').toUpperCase();
            const text = (el.textContent || '').trim();
            if (isTableOfContentsLine(text)) return false;

            if (el.classList?.contains('heading-chapter') || el.classList?.contains('heading-preliminary')) {
                return true;
            }

            if (tag === 'H1') {
                return text.length > 0 && text.length <= 120;
            }

            if (text.length <= 80 && !/[.!?]$/.test(text)) {
                if (/^(BAB|CHAPTER|BAGIAN|SECTION)\s*(?:[IVXLCDM]+|\d+)/i.test(text)) {
                    return true;
                }
                if (/^(ABSTRAK|ABSTRACT|KATA\s+PENGANTAR|PRAKATA|LEMBAR\s+PENGESAHAN|HALAMAN\s+PENGESAHAN|LEMBAR\s+PERSETUJUAN|HALAMAN\s+PERSETUJUAN|PERNYATAAN\s+KEASLIAN|PERNYATAAN\s+ORISINALITAS|DAFTAR\s+ISI|DAFTAR\s+TABEL|DAFTAR\s+GAMBAR|DAFTAR\s+LAMPIRAN|DAFTAR\s+PUSTAKA|RIWAYAT\s+HIDUP|BIODATA\s+PENULIS|LAMPIRAN)(\s+.*)?$/i.test(text)) {
                    return true;
                }
            }

            return false;
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

            // 2. Major Heading (H1, Chapter, Preliminary)
            if (isMajorHeading(el)) {
                if (currentBlocks.length > 0) {
                    finalizeCurrentPage();
                }
                currentChapter = (el.textContent || '').trim().replace(/\s+/g, ' ');
                currentBlocks.push(el);
                currentWeight += estimateElementWeight(el);
                continue;
            }

            // Track H2 / H3 if no currentChapter set yet
            const tag = (el.tagName || '').toUpperCase();
            if ((tag === 'H2' || tag === 'H3' || el.classList?.contains('heading-section')) && !currentChapter) {
                const subText = (el.textContent || '').trim().replace(/\s+/g, ' ');
                if (subText && subText.length <= 80 && !isTableOfContentsLine(subText)) {
                    currentChapter = subText;
                }
            }

            // 3. Regular Element (p, table, image, list, etc.)
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
            sheet.className = 'document-page-sheet relative flex flex-col justify-between shadow-2xl';
            sheet.setAttribute('data-page-index', pageNum);
            sheet.setAttribute('id', pageNum === 1 ? 'document-page-canvas' : `document-page-${pageNum}`);

            // Apply full page setup (paper size, orientation, and margins)
            this.applyPageSetupToSheet(sheet, pageNum);

            // Apply dark mode sheet if active
            const isDark = document.getElementById('btn-toggle-dark-sheet')?.classList.contains('active-dark') || false;
            if (isDark) {
                sheet.classList.add('canvas-dark-sheet');
            }

            // Header (Pristine running header without synthetic in-content markers)
            const isSuppressedFirstPage = this.differentFirstPage && pageNum === 1;
            const header = document.createElement('header');
            header.className = `page-sheet-header flex items-center justify-between text-[11px] text-gray-400 pb-2 border-b border-gray-100 dark:border-gray-800 select-none cursor-pointer transition hover:bg-indigo-50/40 dark:hover:bg-indigo-950/20 rounded px-1 -mx-1 ${isSuppressedFirstPage ? 'opacity-30' : ''}`;
            header.setAttribute('title', 'Klik untuk menyesuaikan Header & Footer');
            header.setAttribute('data-action', 'header-footer-modal');

            const headerLeft = this.customHeaderText || docTitle;
            const headerRight = p.chapterName || '';
            header.innerHTML = `
                <span class="header-doc-title truncate max-w-[55%] font-medium text-gray-600 dark:text-gray-300" title="${this.escapeHtml(headerLeft)}">${this.escapeHtml(headerLeft)}</span>
                <span class="header-chapter-name text-gray-400 font-serif italic text-[11px] truncate max-w-[42%] text-right" title="${this.escapeHtml(headerRight)}">${this.escapeHtml(headerRight)}</span>
            `;
            sheet.appendChild(header);

            // Body
            const body = document.createElement('div');
            body.className = 'page-sheet-body flex-1 outline-none';
            if (this.currentFontFamily) {
                body.style.fontFamily = this.currentFontFamily;
            }
            if (this.currentFontSize) {
                body.style.fontSize = this.currentFontSize;
            }
            if (this.currentLineSpacing) {
                body.style.lineHeight = this.currentLineSpacing;
            }
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
            this.renderLatexInContainer(body);
            sheet.appendChild(body);

            // Footer (Clean document footer without synthetic page marks or 'halaman dari')
            const footer = document.createElement('footer');
            footer.className = `page-sheet-footer flex items-center justify-between text-[11px] text-gray-400 pt-2 border-t border-gray-100 dark:border-gray-800 select-none cursor-pointer transition hover:bg-indigo-50/40 dark:hover:bg-indigo-950/20 rounded px-1 -mx-1 ${isSuppressedFirstPage ? 'opacity-30' : ''}`;
            footer.setAttribute('title', 'Klik untuk menyesuaikan Header & Footer');
            footer.setAttribute('data-action', 'header-footer-modal');

            const footerText = this.customFooterText || '';
            footer.innerHTML = `
                <span class="footer-doc-title text-[11px] text-gray-400 font-mono truncate max-w-[85%]" title="${this.escapeHtml(footerText)}">${this.escapeHtml(footerText)}</span>
            `;
            sheet.appendChild(footer);

            // Side Page Indicator (Outside Document Canvas)
            const sideIndicator = document.createElement('div');
            sideIndicator.className = 'page-side-indicator absolute -left-12 sm:-left-16 top-6 select-none no-print pointer-events-auto flex flex-col items-center';
            sideIndicator.title = `Lembar Halaman ${pageNum} dari ${totalPages}`;
            sideIndicator.innerHTML = `
                <div class="flex flex-col items-center justify-center min-w-[38px] px-2 py-1.5 rounded-lg bg-white/95 dark:bg-gray-800/95 border border-gray-200 dark:border-gray-700 shadow-sm text-gray-600 dark:text-gray-300 font-mono text-[11px] hover:border-indigo-400 hover:text-indigo-600 transition">
                    <span class="text-[9px] uppercase tracking-wider text-gray-400 dark:text-gray-500 font-semibold leading-none mb-0.5">Hal</span>
                    <span class="font-bold text-xs leading-none text-indigo-600 dark:text-indigo-400">${pageNum}</span>
                    <span class="text-[9px] text-gray-400 dark:text-gray-500 leading-none mt-0.5">/ ${totalPages}</span>
                </div>
                ${isSuppressedFirstPage ? '<span class="mt-1 px-1.5 py-0.5 rounded text-[9px] bg-amber-50 dark:bg-amber-950/80 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-800 font-sans shadow-2xs">Sampul</span>' : ''}
            `;
            if (this.showPageNumber === false) {
                sideIndicator.style.display = 'none';
            }
            sheet.appendChild(sideIndicator);

            // Side Header & Footer Quick Action Buttons (Outside Document Canvas)
            const sideActions = document.createElement('div');
            sideActions.className = 'page-side-actions absolute -right-12 sm:-right-14 top-4 select-none no-print flex flex-col gap-2 pointer-events-auto';
            sideActions.innerHTML = `
                <button data-action="header-footer-modal" class="p-1.5 rounded-lg bg-white/95 dark:bg-gray-800/95 border border-gray-200 dark:border-gray-700 shadow-sm text-gray-400 hover:text-indigo-600 hover:border-indigo-400 transition" title="Sesuaikan Header Dokumen">
                    <span class="text-xs">🏷️</span>
                </button>
            `;
            sheet.appendChild(sideActions);

            const sideFooterAction = document.createElement('div');
            sideFooterAction.className = 'page-side-footer-action absolute -right-12 sm:-right-14 bottom-4 select-none no-print pointer-events-auto';
            sideFooterAction.innerHTML = `
                <button data-action="header-footer-modal" class="p-1.5 rounded-lg bg-white/95 dark:bg-gray-800/95 border border-gray-200 dark:border-gray-700 shadow-sm text-gray-400 hover:text-indigo-600 hover:border-indigo-400 transition" title="Sesuaikan Footer Dokumen">
                    <span class="text-xs">📋</span>
                </button>
            `;
            sheet.appendChild(sideFooterAction);

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

    formatPageNumber(pageNum, totalPages) {
        if (!this.showPageNumber) return '';
        const offset = (parseInt(this.startPageNumber, 10) || 1) - 1;
        const current = pageNum + offset;
        const total = totalPages + offset;

        switch (this.pageNumberFormat) {
            case 'number':
                return `${current}`;
            case 'simple':
                return `${current} dari ${total}`;
            case 'hal':
                return `Hal. ${current}`;
            case 'dash':
                return `- ${current} -`;
            case 'roman': {
                const lookup = [
                    ['m', 1000], ['cm', 900], ['d', 500], ['cd', 400],
                    ['c', 100], ['xc', 90], ['l', 50], ['xl', 40],
                    ['x', 10], ['ix', 9], ['v', 5], ['iv', 4], ['i', 1]
                ];
                let num = current;
                let roman = '';
                for (const [letter, value] of lookup) {
                    while (num >= value) {
                        roman += letter;
                        num -= value;
                    }
                }
                return roman || 'i';
            }
            case 'full':
            default:
                return `Halaman ${current} dari ${total}`;
        }
    }

    setHeaderText(text) {
        this.customHeaderText = (text || '').trim();
        this.renumberPages();
        this.saveToLocalStorage();
    }

    setFooterText(text) {
        this.customFooterText = (text || '').trim();
        this.renumberPages();
        this.saveToLocalStorage();
    }

    setPageNumberFormat(format) {
        this.pageNumberFormat = format || 'full';
        this.renumberPages();
        this.saveToLocalStorage();
    }

    setDifferentFirstPage(enabled) {
        this.differentFirstPage = Boolean(enabled);
        this.renumberPages();
        this.saveToLocalStorage();
    }

    setShowPageNumber(show) {
        this.showPageNumber = Boolean(show);
        this.renumberPages();
        this.saveToLocalStorage();
    }

    setStartPageNumber(start) {
        this.startPageNumber = Math.max(1, parseInt(start, 10) || 1);
        this.renumberPages();
        this.saveToLocalStorage();
    }

    renumberPages() {
        const sheets = this.getAllPageSheets();
        const total = sheets.length;
        const docTitle = this.getTitle() || 'Dokumen VelScribe Wordcraft';

        let activeChapter = '';
        sheets.forEach((sheet, idx) => {
            const pageNum = idx + 1;
            sheet.setAttribute('data-page-index', pageNum);
            if (pageNum === 1) {
                sheet.setAttribute('id', 'document-page-canvas');
            } else {
                sheet.setAttribute('id', `document-page-${pageNum}`);
            }

            const body = sheet.querySelector('.page-sheet-body');
            if (body) {
                body.setAttribute('data-page-number', pageNum);
                const headingEl = body.querySelector('h1, .heading-chapter, .heading-preliminary, h2');
                if (headingEl) {
                    const text = (headingEl.textContent || '').trim().replace(/\s+/g, ' ');
                    if (text && text.length <= 100) {
                        activeChapter = text;
                    }
                }
            }

            const isSuppressedFirstPage = this.differentFirstPage && pageNum === 1;

            const headerEl = sheet.querySelector('.page-sheet-header');
            if (headerEl) {
                headerEl.classList.remove('invisible');
                if (isSuppressedFirstPage) {
                    headerEl.classList.add('opacity-30');
                } else {
                    headerEl.classList.remove('opacity-30');
                }
            }

            const headerTitle = sheet.querySelector('.header-doc-title');
            if (headerTitle) {
                const leftText = this.customHeaderText || docTitle;
                headerTitle.textContent = leftText;
                headerTitle.title = leftText;
            }

            const headerChapter = sheet.querySelector('.header-chapter-name');
            if (headerChapter) {
                headerChapter.textContent = activeChapter;
                headerChapter.title = activeChapter;
            }

            const footerEl = sheet.querySelector('.page-sheet-footer');
            if (footerEl) {
                footerEl.classList.remove('invisible');
                if (isSuppressedFirstPage) {
                    footerEl.classList.add('opacity-30');
                } else {
                    footerEl.classList.remove('opacity-30');
                }
            }

            const footerTitle = sheet.querySelector('.footer-doc-title');
            if (footerTitle) {
                const ftrText = this.customFooterText || '';
                footerTitle.textContent = ftrText;
                footerTitle.title = ftrText;
            }

            const footerNum = sheet.querySelector('.footer-page-num');
            if (footerNum) {
                footerNum.remove();
            }

            // Update or create side indicator (outside document content)
            let sideIndicator = sheet.querySelector('.page-side-indicator');
            if (!sideIndicator) {
                sideIndicator = document.createElement('div');
                sideIndicator.className = 'page-side-indicator absolute -left-12 sm:-left-16 top-6 select-none no-print pointer-events-auto flex flex-col items-center';
                sheet.appendChild(sideIndicator);
            }
            sideIndicator.title = `Lembar Halaman ${pageNum} dari ${total}`;
            sideIndicator.innerHTML = `
                <div class="flex flex-col items-center justify-center min-w-[38px] px-2 py-1.5 rounded-lg bg-white/95 dark:bg-gray-800/95 border border-gray-200 dark:border-gray-700 shadow-sm text-gray-600 dark:text-gray-300 font-mono text-[11px] hover:border-indigo-400 hover:text-indigo-600 transition">
                    <span class="text-[9px] uppercase tracking-wider text-gray-400 dark:text-gray-500 font-semibold leading-none mb-0.5">Hal</span>
                    <span class="font-bold text-xs leading-none text-indigo-600 dark:text-indigo-400">${pageNum}</span>
                    <span class="text-[9px] text-gray-400 dark:text-gray-500 leading-none mt-0.5">/ ${total}</span>
                </div>
                ${isSuppressedFirstPage ? '<span class="mt-1 px-1.5 py-0.5 rounded text-[9px] bg-amber-50 dark:bg-amber-950/80 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-800 font-sans shadow-2xs">Sampul</span>' : ''}
            `;
            if (this.showPageNumber === false) {
                sideIndicator.style.display = 'none';
            } else {
                sideIndicator.style.display = '';
            }

            // Side quick action buttons (outside document content)
            if (!sheet.querySelector('.page-side-actions')) {
                const sideActions = document.createElement('div');
                sideActions.className = 'page-side-actions absolute -right-12 sm:-right-14 top-4 select-none no-print flex flex-col gap-2 pointer-events-auto';
                sideActions.innerHTML = `
                    <button data-action="header-footer-modal" class="p-1.5 rounded-lg bg-white/95 dark:bg-gray-800/95 border border-gray-200 dark:border-gray-700 shadow-sm text-gray-400 hover:text-indigo-600 hover:border-indigo-400 transition" title="Sesuaikan Header Dokumen">
                        <span class="text-xs">🏷️</span>
                    </button>
                `;
                sheet.appendChild(sideActions);
            }

            if (!sheet.querySelector('.page-side-footer-action')) {
                const sideFooterAction = document.createElement('div');
                sideFooterAction.className = 'page-side-footer-action absolute -right-12 sm:-right-14 bottom-4 select-none no-print pointer-events-auto';
                sideFooterAction.innerHTML = `
                    <button data-action="header-footer-modal" class="p-1.5 rounded-lg bg-white/95 dark:bg-gray-800/95 border border-gray-200 dark:border-gray-700 shadow-sm text-gray-400 hover:text-indigo-600 hover:border-indigo-400 transition" title="Sesuaikan Footer Dokumen">
                        <span class="text-xs">📋</span>
                    </button>
                `;
                sheet.appendChild(sideFooterAction);
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

    getDocumentWidthMm() {
        let w = (this.currentPaperSize === 'custom' && this.customWidthMm) ? this.customWidthMm : (PAPER_DIMENSIONS[this.currentPaperSize]?.width || 210);
        let h = (this.currentPaperSize === 'custom' && this.customHeightMm) ? this.customHeightMm : (PAPER_DIMENSIONS[this.currentPaperSize]?.height || 297);
        return this.currentOrientation === 'landscape' ? h : w;
    }

    getDocumentHeightMm() {
        let w = (this.currentPaperSize === 'custom' && this.customWidthMm) ? this.customWidthMm : (PAPER_DIMENSIONS[this.currentPaperSize]?.width || 210);
        let h = (this.currentPaperSize === 'custom' && this.customHeightMm) ? this.customHeightMm : (PAPER_DIMENSIONS[this.currentPaperSize]?.height || 297);
        return this.currentOrientation === 'landscape' ? w : h;
    }

    applyPageSetupToSheet(sheet, pageIndex = null) {
        if (!sheet) return;

        sheet.classList.remove('page-a4', 'page-letter', 'page-legal', 'page-folio', 'page-executive', 'page-a3', 'page-a5', 'page-b5', 'page-statement', 'page-tabloid');
        sheet.classList.add(`page-${this.currentPaperSize || 'a4'}`);

        if (this.currentOrientation === 'landscape') {
            sheet.classList.add('page-landscape');
        } else {
            sheet.classList.remove('page-landscape');
        }

        const widthMm = this.getDocumentWidthMm();
        const heightMm = this.getDocumentHeightMm();
        sheet.style.setProperty('width', `${widthMm}mm`, 'important');
        sheet.style.setProperty('min-height', `${heightMm}mm`, 'important');

        // Apply Vertical Page Alignment to page sheet body
        const sheetBody = sheet.querySelector('.page-sheet-body');
        if (sheetBody) {
            if (this.verticalAlign === 'center') {
                sheetBody.style.display = 'flex';
                sheetBody.style.flexDirection = 'column';
                sheetBody.style.justifyContent = 'center';
            } else if (this.verticalAlign === 'bottom') {
                sheetBody.style.display = 'flex';
                sheetBody.style.flexDirection = 'column';
                sheetBody.style.justifyContent = 'flex-end';
            } else if (this.verticalAlign === 'justify') {
                sheetBody.style.display = 'flex';
                sheetBody.style.flexDirection = 'column';
                sheetBody.style.justifyContent = 'space-between';
            } else {
                sheetBody.style.display = '';
                sheetBody.style.flexDirection = '';
                sheetBody.style.justifyContent = '';
            }

            // Apply Columns
            if (this.columns === '2' || this.columns === 2) {
                sheetBody.style.columnCount = '2';
                sheetBody.style.columnGap = '1.25cm';
                sheetBody.style.columnRule = '1px solid #e5e7eb';
            } else if (this.columns === '3' || this.columns === 3) {
                sheetBody.style.columnCount = '3';
                sheetBody.style.columnGap = '1cm';
                sheetBody.style.columnRule = '1px solid #e5e7eb';
            } else if (this.columns === 'left' || this.columns === 'right') {
                sheetBody.style.columnCount = '2';
                sheetBody.style.columnGap = '1.25cm';
            } else {
                sheetBody.style.columnCount = '';
                sheetBody.style.columnGap = '';
                sheetBody.style.columnRule = '';
            }

            // Apply Hyphenation
            if (this.hyphenation === 'auto') {
                sheetBody.style.hyphens = 'auto';
                sheetBody.style.webkitHyphens = 'auto';
            } else {
                sheetBody.style.hyphens = 'none';
                sheetBody.style.webkitHyphens = 'none';
            }

            // Apply Tab Stop
            if (this.defaultTabStop) {
                sheetBody.style.tabSize = `${this.defaultTabStop}cm`;
            }
        }

        this.applyMarginsToSheet(sheet, this.currentMarginPreset, pageIndex);

        // Apply Page Color
        if (this.pageColor && this.pageColor !== 'none' && this.pageColor !== '#ffffff') {
            sheet.style.backgroundColor = this.pageColor;
        } else {
            sheet.style.backgroundColor = '';
        }

        // Apply Watermark
        sheet.querySelectorAll('.page-sheet-watermark').forEach(el => el.remove());
        if (this.watermark && this.watermark.text) {
            const wmDiv = document.createElement('div');
            wmDiv.className = 'page-sheet-watermark select-none pointer-events-none absolute inset-0 flex items-center justify-center overflow-hidden z-0';
            const rot = this.watermark.layout === 'horizontal' ? '0deg' : '-45deg';
            const opacity = this.watermark.opacity || 0.25;
            const fontSize = this.watermark.size && this.watermark.size !== 'auto' ? `${this.watermark.size}pt` : '72pt';
            const fontFam = this.watermark.font || "'Times New Roman', serif";
            const color = this.watermark.color || '#9ca3af';
            wmDiv.innerHTML = `<span style="transform: rotate(${rot}); font-size: ${fontSize}; font-family: ${fontFam}; color: ${color}; opacity: ${opacity}; font-weight: bold; text-transform: uppercase; white-space: nowrap; user-select: none;">${this.escapeHtml(this.watermark.text)}</span>`;
            sheet.insertBefore(wmDiv, sheet.firstChild);
        }

        // Apply Page Border
        sheet.querySelectorAll('.page-sheet-border-frame').forEach(el => el.remove());
        if (this.pageBorder && this.pageBorder.setting && this.pageBorder.setting !== 'none') {
            const pIdx = pageIndex || parseInt(sheet.getAttribute('data-page-index') || '1', 10);
            const shouldApply = (this.pageBorder.applyTo === 'document') ||
                (this.pageBorder.applyTo === 'first-page' && pIdx === 1) ||
                (this.pageBorder.applyTo === 'not-first-page' && pIdx > 1);
            if (shouldApply) {
                const borderDiv = document.createElement('div');
                const inset = `${this.pageBorder.insetMm || 15}mm`;
                const bStyle = this.pageBorder.style || 'solid';
                const bWidth = this.pageBorder.width || '1.5pt';
                const bColor = this.pageBorder.color || '#000000';
                const shadow = this.pageBorder.setting === 'shadow' ? 'box-shadow: 4px 4px 0px rgba(0,0,0,0.25);' : '';
                borderDiv.className = 'page-sheet-border-frame pointer-events-none absolute select-none';
                borderDiv.style.cssText = `top: ${inset}; left: ${inset}; right: ${inset}; bottom: ${inset}; border: ${bWidth} ${bStyle} ${bColor}; ${shadow} z-index: 2;`;
                sheet.appendChild(borderDiv);
            }
        }

        // Apply Line Numbers
        if (this.lineNumbers === 'page' || this.lineNumbers === 'continuous') {
            sheet.classList.add('show-line-numbers');
            if (this.lineNumbers === 'continuous') {
                const pIdx = pageIndex || parseInt(sheet.getAttribute('data-page-index') || '1', 10);
                const startOffset = Math.max(0, (pIdx - 1) * 25);
                sheet.style.counterReset = `line-counter ${startOffset}`;
            } else {
                sheet.style.counterReset = 'line-counter 0';
            }
        } else {
            sheet.classList.remove('show-line-numbers');
            sheet.style.counterReset = '';
        }
    }

    applyMarginsToSheet(sheet, preset = null, pageIndex = null) {
        if (!sheet) return;
        const targetPreset = preset || this.currentMarginPreset || 'normal';

        const pNum = pageIndex || parseInt(sheet.getAttribute('data-page-index') || '1', 10);
        const isOdd = pNum % 2 !== 0;

        if (typeof targetPreset === 'object' && targetPreset !== null) {
            const top = parseFloat(targetPreset.top ?? 25.4);
            const bottom = parseFloat(targetPreset.bottom ?? 25.4);
            let left = parseFloat(targetPreset.left ?? 25.4);
            let right = parseFloat(targetPreset.right ?? 25.4);
            const gutter = parseFloat(targetPreset.gutter ?? 0);
            const gutterPos = targetPreset.gutterPos || 'left';

            let topPad = top;
            let leftPad = left;
            if (gutterPos === 'top') {
                topPad += gutter;
            } else {
                leftPad += gutter;
            }

            if (this.multiplePagesMode === 'mirror') {
                sheet.style.paddingTop = `${topPad}mm`;
                sheet.style.paddingBottom = `${bottom}mm`;
                sheet.style.paddingLeft = `${isOdd ? leftPad : right}mm`;
                sheet.style.paddingRight = `${isOdd ? right : leftPad}mm`;
            } else {
                sheet.style.paddingTop = `${topPad}mm`;
                sheet.style.paddingBottom = `${bottom}mm`;
                sheet.style.paddingLeft = `${leftPad}mm`;
                sheet.style.paddingRight = `${right}mm`;
            }
            return;
        }

        if (targetPreset === 'skripsi') {
            // Skripsi standard: Top 4cm, Left 4cm, Right 3cm, Bottom 3cm
            sheet.style.paddingTop = '40mm';
            sheet.style.paddingLeft = '40mm';
            sheet.style.paddingRight = '30mm';
            sheet.style.paddingBottom = '30mm';
        } else if (targetPreset === 'narrow') {
            sheet.style.padding = '12.7mm';
        } else if (targetPreset === 'moderate') {
            sheet.style.paddingTop = '25.4mm';
            sheet.style.paddingBottom = '25.4mm';
            sheet.style.paddingLeft = '19.1mm';
            sheet.style.paddingRight = '19.1mm';
        } else if (targetPreset === 'wide') {
            sheet.style.paddingTop = '25.4mm';
            sheet.style.paddingBottom = '25.4mm';
            sheet.style.paddingLeft = '50.8mm';
            sheet.style.paddingRight = '50.8mm';
        } else if (targetPreset === 'office2003') {
            sheet.style.paddingTop = '25.4mm';
            sheet.style.paddingBottom = '25.4mm';
            sheet.style.paddingLeft = '31.8mm';
            sheet.style.paddingRight = '31.8mm';
        } else if (targetPreset === 'mirrored') {
            sheet.style.paddingTop = '25.4mm';
            sheet.style.paddingBottom = '25.4mm';
            sheet.style.paddingLeft = `${isOdd ? 31.8 : 25.4}mm`;
            sheet.style.paddingRight = `${isOdd ? 25.4 : 31.8}mm`;
        } else if (targetPreset === 'custom' && this.currentMargins) {
            this.applyMarginsToSheet(sheet, this.currentMargins, pageIndex);
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
        let importedFont = null;

        try {
            if (['docx', 'doc'].includes(extension)) {
                detectedFormat = 'docx';
                const arrayBuffer = await file.arrayBuffer();

                try {
                    // Try high-fidelity OpenXML parser first
                    const parsed = await DocxImporter.parse(arrayBuffer, filename);
                    if (typeof parsed === 'object' && parsed !== null && parsed.html !== undefined) {
                        contentHtml = parsed.html;
                        this.customHeaderText = parsed.headerText || '';
                        this.customFooterText = parsed.footerText || '';
                        this.differentFirstPage = Boolean(parsed.differentFirstPage);
                        if (parsed.defaultFontFamily) {
                            importedFont = parsed.defaultFontFamily;
                        }
                        if (parsed.pageSetup) {
                            this.setPageSetup({
                                paperSize: parsed.pageSetup.paperSize,
                                customWidth: parsed.pageSetup.widthMm,
                                customHeight: parsed.pageSetup.heightMm,
                                orientation: parsed.pageSetup.orientation,
                                margins: parsed.pageSetup.margins,
                                marginPreset: parsed.pageSetup.marginPreset,
                            });
                        }
                    } else {
                        contentHtml = String(parsed);
                    }
                } catch (parseErr) {
                    console.warn('DocxImporter error, falling back to server or mammoth:', parseErr);
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
                            "u => u",
                            "strike => s",
                            "r[style-name='Strong'] => strong",
                            "p[style-name^='toc'] => p.toc-entry:fresh",
                            "p[style-name^='TOC'] => p.toc-entry:fresh",
                            "p[style-name='Table of Figures'] => p.toc-entry:fresh",
                            "p[style-name='Daftar Isi'] => p.toc-entry:fresh",
                        ];

                        const transformElement = (element) => {
                            if (element.children) {
                                element.children = element.children.map(transformElement);
                            }
                            if (element.type === 'break' && element.breakType === 'page') {
                                return {
                                    type: 'text',
                                    value: '[[WORD_PAGE_BREAK]]'
                                };
                            }
                            return element;
                        };

                        const result = await window.mammoth.convertToHtml({
                            arrayBuffer,
                            styleMap,
                            transformDocument: transformElement,
                        });
                        contentHtml = result.value
                            .replace(/<p>\s*\[\[WORD_PAGE_BREAK\]\]\s*<\/p>/gi, '<div class="page-break my-8 border-b-2 border-dashed border-gray-400 dark:border-gray-600 text-center py-2 select-none" data-page-break="true"><span class="bg-gray-100 dark:bg-gray-800 px-3 py-1 rounded-full text-xs text-gray-500 font-mono">Pemisah Halaman</span></div>')
                            .replace(/\[\[WORD_PAGE_BREAK\]\]/gi, '<div class="page-break my-8 border-b-2 border-dashed border-gray-400 dark:border-gray-600 text-center py-2 select-none" data-page-break="true"><span class="bg-gray-100 dark:bg-gray-800 px-3 py-1 rounded-full text-xs text-gray-500 font-mono">Pemisah Halaman</span></div>');
                    } else {
                        const backendResult = await this.uploadToBackend(file);
                        contentHtml = backendResult.content_html;
                        this.customHeaderText = backendResult.header_text || '';
                        this.customFooterText = backendResult.footer_text || '';
                        if (backendResult.different_first_page !== undefined) {
                            this.differentFirstPage = Boolean(backendResult.different_first_page);
                        }
                        if (backendResult.default_font_family) {
                            importedFont = backendResult.default_font_family;
                        }
                        if (backendResult.page_setup) {
                            this.setPageSetup({
                                paperSize: backendResult.page_setup.paper_size,
                                customWidth: backendResult.page_setup.width_mm,
                                customHeight: backendResult.page_setup.height_mm,
                                orientation: backendResult.page_setup.orientation,
                                margins: backendResult.page_setup.margins,
                                marginPreset: backendResult.page_setup.margin_preset,
                            });
                        }
                    }
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
                if (data.header_text) this.customHeaderText = data.header_text;
                if (data.footer_text) this.customFooterText = data.footer_text;
                const layout = data.layout || data.layout_options;
                if (layout) {
                    this.setPageSetup({
                        paperSize: layout.paper_size,
                        customWidth: layout.width_mm,
                        customHeight: layout.height_mm,
                        orientation: layout.orientation,
                        margins: layout.margins,
                        marginPreset: layout.margin_preset,
                    });
                    if (layout.line_spacing) {
                        this.setLineSpacing(layout.line_spacing);
                    }
                    if (layout.paragraph_spacing) {
                        this.setParagraphSpacing(layout.paragraph_spacing.before, layout.paragraph_spacing.after);
                    }
                    if (layout.paragraph_indent) {
                        this.setParagraphIndent(layout.paragraph_indent.left, layout.paragraph_indent.right);
                    }
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

            const restoreBtn = document.getElementById('btn-restore-draft');
            if (restoreBtn) restoreBtn.classList.add('hidden');

            // Include header & footer details in notification if detected
            let hfDetail = '';
            if (this.customHeaderText && this.customFooterText) {
                hfDetail = ` (Header: "${this.customHeaderText}" & Footer: "${this.customFooterText}")`;
            } else if (this.customHeaderText) {
                hfDetail = ` (Header: "${this.customHeaderText}")`;
            } else if (this.customFooterText) {
                hfDetail = ` (Footer: "${this.customFooterText}")`;
            }

            // Apply adapted typography and margins
            if (adapted.isAcademic) {
                this.setMarginPreset('skripsi');
                const academicFont = importedFont ? DocxImporter.formatFontFamily(importedFont) : "'Times New Roman', serif";
                this.setFontFamily(academicFont);
                this.setFontSize('12pt');
                this.setLineSpacing('1.5');
                const cleanDisplayFont = (importedFont || 'Times New Roman').replace(/['"]/g, '').split(',')[0].trim();
                this.onAdaptationNotice({
                    type: 'academic',
                    message: `Format Skripsi Terdeteksi: Margin 4-4-3-3 cm, Font ${cleanDisplayFont} 12pt, Spasi 1.5, dan ${adapted.headingCount} Bab/Subbab berhasil dipecah menjadi lembar halaman A4.${hfDetail}`,
                });
            } else {
                this.setMarginPreset('normal');
                if (importedFont) {
                    this.setFontFamily(DocxImporter.formatFontFamily(importedFont));
                }
                this.onAdaptationNotice({
                    type: 'normal',
                    message: `Format ${detectedFormat.toUpperCase()} berhasil dimuat dan diselaraskan ke dalam lembar halaman A4.${hfDetail}`,
                });
            }

            return {
                success: true,
                filename,
                format: detectedFormat,
                isAcademic: adapted.isAcademic,
                message: `Format ${detectedFormat.toUpperCase()} otomatis disesuaikan.${hfDetail}`,
            };
        } catch (err) {
            console.error('Error loading local file:', err);
            // Fallback to backend parser
            try {
                const backendResult = await this.uploadToBackend(file);
                this.customHeaderText = backendResult.header_text || '';
                this.customFooterText = backendResult.footer_text || '';
                if (backendResult.different_first_page !== undefined) {
                    this.differentFirstPage = Boolean(backendResult.different_first_page);
                }
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

        // Deduplicate existing TOC blocks if any exist
        const existingTocs = container.querySelectorAll('.table-of-contents, [data-toc-block="true"]');
        if (existingTocs.length > 1) {
            for (let i = 1; i < existingTocs.length; i++) {
                existingTocs[i].remove();
            }
        }
        let hasCreatedToc = existingTocs.length > 0;

        const isMetadataOrSignatureLine = (str) => {
            if (!str) return false;
            const clean = str.trim();
            if (!clean) return false;
            // Student & employee identification numbers
            if (/\b(NIM|NPM|NIS|NIK|NIP|NIDN|NRK)\b/i.test(clean)) return true;
            // Academic and professional degrees / honorifics
            if (/\b(S\.T\.|M\.T\.|S\.Kom\.|M\.Kom\.|Ph\.D\.|Dr\.|Prof\.|Ir\.|M\.Sc\.|B\.Sc\.|M\.Eng\.|S\.Pd\.|M\.Pd\.|S\.Si\.|M\.Si\.|S\.E\.|M\.M\.)\b/i.test(clean)) return true;
            // Approval, signature, and committee titles
            if (/^(OLEH|DISUSUN\s+OLEH|BY|PEMBIMBING|PENGUJI|DEWAN\s+PENGUJI|TIM\s+PENGUJI|KETUA|SEKRETARIS|ANGGOTA|DEKAN|DIREKTUR|WAKIL\s+DEKAN|WAKIL\s+DIREKTUR|KOORDINATOR|MENGETAHUI|MENYETUJUI|DISAHKAN|DOSEN|JURUSAN|PROGRAM\s+STUDI|FAKULTAS|POLITEKNIK|UNIVERSITAS|KEMENTERIAN|TANGGAL\s+LULUS|TANGGAL\s+SEMINAR)\b/i.test(clean)) return true;
            // Signature lines with dots or underscores
            if (/^[._\-\s]{4,}$/.test(clean)) return true;
            return false;
        };

        const isTableOfContentsLine = (str) => {
            if (!str) return false;
            const clean = str.trim().replace(/\s+/g, ' ');
            if (!clean || clean.length < 4 || clean.length > 140) return false;
            if (isMetadataOrSignatureLine(clean)) return false;
            if (/^(NIM|NPM|NIS|NIK|OLEH|DISUSUN|BY|PROGRAM\s+STUDI|JURUSAN|FAKULTAS|POLITEKNIK|UNIVERSITAS|DEPARTMENT|STUDY\s+PROGRAM|SEKOLAH\s+TINGGI|INSTITUT|SEBAGAI\s+SALAH\s+SATU\s+SYARAT|SUBMITTED\s+AS)\b/i.test(clean)) {
                return false;
            }
            return /^(.{2,100}?)(?:[\s.·…]{3,}|\t+)([ivxlcdm\d]{1,5})$/i.test(clean);
        };

        const allText = container.textContent.toLowerCase();
        const checkFilename = filename.toLowerCase();
        const isDocx = checkFilename.endsWith('.docx') || checkFilename.endsWith('.doc');

        const isAcademic =
            checkFilename.includes('skripsi') ||
            checkFilename.includes('tesis') ||
            checkFilename.includes('thesis') ||
            checkFilename.includes('disertasi') ||
            checkFilename.includes('tugas-akhir') ||
            checkFilename.includes('tugas_akhir') ||
            (/bab\s*(?:\d+|[ivxlcdm]+)/i.test(allText) && (
                allText.includes('skripsi') ||
                allText.includes('tesis') ||
                allText.includes('thesis') ||
                allText.includes('disertasi') ||
                allText.includes('lembar pengesahan') ||
                allText.includes('pernyataan keaslian') ||
                allText.includes('nim.')
            ));

        let inCover = false;
        if (isAcademic) {
            const firstFew = allText.slice(0, 1500);
            if (
                firstFew.includes('skripsi') ||
                firstFew.includes('tesis') ||
                firstFew.includes('thesis') ||
                firstFew.includes('disusun') ||
                firstFew.includes('oleh :') ||
                firstFew.includes('oleh:') ||
                firstFew.includes('nim')
            ) {
                inCover = true;
            }
        }
        let coverCount = 0;
        let headingCount = 0;
        let consecutiveEmpty = 0;

        const getCleanText = (node) => {
            if (!node) return '';
            if (typeof node === 'string') return node.trim();
            const clone = node.cloneNode(true);
            clone.querySelectorAll('br').forEach(br => br.replaceWith(' '));
            return clone.textContent.trim().replace(/\s+/g, ' ');
        };

        const children = Array.from(container.children);
        for (let el of children) {
            if (!container.contains(el)) continue;

            if (el.classList?.contains('table-of-contents') && hasCreatedToc) {
                el.remove();
                continue;
            }

            // 1. Explicit Page Break: Preserve it immediately and continue
            if (
                el.classList?.contains('page-break') ||
                el.hasAttribute('data-page-break') ||
                el.getAttribute('style')?.includes('page-break-after: always') ||
                el.getAttribute('style')?.includes('break-after: page') ||
                el.textContent.trim() === 'Pemisah Halaman' ||
                el.textContent.trim() === 'Pemisah Halaman (Akhir Sampul)'
            ) {
                el.className = 'page-break my-8 border-b-2 border-dashed border-gray-400 dark:border-gray-600 text-center py-2 select-none';
                el.setAttribute('data-page-break', 'true');
                el.innerHTML = '<span class="bg-gray-100 dark:bg-gray-800 px-3 py-1 rounded-full text-xs text-gray-500 font-mono">Pemisah Halaman</span>';
                continue;
            }

            const cleanText = getCleanText(el);

            // Collapse excessive empty paragraphs
            if (cleanText === '' && !el.querySelector('img')) {
                consecutiveEmpty++;
                if (consecutiveEmpty > 1) {
                    el.remove();
                    continue;
                }
            } else {
                consecutiveEmpty = 0;
            }

            const isChapter = /^(BAB\s*(?:\d+|[IVXLCDM]+))\b/i.test(cleanText);
            const isPreliminary = /^(ABSTRAK|ABSTRACT|KATA\s+PENGANTAR|PRAKATA|LEMBAR\s+PENGESAHAN|HALAMAN\s+PENGESAHAN|LEMBAR\s+PERSETUJUAN|HALAMAN\s+PERSETUJUAN|PERNYATAAN\s+KEASLIAN|PERNYATAAN\s+ORISINALITAS|DAFTAR\s+ISI|DAFTAR\s+TABEL|DAFTAR\s+GAMBAR|DAFTAR\s+LAMPIRAN|DAFTAR\s+PUSTAKA|RIWAYAT\s+HIDUP|BIODATA\s+PENULIS|LAMPIRAN)(\s+.*)?$/i.test(cleanText);

            // End cover page when next section begins
            if (inCover && (isChapter || isPreliminary)) {
                inCover = false;
            }

            // Cover Page centering & typography
            if (inCover) {
                coverCount++;
                const img = el.querySelector('img') || (el.tagName === 'IMG' ? el : null);
                if (img) {
                    img.className = 'cover-logo mx-auto block my-6 max-h-40 object-contain';
                    el.className = 'text-center my-4';
                } else if (cleanText) {
                    if (/^(OLEH|DISUSUN\s+OLEH|BY)\s*:/i.test(cleanText)) {
                        el.className = 'cover-author-label text-center text-sm font-medium text-gray-700 my-2 leading-relaxed';
                    } else if (/^(?:NIM\b|\d{7,12}$)/i.test(cleanText)) {
                        el.className = 'cover-author-nim text-center font-bold text-base text-gray-900 my-1 leading-relaxed';
                    } else if (/^(PROGRAM\s+STUDI|JURUSAN|FAKULTAS|POLITEKNIK|UNIVERSITAS|DEPARTMENT|STUDY\s+PROGRAM)/i.test(cleanText)) {
                        el.className = 'cover-institution text-center font-bold text-sm uppercase text-gray-900 my-1 leading-relaxed';
                    } else if (/^(Sebagai salah satu syarat|Submitted as a partial)/i.test(cleanText)) {
                        el.className = 'cover-subtitle text-center text-sm italic text-gray-700 my-3 max-w-xl mx-auto leading-relaxed';
                    } else if (/^(SKRIPSI|THESIS|TESIS|DISERTASI|PROPOSAL|LAPORAN|TUGAS\s+AKHIR)$/i.test(cleanText)) {
                        el.className = 'cover-badge text-center font-bold text-xl uppercase tracking-widest text-gray-900 my-2';
                    } else {
                        el.className = 'cover-title text-center font-bold text-gray-900 my-2 leading-snug';
                    }
                }
                continue;
            }

            // Preserve existing heading elements from OpenXML or markdown parser
            if (/^H[1-6]$/i.test(el.tagName)) {
                headingCount++;
                const lvl = parseInt(el.tagName.replace(/h/i, ''), 10);
                if (!el.className) {
                    el.className = lvl === 1
                        ? 'heading-chapter font-bold text-xl my-6 text-gray-900 uppercase'
                        : (lvl === 2 ? 'heading-section font-bold text-lg my-4 text-gray-900' : 'heading-subsection font-semibold text-base my-3 text-gray-800');
                }
                continue;
            }

            // Table of Contents entries: Group contiguous TOC lines into a single seamless table-of-contents block
            if ((isTableOfContentsLine(cleanText) || el.classList?.contains('toc-entry')) && !hasCreatedToc) {
                let prevEl = el.previousElementSibling;
                let tocHeading = 'DAFTAR ISI';
                let hasExplicitTocHeading = false;
                if (prevEl && /^(DAFTAR\s+ISI|TABLE\s+OF\s+CONTENTS|KONTEN)$/i.test(getCleanText(prevEl))) {
                    tocHeading = getCleanText(prevEl).toUpperCase();
                    hasExplicitTocHeading = true;
                }

                // Check how many contiguous entries follow
                let entryCount = 0;
                let testEl = el;
                while (testEl && (isTableOfContentsLine(getCleanText(testEl)) || testEl.classList?.contains('toc-entry'))) {
                    entryCount++;
                    testEl = testEl.nextElementSibling;
                }

                // Only create a TOC block if we have an explicit TOC heading, OR at least 2 entries, OR existing .toc-entry
                if (!hasExplicitTocHeading && entryCount < 2 && !el.classList?.contains('toc-entry')) {
                    continue;
                }

                if (hasExplicitTocHeading && prevEl) {
                    prevEl.remove();
                }

                const tocGroupDiv = doc.createElement('div');
                tocGroupDiv.className = 'table-of-contents w-full my-6 relative group';
                tocGroupDiv.setAttribute('data-toc-block', 'true');
                tocGroupDiv.setAttribute('data-toc-style', 'template');
                tocGroupDiv.setAttribute('data-toc-leader', 'dots');
                tocGroupDiv.setAttribute('contenteditable', 'false');
                tocGroupDiv.setAttribute('tabindex', '0');

                tocGroupDiv.innerHTML = `
                    <div class="toc-toolbar absolute -top-4 right-0 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1 rounded-md shadow-lg flex items-center gap-2 text-xs opacity-0 group-hover:opacity-100 transition duration-150 z-30 no-print select-none" contenteditable="false">
                        <button type="button" class="btn-toc-update hover:text-indigo-400 dark:hover:text-indigo-600 font-semibold flex items-center gap-1 cursor-pointer" title="Perbarui Daftar Isi (Update Table)">
                            <span>🔄 Perbarui Tabel...</span>
                        </button>
                        <span class="w-px h-3 bg-gray-700 dark:bg-gray-300"></span>
                        <button type="button" class="btn-toc-customize hover:text-indigo-400 dark:hover:text-indigo-600 flex items-center gap-1 cursor-pointer" title="Kustomisasi Format Daftar Isi">
                            <span>⚙️ Format</span>
                        </button>
                        <span class="w-px h-3 bg-gray-700 dark:bg-gray-300"></span>
                        <button type="button" class="btn-toc-delete hover:text-red-400 text-red-300 dark:text-red-600 cursor-pointer" title="Hapus Daftar Isi dari Dokumen">
                            <span>🗑️ Hapus</span>
                        </button>
                    </div>
                    <h1 class="toc-header heading-chapter text-center font-bold text-xl mb-6 uppercase tracking-wider text-gray-900 dark:text-white font-serif outline-none" contenteditable="true">${this.escapeHtml(tocHeading)}</h1>
                    <div class="toc-entries space-y-1 font-serif" contenteditable="true"></div>
                `;

                const entriesContainer = tocGroupDiv.querySelector('.toc-entries');
                let currEl = el;

                while (currEl && (isTableOfContentsLine(getCleanText(currEl)) || currEl.classList?.contains('toc-entry'))) {
                    const lineText = getCleanText(currEl);
                    const match = lineText.match(/^(.{2,100}?)(?:[\s.·…]{2,}|\t+)([ivxlcdm\d]+)$/i);
                    let itemTitle = lineText.replace(/[\s.·…]+$/, '').trim();
                    let itemPage = '1';

                    if (match) {
                        itemTitle = match[1].trim();
                        itemPage = match[2].trim();
                    }

                    // Determine level based on numbering
                    let lvl = 1;
                    if (/^\d+\.\d+\.\d+\s+/.test(itemTitle)) lvl = 3;
                    else if (/^\d+\.\d+\s+/.test(itemTitle)) lvl = 2;
                    else if (/^BAB\b|ABSTRAK|ABSTRACT|KATA\s+PENGANTAR|DAFTAR\s+TABEL|DAFTAR\s+GAMBAR|DAFTAR\s+PUSTAKA/i.test(itemTitle)) lvl = 1;

                    let levelIndent = 'pl-0 font-bold text-gray-900 dark:text-gray-100 uppercase tracking-wide';
                    if (lvl === 2) levelIndent = 'pl-6 font-medium text-gray-800 dark:text-gray-200 text-xs sm:text-sm';
                    else if (lvl >= 3) levelIndent = 'pl-12 text-gray-700 dark:text-gray-300 text-xs';

                    const cleanSlug = itemTitle.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '').substring(0, 30);
                    const targetId = `heading-${cleanSlug || 'sec'}`;

                    const itemDiv = doc.createElement('div');
                    itemDiv.className = `toc-entry toc-item flex items-baseline justify-between py-1 ${levelIndent}`;
                    itemDiv.setAttribute('data-level', String(lvl));
                    itemDiv.setAttribute('data-target', targetId);
                    itemDiv.innerHTML = `
                        <a href="#${targetId}" class="toc-link hover:text-indigo-600 dark:hover:text-indigo-400 hover:underline truncate max-w-[80%]" contenteditable="true">${this.escapeHtml(itemTitle)}</a>
                        <span class="toc-leader flex-1 mx-2 border-b border-dotted border-gray-400 dark:border-gray-500 select-none" contenteditable="false"></span>
                        <span class="toc-page font-mono text-sm font-semibold text-gray-800 dark:text-gray-200 shrink-0 text-right min-w-[24px]" contenteditable="true">${this.escapeHtml(itemPage)}</span>
                    `;

                    entriesContainer.appendChild(itemDiv);

                    const next = currEl.nextElementSibling;
                    currEl.remove();
                    currEl = next;
                }

                if (currEl) {
                    currEl.before(tocGroupDiv);
                } else {
                    container.appendChild(tocGroupDiv);
                }

                hasCreatedToc = true;
                continue;
            }

            // Formula / Math: LaTeX Display Equations ($$...$$ or \[...\])
            const mathMatch = cleanText.match(/^(?:\$\$|\\\[)([\s\S]+?)(?:\$\$|\\\])$/);
            if (mathMatch) {
                const formula = mathMatch[1].trim();
                if (formula) {
                    const eqDiv = doc.createElement('div');
                    eqDiv.className = 'latex-equation display-equation my-4 text-center cursor-pointer select-none';
                    eqDiv.setAttribute('contenteditable', 'false');
                    eqDiv.setAttribute('data-latex', formula);
                    eqDiv.setAttribute('data-display', 'true');
                    eqDiv.setAttribute('title', 'Klik dua kali untuk mengedit rumus LaTeX');

                    const wrap = doc.createElement('div');
                    wrap.className = 'latex-math-wrapper inline-block px-3 py-1.5 rounded transition';
                    wrap.innerHTML = DocumentEngine.renderLatexHtml(formula, true);
                    eqDiv.appendChild(wrap);

                    el.replaceWith(eqDiv);
                    continue;
                }
            }

            // Chapter Headings: BAB I, BAB II, BAB 1, etc.
            const babMatch = cleanText.match(/^(BAB\s*(?:\d+|[IVXLCDM]+))\s*(.*)$/i);
            if (!isTableOfContentsLine(cleanText) && babMatch && cleanText.length <= 80 && !/[.!?]$/.test(cleanText)) {
                headingCount++;
                let titleText = `${babMatch[1]} ${babMatch[2]}`.trim();
                // Normalize BAB 1PENDAHULUAN -> BAB 1 PENDAHULUAN
                titleText = titleText.replace(/^(BAB\s*(?:\d+|[IVXLCDM]+))\s*([A-Za-z])/i, '$1 $2');
                const nextEl = el.nextElementSibling;
                if ((!babMatch[2] || babMatch[2].trim() === '') && nextEl) {
                    const nextClean = getCleanText(nextEl);
                    if (nextClean && nextClean.length < 100 && !/^\d+\.\d+/.test(nextClean)) {
                        titleText = `${titleText} ${nextClean}`;
                        nextEl.remove();
                    }
                }

                const h1 = doc.createElement('h1');
                h1.className = 'heading-chapter text-center font-bold text-xl my-6 text-gray-900 uppercase';
                h1.textContent = titleText;
                const elStyle = el.getAttribute('style');
                if (elStyle) {
                    h1.setAttribute('style', elStyle);
                }

                const prevEl = el.previousElementSibling;
                const hasPageBreakBefore = prevEl && (prevEl.classList?.contains('page-break') || prevEl.hasAttribute('data-page-break'));
                if (!hasPageBreakBefore) {
                    const pb = doc.createElement('div');
                    pb.className = 'page-break my-6 border-b border-dashed border-gray-300 dark:border-gray-700 text-center py-1 select-none';
                    pb.setAttribute('data-page-break', 'true');
                    pb.innerHTML = '<span class="text-[10px] text-gray-400 font-mono">Awal Bab Baru</span>';
                    el.before(pb);
                }

                el.replaceWith(h1);
                continue;
            }

            // Academic Preliminaries: ABSTRAK, DAFTAR ISI, LEMBAR PENGESAHAN, etc.
            if (
                !isTableOfContentsLine(cleanText) &&
                !isMetadataOrSignatureLine(cleanText) &&
                isPreliminary &&
                cleanText.length <= 60 &&
                !/[.!?]$/.test(cleanText)
            ) {
                headingCount++;
                const h1 = doc.createElement('h1');
                h1.className = 'heading-preliminary text-center font-bold text-xl my-6 text-gray-900 uppercase';
                h1.textContent = cleanText.toUpperCase();
                const elStyle = el.getAttribute('style');
                if (elStyle) {
                    h1.setAttribute('style', elStyle);
                }

                const prevEl = el.previousElementSibling;
                const hasPageBreakBefore = prevEl && (prevEl.classList?.contains('page-break') || prevEl.hasAttribute('data-page-break'));
                if (!hasPageBreakBefore) {
                    const pb = doc.createElement('div');
                    pb.className = 'page-break my-6 border-b border-dashed border-gray-300 dark:border-gray-700 text-center py-1 select-none';
                    pb.setAttribute('data-page-break', 'true');
                    pb.innerHTML = '<span class="text-[10px] text-gray-400 font-mono">Halaman Baru</span>';
                    el.before(pb);
                }

                el.replaceWith(h1);
                continue;
            }

            // Subsections: 1.1 Latar Belakang, 2.1 Teori
            if (!isTableOfContentsLine(cleanText) && /^(\d+\.\d+)\s+(.+)$/.test(cleanText)) {
                headingCount++;
                const h2 = doc.createElement('h2');
                h2.className = 'heading-section font-bold text-lg my-4 text-gray-900';
                h2.innerHTML = el.innerHTML;
                const elStyle = el.getAttribute('style');
                if (elStyle) {
                    h2.setAttribute('style', elStyle);
                }
                el.replaceWith(h2);
                continue;
            }

            // Sub-subsections: 1.1.1, 2.3.1
            if (!isTableOfContentsLine(cleanText) && /^(\d+\.\d+\.\d+)\s+(.+)$/.test(cleanText)) {
                headingCount++;
                const h3 = doc.createElement('h3');
                h3.className = 'heading-subsection font-semibold text-base my-3 text-gray-800';
                h3.innerHTML = el.innerHTML;
                const elStyle = el.getAttribute('style');
                if (elStyle) {
                    h3.setAttribute('style', elStyle);
                }
                el.replaceWith(h3);
                continue;
            }

            // Standalone short uppercase lines (only for non-DOCX formats; DOCX headings are already specified by styles)
            if (
                !isDocx &&
                !inCover &&
                !isTableOfContentsLine(cleanText) &&
                !isMetadataOrSignatureLine(cleanText) &&
                cleanText.length > 5 &&
                cleanText.length < 60 &&
                cleanText === cleanText.toUpperCase() &&
                /[a-zA-Z]{3,}/.test(cleanText) &&
                !/^[\d\s.,:%–=+\/-]+$/.test(cleanText) &&
                !/[.,;!?]$/.test(cleanText) &&
                !el.querySelector('img')
            ) {
                headingCount++;
                const h2 = doc.createElement('h2');
                h2.className = 'heading-section font-bold text-lg my-4 text-center text-gray-900 uppercase';
                h2.innerHTML = cleanText;
                const elStyle = el.getAttribute('style');
                if (elStyle) {
                    h2.setAttribute('style', elStyle);
                }
                el.replaceWith(h2);
                continue;
            }

            // Table styling
            if (el.tagName === 'TABLE') {
                const isBorderless = el.classList?.contains('border-none') || el.getAttribute('border') === '0' || (!el.querySelector('td.border, th.border') && el.classList.contains('border-none'));
                if (isBorderless) {
                    el.className = 'w-full border-collapse my-4 text-sm border-none';
                    el.querySelectorAll('th, td').forEach(cell => {
                        cell.classList.remove('border', 'border-gray-300', 'dark:border-gray-700');
                        cell.classList.add('p-2');
                    });
                } else {
                    el.className = 'w-full border-collapse my-4 text-sm border border-gray-300 dark:border-gray-700';
                    el.querySelectorAll('th, td').forEach(cell => {
                        cell.classList.add('border', 'border-gray-300', 'dark:border-gray-700', 'p-2');
                    });
                    el.querySelectorAll('th').forEach(th => {
                        th.classList.add('bg-gray-100', 'dark:bg-gray-800', 'font-semibold');
                    });
                }
                continue;
            }

            // Normal Body Paragraphs
            if (el.tagName === 'P') {
                if (el.innerHTML.includes('$$') || el.innerHTML.includes('\\[')) {
                    el.innerHTML = this.formatLatexEquationsInHtml(el.innerHTML);
                }

                const hasCenter = el.classList.contains('text-center') || el.style.textAlign === 'center';
                const hasRight = el.classList.contains('text-right') || el.style.textAlign === 'right';
                const hasJustify = el.classList.contains('text-justify') || el.style.textAlign === 'justify';
                const hasLeft = el.classList.contains('text-left') || el.style.textAlign === 'left';

                if (hasCenter) {
                    el.classList.add('text-center', 'my-2', 'leading-relaxed', 'text-gray-800');
                } else if (hasRight) {
                    el.classList.add('text-right', 'my-2', 'leading-relaxed', 'text-gray-800');
                } else if (hasJustify) {
                    const indent = (isAcademic && cleanText.length > 100) ? 'indent-8' : '';
                    el.classList.add('text-justify', 'my-3', 'leading-relaxed', 'text-gray-800');
                    if (indent) el.classList.add(indent);
                } else if (hasLeft) {
                    el.classList.add('text-left', 'my-2', 'leading-relaxed', 'text-gray-800');
                } else {
                    if (isAcademic && cleanText.length > 100) {
                        el.classList.add('text-justify', 'my-3', 'leading-relaxed', 'text-gray-800', 'indent-8');
                    } else {
                        el.classList.add('my-2', 'leading-relaxed', 'text-gray-800');
                    }
                }
                continue;
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
     * Microsoft Word Page Setup & Margins Suite
     * ------------------------------------------------------------- */

    setMarginPreset(preset) {
        this.currentMarginPreset = preset;
        if (MARGIN_PRESETS[preset]) {
            const p = MARGIN_PRESETS[preset];
            this.currentMargins = {
                top: p.top,
                bottom: p.bottom,
                left: p.left || p.inside || 25.4,
                right: p.right || p.outside || 25.4,
                gutter: p.gutter || 0,
                gutterPos: p.gutterPos || 'left',
            };
            if (preset === 'mirrored') {
                this.multiplePagesMode = 'mirror';
            } else if (this.multiplePagesMode === 'mirror') {
                this.multiplePagesMode = 'normal';
            }
        }
        const sheets = this.getAllPageSheets();
        sheets.forEach((sheet, idx) => this.applyPageSetupToSheet(sheet, idx + 1));
        this.updateRulerForPageSetup();

        const marginSelect = document.getElementById('layout-margins');
        if (marginSelect) {
            marginSelect.value = preset;
        }
        this.handleInput();
    }

    setCustomMargins(margins = {}) {
        this.currentMarginPreset = 'custom';
        this.currentMargins = {
            top: parseFloat(margins.top ?? 25.4),
            bottom: parseFloat(margins.bottom ?? 25.4),
            left: parseFloat(margins.left ?? 25.4),
            right: parseFloat(margins.right ?? 25.4),
            gutter: parseFloat(margins.gutter ?? 0),
            gutterPos: margins.gutterPos || 'left',
        };
        if (margins.multiplePages) {
            this.multiplePagesMode = margins.multiplePages;
        }
        const sheets = this.getAllPageSheets();
        sheets.forEach((sheet, idx) => this.applyPageSetupToSheet(sheet, idx + 1));
        this.updateRulerForPageSetup();

        const marginSelect = document.getElementById('layout-margins');
        if (marginSelect) {
            marginSelect.value = 'custom';
        }
        this.handleInput();
    }

    setPaperSize(sizeName, customWidth = null, customHeight = null) {
        this.currentPaperSize = sizeName;
        if (sizeName === 'custom' && customWidth && customHeight) {
            this.customWidthMm = parseFloat(customWidth);
            this.customHeightMm = parseFloat(customHeight);
        }
        const sheets = this.getAllPageSheets();
        sheets.forEach((sheet, idx) => this.applyPageSetupToSheet(sheet, idx + 1));
        this.updateRulerForPageSetup();

        const paperSelect = document.getElementById('layout-paper-size');
        if (paperSelect) {
            paperSelect.value = sizeName;
        }
        this.handleInput();
    }

    setOrientation(orientation) {
        this.currentOrientation = orientation === 'landscape' ? 'landscape' : 'portrait';
        const sheets = this.getAllPageSheets();
        sheets.forEach((sheet, idx) => this.applyPageSetupToSheet(sheet, idx + 1));
        this.updateRulerForPageSetup();

        const orientSelect = document.getElementById('layout-orientation');
        if (orientSelect) {
            orientSelect.value = this.currentOrientation;
        }
        this.handleInput();
    }

    setPageSetup(options = {}) {
        if (options.paperSize) this.currentPaperSize = options.paperSize;
        if (options.customWidth) this.customWidthMm = parseFloat(options.customWidth);
        if (options.customHeight) this.customHeightMm = parseFloat(options.customHeight);
        if (options.orientation) this.currentOrientation = options.orientation;
        if (options.multiplePages) this.multiplePagesMode = options.multiplePages;
        if (options.sectionStart) this.sectionStart = options.sectionStart;
        if (options.diffOddEven !== undefined) this.differentOddEven = Boolean(options.diffOddEven);
        if (options.diffFirstPage !== undefined) this.differentFirstPage = Boolean(options.diffFirstPage);
        if (options.edgeHeader !== undefined) this.edgeHeader = parseFloat(options.edgeHeader);
        if (options.edgeFooter !== undefined) this.edgeFooter = parseFloat(options.edgeFooter);
        if (options.verticalAlign) this.verticalAlign = options.verticalAlign;
        if (options.columns !== undefined) this.columns = options.columns;
        if (options.hyphenation !== undefined) this.hyphenation = options.hyphenation;
        if (options.tabStops !== undefined) this.tabStops = options.tabStops;
        if (options.defaultTabStop !== undefined) this.defaultTabStop = parseFloat(options.defaultTabStop);
        if (options.watermark !== undefined) this.watermark = options.watermark;
        if (options.pageColor !== undefined) this.pageColor = options.pageColor;
        if (options.pageBorder !== undefined) this.pageBorder = options.pageBorder;
        if (options.lineNumbers !== undefined) this.lineNumbers = options.lineNumbers;

        if (options.margins) {
            this.setCustomMargins(options.margins);
        } else if (options.marginPreset) {
            this.setMarginPreset(options.marginPreset);
        } else {
            const sheets = this.getAllPageSheets();
            sheets.forEach((sheet, idx) => this.applyPageSetupToSheet(sheet, idx + 1));
            this.updateRulerForPageSetup();
        }

        if (options.scope === 'current') {
            const activeSheet = this.getActiveEditor()?.closest('.document-page-sheet') || this.canvasEl;
            if (activeSheet) {
                if (options.orientation === 'landscape') {
                    activeSheet.setAttribute('data-orientation', 'landscape');
                    const w = this.getDocumentHeightMm();
                    const h = this.getDocumentWidthMm();
                    activeSheet.style.setProperty('width', `${Math.max(w, h)}mm`, 'important');
                    activeSheet.style.setProperty('min-height', `${Math.min(w, h)}mm`, 'important');
                } else if (options.orientation === 'portrait') {
                    activeSheet.setAttribute('data-orientation', 'portrait');
                    const w = this.getDocumentWidthMm();
                    const h = this.getDocumentHeightMm();
                    activeSheet.style.setProperty('width', `${Math.min(w, h)}mm`, 'important');
                    activeSheet.style.setProperty('min-height', `${Math.max(w, h)}mm`, 'important');
                }
                if (options.margins) {
                    this.applyMarginsToSheet(activeSheet, options.margins);
                } else if (options.marginPreset) {
                    this.applyMarginsToSheet(activeSheet, options.marginPreset);
                }
            }
        } else if (options.scope === 'forward') {
            const sheets = this.getAllPageSheets();
            const activeSheet = this.getActiveEditor()?.closest('.document-page-sheet') || this.canvasEl;
            const activeIdx = sheets.indexOf(activeSheet);
            const startIdx = activeIdx >= 0 ? activeIdx : 0;
            for (let i = startIdx; i < sheets.length; i++) {
                const sheet = sheets[i];
                if (options.orientation === 'landscape') {
                    sheet.setAttribute('data-orientation', 'landscape');
                    const w = this.getDocumentHeightMm();
                    const h = this.getDocumentWidthMm();
                    sheet.style.setProperty('width', `${Math.max(w, h)}mm`, 'important');
                    sheet.style.setProperty('min-height', `${Math.min(w, h)}mm`, 'important');
                } else if (options.orientation === 'portrait') {
                    sheet.setAttribute('data-orientation', 'portrait');
                    const w = this.getDocumentWidthMm();
                    const h = this.getDocumentHeightMm();
                    sheet.style.setProperty('width', `${Math.min(w, h)}mm`, 'important');
                    sheet.style.setProperty('min-height', `${Math.max(w, h)}mm`, 'important');
                }
                if (options.margins) {
                    this.applyMarginsToSheet(sheet, options.margins, i + 1);
                } else if (options.marginPreset) {
                    this.applyMarginsToSheet(sheet, options.marginPreset, i + 1);
                }
                this.applyPageSetupToSheet(sheet, i + 1);
            }
        }

        const psSelect = document.getElementById('layout-paper-size');
        if (psSelect && this.currentPaperSize) psSelect.value = this.currentPaperSize;

        const orSelect = document.getElementById('layout-orientation');
        if (orSelect && this.currentOrientation) orSelect.value = this.currentOrientation;

        this.handleInput();
    }

    updateRulerForPageSetup() {
        const widthMm = this.getDocumentWidthMm();
        const totalWidthCm = widthMm / 10;

        const rulerTicks = document.getElementById('ruler-ticks');
        if (rulerTicks) {
            rulerTicks.style.width = `${widthMm}mm`;
            if (window.uiController && typeof window.uiController.renderRuler === 'function') {
                window.uiController.renderRuler(totalWidthCm);
            }
        }

        let leftMarginCm = 2.54;
        let rightMarginCm = 2.54;
        if (this.currentMarginPreset === 'skripsi') {
            leftMarginCm = 4.0;
            rightMarginCm = 3.0;
        } else if (this.currentMarginPreset === 'narrow') {
            leftMarginCm = 1.27;
            rightMarginCm = 1.27;
        } else if (this.currentMarginPreset === 'moderate') {
            leftMarginCm = 1.91;
            rightMarginCm = 1.91;
        } else if (this.currentMarginPreset === 'wide') {
            leftMarginCm = 5.08;
            rightMarginCm = 5.08;
        } else if (this.currentMarginPreset === 'mirrored') {
            leftMarginCm = 3.18;
            rightMarginCm = 2.54;
        } else if (this.currentMargins) {
            const gutter = (this.currentMargins.gutterPos === 'left' ? (this.currentMargins.gutter || 0) : 0);
            leftMarginCm = (this.currentMargins.left + gutter) / 10;
            rightMarginCm = (this.currentMargins.right) / 10;
        }

        this.updateRulerMarkers(leftMarginCm, totalWidthCm - rightMarginCm, totalWidthCm);
        this.updateVerticalRuler();
    }

    updateRulerMarkersForPreset(preset) {
        this.updateRulerForPageSetup();
    }

    updateVerticalRuler() {
        const vRuler = document.getElementById('vertical-ruler');
        const vTicks = document.getElementById('vertical-ruler-ticks');
        const vTopShade = document.getElementById('v-ruler-top-shade');
        const vBottomShade = document.getElementById('v-ruler-bottom-shade');
        const vTopMargin = document.getElementById('v-ruler-top-margin');
        const vBottomMargin = document.getElementById('v-ruler-bottom-margin');

        if (!vRuler || !vTicks) return;

        const heightMm = this.getDocumentHeightMm();
        const heightCm = heightMm / 10;
        vRuler.style.height = `${heightMm}mm`;

        const topCm = ((this.currentMargins && this.currentMargins.top) || 25.4) / 10;
        const bottomCm = ((this.currentMargins && this.currentMargins.bottom) || 25.4) / 10;

        const topPct = (topCm / heightCm) * 100;
        const bottomPct = (bottomCm / heightCm) * 100;

        if (vTopShade) vTopShade.style.height = `${topPct}%`;
        if (vBottomShade) vBottomShade.style.height = `${bottomPct}%`;
        if (vTopMargin) vTopMargin.style.top = `${topPct}%`;
        if (vBottomMargin) vBottomMargin.style.bottom = `${bottomPct}%`;

        vTicks.innerHTML = '';
        const totalCm = Math.max(1, Math.round(heightCm));
        for (let cm = 0; cm <= totalCm; cm++) {
            const tick = document.createElement('div');
            tick.className = 'flex-1 border-b border-gray-300 dark:border-gray-700 w-full relative';
            tick.innerHTML = `
                <span class="absolute left-0.5 bottom-0.5 text-[8px] text-gray-400 font-mono select-none">${cm}</span>
                <div class="absolute right-0 bottom-1/2 h-px w-1 bg-gray-200 dark:bg-gray-800"></div>
            `;
            vTicks.appendChild(tick);
        }
    }

    updateRulerMarkers(leftCm, rightCm, totalCm = null) {
        const rulerContainer = document.getElementById('ruler-container');
        const leftShade = document.getElementById('ruler-left-shade');
        const rightShade = document.getElementById('ruler-right-shade');
        const leftMarker = document.getElementById('ruler-left-margin');
        const rightMarker = document.getElementById('ruler-right-margin');
        const firstLineMarker = document.getElementById('ruler-marker-first-line');
        const leftIndentMarker = document.getElementById('ruler-marker-left-indent');
        const rightIndentMarker = document.getElementById('ruler-marker-right-indent');

        const total = totalCm || (this.getDocumentWidthMm() / 10) || 21;
        const totalMm = total * 10;

        if (rulerContainer) {
            rulerContainer.style.width = `${totalMm}mm`;
        }

        const leftPct = (leftCm / total) * 100;
        const rightPct = (rightCm / total) * 100;
        const rightMarginWidthPct = ((total - rightCm) / total) * 100;

        if (leftShade) {
            leftShade.style.width = `${leftPct}%`;
        }
        if (rightShade) {
            rightShade.style.width = `${rightMarginWidthPct}%`;
        }

        if (leftMarker) {
            leftMarker.style.left = `${leftPct}%`;
            leftMarker.title = `Margin Kiri (${leftCm.toFixed(1)} cm)`;
        }
        if (rightMarker) {
            rightMarker.style.left = `${rightPct}%`;
            rightMarker.title = `Margin Kanan (${(total - rightCm).toFixed(1)} cm)`;
        }

        // Indent markers:
        const leftIndentCm = (this.paragraphIndent && this.paragraphIndent.left) ? parseFloat(this.paragraphIndent.left) : 0;
        const rightIndentCm = (this.paragraphIndent && this.paragraphIndent.right) ? parseFloat(this.paragraphIndent.right) : 0;

        let firstLineOffsetCm = 0;
        const activeFormat = typeof this.getActiveParagraphFormat === 'function' ? this.getActiveParagraphFormat() : null;
        if (activeFormat && activeFormat.specialIndent === 'first-line') {
            firstLineOffsetCm = parseFloat(activeFormat.specialIndentVal || 1.25);
        } else if (activeFormat && activeFormat.specialIndent === 'hanging') {
            firstLineOffsetCm = -parseFloat(activeFormat.specialIndentVal || 1.25);
        }

        if (firstLineMarker) {
            const posCm = Math.max(0, leftCm + leftIndentCm + firstLineOffsetCm);
            firstLineMarker.style.left = `${(posCm / total) * 100}%`;
            firstLineMarker.title = `Indentasi Baris Pertama (${posCm.toFixed(2)} cm)`;
        }

        if (leftIndentMarker) {
            const posCm = Math.max(0, leftCm + leftIndentCm);
            leftIndentMarker.style.left = `${(posCm / total) * 100}%`;
            leftIndentMarker.title = `Indentasi Kiri (${posCm.toFixed(2)} cm)`;
        }

        if (rightIndentMarker) {
            const posCm = Math.max(0, rightCm - rightIndentCm);
            rightIndentMarker.style.left = `${(posCm / total) * 100}%`;
            rightIndentMarker.title = `Indentasi Kanan (${posCm.toFixed(2)} cm)`;
        }
    }

    /* -------------------------------------------------------------
     * Microsoft Word Paragraph Customization Suite
     * ------------------------------------------------------------- */

    setParagraphFormatting(options = {}, scope = 'selection') {
        let targets = [];
        if (scope === 'all') {
            this.getAllEditorBodies().forEach(body => {
                const els = body.querySelectorAll('p, h1, h2, h3, h4, h5, h6, blockquote, .heading-chapter');
                els.forEach(el => targets.push(el));
            });
        } else {
            targets = this.getSelectedParagraphElements();
            if (targets.length === 0) {
                const active = this.getActiveEditor();
                if (active) {
                    const p = active.querySelector('p') || active;
                    targets.push(p);
                }
            }
        }

        targets.forEach(el => {
            const align = options.alignment || options.textAlign;
            if (align) {
                el.style.textAlign = align;
                el.classList.remove('text-left', 'text-center', 'text-right', 'text-justify');
                if (align === 'justify') el.classList.add('text-justify');
                else if (align === 'center') el.classList.add('text-center');
                else if (align === 'right') el.classList.add('text-right');
                else el.classList.add('text-left');
            }
            const lIndent = options.leftIndent ?? options.indentLeft;
            if (lIndent !== undefined && lIndent !== null && lIndent !== '') {
                const l = parseFloat(lIndent);
                el.style.marginLeft = l > 0 ? `${l}cm` : '';
            }
            const rIndent = options.rightIndent ?? options.indentRight;
            if (rIndent !== undefined && rIndent !== null && rIndent !== '') {
                const r = parseFloat(rIndent);
                el.style.marginRight = r > 0 ? `${r}cm` : '';
            }
            if (options.specialIndent) {
                const val = (options.specialIndentVal !== undefined && options.specialIndentVal !== '')
                    ? parseFloat(options.specialIndentVal)
                    : ((options.specialVal !== undefined && options.specialVal !== '') ? parseFloat(options.specialVal) : 1.25);
                if (options.specialIndent === 'first-line') {
                    el.style.textIndent = `${val}cm`;
                    el.style.paddingLeft = '';
                    el.classList.add('indent-first-line');
                    el.classList.remove('indent-hanging');
                } else if (options.specialIndent === 'hanging') {
                    el.style.textIndent = `-${val}cm`;
                    el.style.paddingLeft = `${val}cm`;
                    el.classList.add('indent-hanging');
                    el.classList.remove('indent-first-line');
                } else if (options.specialIndent === 'none') {
                    el.style.textIndent = '0';
                    el.style.paddingLeft = '';
                    el.classList.remove('indent-first-line', 'indent-hanging');
                }
            }
            const spBefore = options.spaceBefore ?? options.spacingBefore;
            if (spBefore !== undefined && spBefore !== null && spBefore !== '') {
                el.style.marginTop = `${parseFloat(spBefore)}pt`;
            }
            const spAfter = options.spaceAfter ?? options.spacingAfter;
            if (spAfter !== undefined && spAfter !== null && spAfter !== '') {
                el.style.marginBottom = `${parseFloat(spAfter)}pt`;
            }
            if (options.lineSpacing) {
                el.style.lineHeight = String(options.lineSpacing);
            }
            if (options.keepWithNext !== undefined) {
                el.style.pageBreakAfter = options.keepWithNext ? 'avoid' : '';
                el.style.breakAfter = options.keepWithNext ? 'avoid' : '';
            }
            if (options.keepLinesTogether !== undefined) {
                el.style.pageBreakInside = options.keepLinesTogether ? 'avoid' : '';
                el.style.breakInside = options.keepLinesTogether ? 'avoid' : '';
            }
            if (options.pageBreakBefore !== undefined) {
                el.style.pageBreakBefore = options.pageBreakBefore ? 'always' : '';
                el.style.breakBefore = options.pageBreakBefore ? 'page' : '';
            }
            if (options.widowOrphan !== undefined) {
                el.style.orphans = options.widowOrphan ? '2' : '';
                el.style.widows = options.widowOrphan ? '2' : '';
            }
            if (options.suppressLineNumbers !== undefined) {
                if (options.suppressLineNumbers) {
                    el.classList.add('suppress-line-numbers');
                } else {
                    el.classList.remove('suppress-line-numbers');
                }
            }
            if (options.dontHyphenate !== undefined) {
                if (options.dontHyphenate) {
                    el.style.hyphens = 'none';
                    el.style.webkitHyphens = 'none';
                } else {
                    el.style.hyphens = '';
                    el.style.webkitHyphens = '';
                }
            }
            if (options.outlineLevel) {
                el.setAttribute('data-outline-level', options.outlineLevel);
            }
            if (options.mirrorIndents !== undefined) {
                this.paragraphIndent.mirrorIndents = Boolean(options.mirrorIndents);
            }
            if (options.dontAddSpaceSame !== undefined) {
                el.setAttribute('data-dont-add-space-same', options.dontAddSpaceSame ? 'true' : 'false');
            }
        });

        if (options.lineSpacing) {
            this.currentLineSpacing = String(options.lineSpacing);
            const lsSelect = document.getElementById('line-spacing-select');
            if (lsSelect) lsSelect.value = this.currentLineSpacing;
        }
        if (options.spaceBefore !== undefined) {
            this.paragraphSpacing.before = parseFloat(options.spaceBefore);
            const sbInput = document.getElementById('layout-spacing-before');
            if (sbInput) sbInput.value = this.paragraphSpacing.before;
        }
        if (options.spaceAfter !== undefined) {
            this.paragraphSpacing.after = parseFloat(options.spaceAfter);
            const saInput = document.getElementById('layout-spacing-after');
            if (saInput) saInput.value = this.paragraphSpacing.after;
        }
        if (options.leftIndent !== undefined) {
            this.paragraphIndent.left = parseFloat(options.leftIndent || 0);
            const liInput = document.getElementById('layout-indent-left');
            if (liInput) liInput.value = this.paragraphIndent.left;
        }
        if (options.rightIndent !== undefined) {
            this.paragraphIndent.right = parseFloat(options.rightIndent || 0);
            const riInput = document.getElementById('layout-indent-right');
            if (riInput) riInput.value = this.paragraphIndent.right;
        }

        this.handleInput();
    }

    addSpaceBeforeParagraph(scope = 'selection') {
        this.setParagraphFormatting({ spaceBefore: 12 }, scope);
    }

    removeSpaceBeforeParagraph(scope = 'selection') {
        this.setParagraphFormatting({ spaceBefore: 0 }, scope);
    }

    addSpaceAfterParagraph(scope = 'selection') {
        this.setParagraphFormatting({ spaceAfter: 8 }, scope);
    }

    removeSpaceAfterParagraph(scope = 'selection') {
        this.setParagraphFormatting({ spaceAfter: 0 }, scope);
    }

    setParagraphIndent(leftCm, rightCm, scope = 'selection') {
        this.setParagraphFormatting({ leftIndent: leftCm, rightIndent: rightCm }, scope);
    }

    setParagraphSpacing(beforePt, afterPt, scope = 'selection') {
        this.setParagraphFormatting({ spaceBefore: beforePt, spaceAfter: afterPt }, scope);
    }

    setSpecialIndent(type, cmVal = 1.25, scope = 'selection') {
        this.setParagraphFormatting({ specialIndent: type, specialIndentVal: cmVal }, scope);
    }

    setParagraphShading(color, scope = 'selection') {
        const targets = scope === 'all'
            ? this.getAllEditorBodies().flatMap(b => Array.from(b.querySelectorAll('p, blockquote, h1, h2, h3, h4, h5, h6, li')))
            : this.getSelectedParagraphElements();

        if (targets.length === 0) {
            const active = this.getActiveEditor();
            if (active) {
                const firstP = active.querySelector('p');
                if (firstP) targets.push(firstP);
            }
        }

        targets.forEach(el => {
            if (!color || color === 'none') {
                el.style.backgroundColor = '';
                el.style.padding = '';
                el.style.borderRadius = '';
            } else {
                el.style.backgroundColor = color;
                if (!el.style.padding) el.style.padding = '6px 12px';
                el.style.borderRadius = '4px';
            }
        });
        this.handleInput();
    }

    setParagraphBorder(borderType, color = '#374151', scope = 'selection') {
        const targets = scope === 'all'
            ? this.getAllEditorBodies().flatMap(b => Array.from(b.querySelectorAll('p, blockquote, h1, h2, h3, h4, h5, h6, li')))
            : this.getSelectedParagraphElements();

        if (targets.length === 0) {
            const active = this.getActiveEditor();
            if (active) {
                const firstP = active.querySelector('p');
                if (firstP) targets.push(firstP);
            }
        }

        targets.forEach(el => {
            el.style.borderTop = '';
            el.style.borderBottom = '';
            el.style.borderLeft = '';
            el.style.borderRight = '';
            el.style.border = '';

            if (borderType === 'bottom') {
                el.style.borderBottom = `1.5px solid ${color}`;
                el.style.paddingBottom = '4px';
            } else if (borderType === 'top') {
                el.style.borderTop = `1.5px solid ${color}`;
                el.style.paddingTop = '4px';
            } else if (borderType === 'left') {
                el.style.borderLeft = `3.5px solid ${color}`;
                el.style.paddingLeft = '10px';
            } else if (borderType === 'right') {
                el.style.borderRight = `3.5px solid ${color}`;
                el.style.paddingRight = '10px';
            } else if (borderType === 'box') {
                el.style.border = `1px solid ${color}`;
                el.style.padding = '8px 12px';
                el.style.borderRadius = '4px';
            } else if (borderType === 'horizontal') {
                el.style.borderBottom = `1px dashed ${color}`;
                el.style.paddingBottom = '8px';
                el.style.marginBottom = '12px';
            }
        });
        this.handleInput();
    }

    applyDropCap(options = {}) {
        const position = options.position || 'dropped';
        if (position === 'none') {
            this.removeDropCap();
            return;
        }

        const lines = options.lines || 3;
        const font = options.font || 'inherit';
        const distance = options.distance || 0.2;

        const targets = this.getSelectedParagraphElements();
        const p = targets[0] || (this.getActiveEditor() ? this.getActiveEditor().querySelector('p') : null);
        if (!p) return;

        const existing = p.querySelector('.drop-cap');
        if (existing) {
            const originalChar = existing.textContent;
            existing.replaceWith(originalChar);
        }

        const rawText = p.innerHTML.trim();
        if (!rawText) return;

        const match = rawText.match(/^([A-Za-z0-9\u00C0-\u024F\u1E00-\u1EFF])/);
        if (!match) return;

        const firstChar = match[1];
        const rest = rawText.substring(firstChar.length);

        const dropCapSpan = document.createElement('span');
        dropCapSpan.className = 'drop-cap';
        dropCapSpan.textContent = firstChar;
        dropCapSpan.style.float = 'left';
        dropCapSpan.style.lineHeight = '0.82';
        dropCapSpan.style.fontWeight = 'bold';
        dropCapSpan.style.marginRight = `${distance}cm`;
        dropCapSpan.style.fontSize = `${lines * 1.05}em`;
        if (font && font !== 'inherit') dropCapSpan.style.fontFamily = font;
        if (position === 'margin') {
            dropCapSpan.style.marginLeft = `-${distance * 2}cm`;
        }

        p.innerHTML = rest;
        p.prepend(dropCapSpan);
        this.handleInput();
    }

    removeDropCap() {
        const targets = this.getSelectedParagraphElements();
        const p = targets[0] || (this.getActiveEditor() ? this.getActiveEditor().querySelector('p') : null);
        if (!p) return;
        const existing = p.querySelector('.drop-cap');
        if (existing) {
            const char = existing.textContent;
            existing.replaceWith(char);
            this.handleInput();
        }
    }

    setTextDirection(dir = 'ltr', scope = 'selection') {
        const targets = scope === 'all'
            ? this.getAllEditorBodies().flatMap(b => Array.from(b.querySelectorAll('p, blockquote, h1, h2, h3, h4, h5, h6, li')))
            : this.getSelectedParagraphElements();

        if (targets.length === 0) {
            const active = this.getActiveEditor();
            if (active) {
                const firstP = active.querySelector('p');
                if (firstP) targets.push(firstP);
            }
        }

        targets.forEach(el => {
            el.setAttribute('dir', dir);
            el.style.direction = dir;
            el.style.textAlign = dir === 'rtl' ? 'right' : 'left';
        });

        this.handleInput();
    }

    toggleFormattingMarks() {
        this.showFormattingMarks = !this.showFormattingMarks;
        const bodies = this.getAllEditorBodies();
        bodies.forEach(body => {
            body.classList.toggle('show-formatting-marks', this.showFormattingMarks);
        });
        const btn = document.getElementById('btn-toggle-formatting-marks');
        if (btn) {
            btn.classList.toggle('bg-indigo-100', this.showFormattingMarks);
            btn.classList.toggle('text-indigo-700', this.showFormattingMarks);
            btn.classList.toggle('dark:bg-indigo-900', this.showFormattingMarks);
            btn.classList.toggle('dark:text-indigo-300', this.showFormattingMarks);
        }
        return this.showFormattingMarks;
    }

    sortParagraphs(order = 'asc', scope = 'selection') {
        let targets = [];
        if (scope === 'all') {
            const active = this.getActiveEditor();
            if (active) targets = Array.from(active.querySelectorAll('p, li'));
        } else {
            targets = this.getSelectedParagraphElements();
            if (targets.length <= 1) {
                const active = this.getActiveEditor();
                if (active) targets = Array.from(active.querySelectorAll('p, li'));
            }
        }

        if (targets.length < 2) return;

        const parent = targets[0].parentElement;
        if (!parent) return;

        const textArray = targets.map(el => ({
            el,
            text: el.textContent.trim(),
            html: el.innerHTML
        }));

        textArray.sort((a, b) => {
            const res = a.text.localeCompare(b.text, 'id', { numeric: true, sensitivity: 'base' });
            return order === 'asc' ? res : -res;
        });

        textArray.forEach(item => {
            parent.appendChild(item.el);
        });

        this.handleInput();
    }

    setColumns(columnVal = 1, scope = 'document') {
        this.columns = columnVal;
        const bodies = scope === 'document' ? this.getAllEditorBodies() : [this.getActiveEditor() || this.editorEl];
        bodies.forEach(body => {
            if (!body) return;
            if (columnVal === '2' || columnVal === 2) {
                body.style.columnCount = '2';
                body.style.columnGap = '1.25cm';
                body.style.columnRule = '1px solid #e5e7eb';
            } else if (columnVal === '3' || columnVal === 3) {
                body.style.columnCount = '3';
                body.style.columnGap = '1cm';
                body.style.columnRule = '1px solid #e5e7eb';
            } else if (columnVal === 'left' || columnVal === 'right') {
                body.style.columnCount = '2';
                body.style.columnGap = '1.25cm';
            } else {
                body.style.columnCount = '';
                body.style.columnGap = '';
                body.style.columnRule = '';
            }
        });
        this.handleInput();
    }

    insertColumnBreak() {
        const active = this.getActiveEditor();
        if (active) active.focus();
        const breakHtml = '<div class="column-break my-4 border-b border-dotted border-gray-400 select-none text-center" style="break-before: column; page-break-before: column;"><span class="bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded text-[10px] text-gray-500 font-mono">Pemisah Kolom</span></div><p><br></p>';
        document.execCommand('insertHTML', false, breakHtml);
        this.handleInput();
    }

    insertSectionBreak(type = 'newpage') {
        if (type === 'newpage') {
            this.insertPageBreak();
        } else {
            const active = this.getActiveEditor();
            if (active) active.focus();
            const breakHtml = '<div class="section-break my-4 border-b-2 border-double border-indigo-300 dark:border-indigo-700 select-none text-center py-1"><span class="bg-indigo-50 dark:bg-indigo-950 px-2 py-0.5 rounded text-[10px] text-indigo-600 dark:text-indigo-400 font-mono">Pemisah Bagian (Continuous)</span></div><p><br></p>';
            document.execCommand('insertHTML', false, breakHtml);
            this.handleInput();
        }
    }

    setHyphenation(mode = 'none') {
        this.hyphenation = mode;
        this.getAllEditorBodies().forEach(body => {
            if (mode === 'auto') {
                body.style.hyphens = 'auto';
                body.style.webkitHyphens = 'auto';
            } else {
                body.style.hyphens = 'none';
                body.style.webkitHyphens = 'none';
            }
        });
        this.handleInput();
    }

    setTabStops(stops = [], defaultStop = 1.27) {
        this.tabStops = stops;
        this.defaultTabStop = defaultStop;
        this.getAllEditorBodies().forEach(body => {
            body.style.tabSize = `${defaultStop}cm`;
        });
        this.handleInput();
    }

    setWatermark(watermarkObj) {
        this.watermark = watermarkObj;
        this.getAllPageSheets().forEach((sheet, idx) => this.applyPageSetupToSheet(sheet, idx + 1));
        this.handleInput();
    }

    removeWatermark() {
        this.watermark = null;
        this.getAllPageSheets().forEach((sheet, idx) => this.applyPageSetupToSheet(sheet, idx + 1));
        this.handleInput();
    }

    setPageColor(color) {
        this.pageColor = color;
        this.getAllPageSheets().forEach((sheet, idx) => this.applyPageSetupToSheet(sheet, idx + 1));
        this.handleInput();
    }

    setPageBorder(borderObj) {
        this.pageBorder = borderObj;
        this.getAllPageSheets().forEach((sheet, idx) => this.applyPageSetupToSheet(sheet, idx + 1));
        this.handleInput();
    }

    removePageBorder() {
        this.pageBorder = null;
        this.getAllPageSheets().forEach((sheet, idx) => this.applyPageSetupToSheet(sheet, idx + 1));
        this.handleInput();
    }

    setLineNumbers(mode = 'none', options = {}) {
        this.lineNumbers = mode;
        if (options.startAt) this.lineNumbersOptions.startAt = parseInt(options.startAt, 10);
        if (options.countBy) this.lineNumbersOptions.countBy = parseInt(options.countBy, 10);
        this.getAllPageSheets().forEach((sheet, idx) => this.applyPageSetupToSheet(sheet, idx + 1));
        this.handleInput();
    }

    getSelectedParagraphElements() {
        const sel = window.getSelection();
        if (!sel || sel.rangeCount === 0) return [];
        const range = sel.getRangeAt(0);

        const startNode = range.startContainer.nodeType === Node.TEXT_NODE ? range.startContainer.parentElement : range.startContainer;
        const endNode = range.endContainer.nodeType === Node.TEXT_NODE ? range.endContainer.parentElement : range.endContainer;

        const startP = startNode?.closest('p, h1, h2, h3, h4, h5, h6, blockquote, .heading-chapter');
        const endP = endNode?.closest('p, h1, h2, h3, h4, h5, h6, blockquote, .heading-chapter');

        if (!startP && !endP) return [];
        if (startP === endP && startP) return [startP];

        const body = startP?.closest('.page-sheet-body') || endP?.closest('.page-sheet-body');
        if (!body) return startP ? [startP] : (endP ? [endP] : []);

        const allBlocks = Array.from(body.querySelectorAll('p, h1, h2, h3, h4, h5, h6, blockquote, .heading-chapter'));
        const startIdx = allBlocks.indexOf(startP);
        const endIdx = allBlocks.indexOf(endP);

        if (startIdx !== -1 && endIdx !== -1) {
            const [low, high] = startIdx <= endIdx ? [startIdx, endIdx] : [endIdx, startIdx];
            return allBlocks.slice(low, high + 1);
        }

        return startP ? [startP] : (endP ? [endP] : []);
    }

    getActiveParagraphFormat() {
        try {
            const paras = this.getSelectedParagraphElements();
            const p = paras.length > 0 ? paras[0] : (this.getActiveEditor()?.querySelector('p') || null);
            if (!p) {
                return {
                    alignment: 'justify',
                    textAlign: 'justify',
                    leftIndent: (this.paragraphIndent && this.paragraphIndent.left) || 0,
                    indentLeft: (this.paragraphIndent && this.paragraphIndent.left) || 0,
                    rightIndent: (this.paragraphIndent && this.paragraphIndent.right) || 0,
                    indentRight: (this.paragraphIndent && this.paragraphIndent.right) || 0,
                    specialIndent: (this.paragraphIndent && this.paragraphIndent.special) || 'first-line',
                    specialIndentVal: (this.paragraphIndent && this.paragraphIndent.specialVal) || 1.25,
                    specialVal: (this.paragraphIndent && this.paragraphIndent.specialVal) || 1.25,
                    spaceBefore: (this.paragraphSpacing && this.paragraphSpacing.before) || 0,
                    spacingBefore: (this.paragraphSpacing && this.paragraphSpacing.before) || 0,
                    spaceAfter: (this.paragraphSpacing && this.paragraphSpacing.after) || 6,
                    spacingAfter: (this.paragraphSpacing && this.paragraphSpacing.after) || 6,
                    lineSpacing: this.currentLineSpacing || '1.5',
                    keepWithNext: false,
                    keepLinesTogether: false,
                    pageBreakBefore: false,
                    widowOrphan: true,
                };
            }

            const computed = (window && typeof window.getComputedStyle === 'function') ? window.getComputedStyle(p) : {};
            let align = p.style?.textAlign || computed?.textAlign || 'justify';
            if (align === 'start') align = 'left';

            let leftIndent = 0;
            if (p.style?.marginLeft) {
                leftIndent = parseFloat(p.style.marginLeft) || 0;
            }

            let rightIndent = 0;
            if (p.style?.marginRight) {
                rightIndent = parseFloat(p.style.marginRight) || 0;
            }

            let specialIndent = 'none';
            let specialVal = 1.25;
            if (p.style?.textIndent) {
                const ti = parseFloat(p.style.textIndent);
                if (ti < 0 || p.classList?.contains('indent-hanging')) {
                    specialIndent = 'hanging';
                    specialVal = Math.abs(ti) || 1.25;
                } else if (ti > 0 || p.classList?.contains('indent-first-line')) {
                    specialIndent = 'first-line';
                    specialVal = ti || 1.25;
                }
            }

            let spaceBefore = 0;
            if (p.style?.marginTop) {
                spaceBefore = parseFloat(p.style.marginTop) || 0;
            }

            let spaceAfter = 6;
            if (p.style?.marginBottom) {
                spaceAfter = parseFloat(p.style.marginBottom) || 0;
            }

            let lineSpacing = p.style?.lineHeight || this.currentLineSpacing || '1.5';

            const keepWithNext = p.style?.pageBreakAfter === 'avoid' || p.style?.breakAfter === 'avoid';
            const keepLinesTogether = p.style?.pageBreakInside === 'avoid' || p.style?.breakInside === 'avoid';
            const pageBreakBefore = p.style?.pageBreakBefore === 'always' || p.style?.breakBefore === 'page';
            const widowOrphan = p.style?.orphans !== '';
            const suppressLineNumbers = Boolean(p.classList?.contains('suppress-line-numbers'));
            const dontHyphenate = p.style?.hyphens === 'none';
            const outlineLevel = p.getAttribute?.('data-outline-level') || (p.tagName?.startsWith('H') ? `level${p.tagName.replace('H', '')}` : 'body');
            const dontAddSpaceSame = p.getAttribute?.('data-dont-add-space-same') === 'true';
            const mirrorIndents = Boolean(this.paragraphIndent?.mirrorIndents);

            return {
                alignment: align,
                textAlign: align,
                leftIndent,
                indentLeft: leftIndent,
                rightIndent,
                indentRight: rightIndent,
                specialIndent,
                specialIndentVal: specialVal,
                specialVal,
                spaceBefore,
                spacingBefore: spaceBefore,
                spaceAfter,
                spacingAfter: spaceAfter,
                lineSpacing,
                keepWithNext,
                keepLinesTogether,
                pageBreakBefore,
                widowOrphan,
                suppressLineNumbers,
                dontHyphenate,
                outlineLevel,
                dontAddSpaceSame,
                mirrorIndents,
            };
        } catch (err) {
            console.warn('getActiveParagraphFormat fallback:', err);
            return {
                alignment: 'justify',
                textAlign: 'justify',
                leftIndent: 0,
                indentLeft: 0,
                rightIndent: 0,
                indentRight: 0,
                specialIndent: 'first-line',
                specialIndentVal: 1.25,
                specialVal: 1.25,
                spaceBefore: 0,
                spacingBefore: 0,
                spaceAfter: 6,
                spacingAfter: 6,
                lineSpacing: '1.5',
                keepWithNext: false,
                keepLinesTogether: false,
                pageBreakBefore: false,
                widowOrphan: true,
            };
        }
    }

    setFontFamily(fontName) {
        this.currentFontFamily = fontName;
        const active = this.getActiveEditor();
        if (active) active.focus();

        const sel = window.getSelection();
        if (sel && sel.rangeCount > 0 && !sel.isCollapsed && this.isEditorFocused()) {
            const range = sel.getRangeAt(0);
            const span = document.createElement('span');
            span.style.fontFamily = fontName;
            span.appendChild(range.extractContents());
            range.insertNode(span);
        } else {
            this.getAllEditorBodies().forEach(b => {
                b.style.fontFamily = fontName;
            });
        }

        const fontSelect = document.getElementById('font-family-select');
        if (fontSelect) {
            const cleanFont = fontName.toLowerCase().replace(/['"]/g, '');
            for (let i = 0; i < fontSelect.options.length; i++) {
                const optVal = fontSelect.options[i].value.toLowerCase().replace(/['"]/g, '');
                const optText = fontSelect.options[i].text.toLowerCase();
                if (optVal.includes(cleanFont) || cleanFont.includes(optText) || (cleanFont.includes('times') && optVal.includes('times'))) {
                    fontSelect.selectedIndex = i;
                    break;
                }
            }
        }
        this.handleInput();
    }

    setFontSize(ptSize) {
        this.currentFontSize = ptSize;
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
        this.currentLineSpacing = val;
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

    /* -------------------------------------------------------------
     * Table of Contents Generator (Microsoft Word Standard)
     * Supports: Automatic Table 1, Automatic Table 2, Manual Table, Custom TOC,
     * Tab Leaders (dots, dashes, line, none), Formats, Levels (1-5), and Live Update.
     * ------------------------------------------------------------- */

    extractHeadings(maxLevels = 3) {
        const headings = [];
        const bodies = this.getAllEditorBodies();

        bodies.forEach((body, bodyIdx) => {
            const pageNum = bodyIdx + 1;
            const hElements = body.querySelectorAll('h1, h2, h3, h4, h5, .heading-chapter, p.font-bold');

            hElements.forEach((h, hIdx) => {
                const text = h.textContent.trim();
                if (!text) return;

                // Never include the TOC title or TOC elements themselves in the TOC
                if (h.closest('.table-of-contents') || /^(DAFTAR\s+ISI|TABLE\s+OF\s+CONTENTS|KONTEN|CONTENTS)$/i.test(text)) {
                    return;
                }

                let level = 1;
                const tag = h.tagName.toLowerCase();
                if (tag === 'h1' || /^BAB\s+[I|V|X|0-9]+|LEMBAR\s+PENGESAHAN|ABSTRAK|ABSTRACT|KATA\s+PENGANTAR|DAFTAR\s+TABEL|DAFTAR\s+GAMBAR|DAFTAR\s+PUSTAKA/i.test(text)) {
                    level = 1;
                } else if (tag === 'h2' || /^\d+\.\d+\s+[A-Z]/.test(text)) {
                    level = 2;
                } else if (tag === 'h3' || /^\d+\.\d+\.\d+\s+[A-Z]/.test(text)) {
                    level = 3;
                } else if (tag === 'h4') {
                    level = 4;
                } else if (tag === 'h5') {
                    level = 5;
                }

                if (level > maxLevels) return;

                let id = h.id;
                if (!id) {
                    const cleanSlug = text.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '').substring(0, 30);
                    id = `heading-${cleanSlug || 'sec'}-${pageNum}-${hIdx}`;
                    h.id = id;
                }

                headings.push({
                    el: h,
                    id: id,
                    text: text,
                    level: level,
                    page: pageNum,
                });
            });
        });

        return headings;
    }

    generateTableOfContentsHtml(options = {}) {
        const headingTitle = (options.heading || 'DAFTAR ISI').trim().toUpperCase();
        const style = options.style || 'template';
        const leader = options.leader || 'dots';
        const levels = parseInt(options.levels || '3', 10);
        const showPageNumbers = options.showPageNumbers !== false;
        const rightAlignPageNumbers = options.rightAlignPageNumbers !== false;
        const templateType = options.templateType || 'auto2';

        let headings = options.headings || this.extractHeadings(levels);

        if (templateType === 'manual' && (!headings || headings.length === 0)) {
            headings = [
                { id: 'heading-bab-1', text: 'BAB I PENDAHULUAN', level: 1, page: 1 },
                { id: 'heading-sub-1-1', text: '1.1 Latar Belakang Masalah', level: 2, page: 1 },
                { id: 'heading-sub-1-2', text: '1.2 Rumusan Masalah', level: 2, page: 3 },
                { id: 'heading-bab-2', text: 'BAB II TINJAUAN PUSTAKA', level: 1, page: 5 },
                { id: 'heading-sub-2-1', text: '2.1 Landasan Teori', level: 2, page: 5 },
                { id: 'heading-sub-2-1-1', text: '2.1.1 Arsitektur Sistem dan Mikrokontroler', level: 3, page: 7 },
                { id: 'heading-bab-3', text: 'BAB III METODE PENELITIAN', level: 1, page: 12 },
                { id: 'heading-daftarpustaka', text: 'DAFTAR PUSTAKA', level: 1, page: 25 },
            ];
        }

        const filtered = (headings || []).filter(h => (h.level || 1) <= levels);

        const leaderClass = leader === 'dashes' ? 'border-b border-dashed border-gray-400 dark:border-gray-500' :
                            leader === 'line' ? 'border-b border-solid border-gray-400 dark:border-gray-500' :
                            leader === 'none' ? 'border-none' :
                            'border-b border-dotted border-gray-400 dark:border-gray-500';

        const tocEntries = [];
        filtered.forEach(item => {
            const lvl = parseInt(item.level || '1', 10);
            const id = this.escapeHtml(item.id || '');
            const text = this.escapeHtml(item.text || '');
            const page = this.escapeHtml(String(item.page || '1'));

            let levelIndent = 'pl-0 font-bold text-gray-900 dark:text-gray-100';
            if (lvl === 2) levelIndent = 'pl-6 font-medium text-gray-800 dark:text-gray-200 text-xs sm:text-sm';
            else if (lvl === 3) levelIndent = 'pl-12 text-gray-700 dark:text-gray-300 text-xs';
            else if (lvl === 4) levelIndent = 'pl-16 text-gray-600 dark:text-gray-400 text-xs';
            else if (lvl >= 5) levelIndent = 'pl-20 text-gray-500 text-xs';

            if (style === 'classic') {
                levelIndent += (lvl === 1 ? ' uppercase tracking-wide font-serif' : ' italic font-serif');
            } else if (style === 'modern') {
                levelIndent += ' font-sans';
            } else if (style === 'formal') {
                levelIndent += ' font-serif';
            }

            let pageHtml = '';
            if (showPageNumbers) {
                const alignClass = rightAlignPageNumbers ? 'font-mono text-xs font-semibold shrink-0 text-right min-w-[20px]' : 'font-mono text-xs font-semibold ml-2';
                pageHtml = `<span class="toc-page ${alignClass} text-gray-800 dark:text-gray-200" contenteditable="true">${page}</span>`;
            }

            let leaderHtml = '';
            if (showPageNumbers && rightAlignPageNumbers && leader !== 'none') {
                leaderHtml = `<span class="toc-leader flex-1 mx-2 ${leaderClass} select-none" contenteditable="false"></span>`;
            } else if (showPageNumbers && !rightAlignPageNumbers) {
                leaderHtml = '&nbsp;';
            }

            tocEntries.push(`
                <div class="toc-entry toc-item flex items-baseline justify-between py-1 ${levelIndent}" data-level="${lvl}" data-target="${id}">
                    <a href="#${id}" class="toc-link hover:text-indigo-600 dark:hover:text-indigo-400 hover:underline truncate max-w-[80%]" contenteditable="true">${text}</a>
                    ${leaderHtml}
                    ${pageHtml}
                </div>
            `);
        });

        let entriesHtml = tocEntries.join('');
        if (!entriesHtml) {
            entriesHtml = '<p class="text-xs text-gray-400 italic text-center py-6">Belum ada judul bab atau subbab terdeteksi. Tambahkan Heading (Bab / Subbab) di naskah lalu klik "Perbarui Tabel".</p>';
        }

        let containerClass = 'w-full my-6 relative group';
        if (style === 'modern') containerClass = 'w-full my-6 relative group';
        else if (style === 'formal') containerClass = 'w-full my-6 relative group border-t border-b border-gray-400 dark:border-gray-600 py-4';
        else if (style === 'simple') containerClass = 'w-full my-4 relative group';

        return `
            <div class="table-of-contents my-6 ${containerClass} relative shadow-2xs group" data-toc-block="true" data-toc-style="${this.escapeHtml(style)}" data-toc-leader="${this.escapeHtml(leader)}" data-toc-levels="${levels}" data-toc-shownum="${showPageNumbers}" data-toc-rightalign="${rightAlignPageNumbers}" contenteditable="false" tabindex="0">
                <div class="toc-toolbar absolute -top-3 right-4 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-2.5 py-1 rounded-md shadow-md flex items-center gap-2 text-[11px] opacity-0 group-hover:opacity-100 transition duration-150 z-20 no-print select-none" contenteditable="false">
                    <button type="button" class="btn-toc-update hover:text-indigo-400 dark:hover:text-indigo-600 font-semibold flex items-center gap-1 cursor-pointer" title="Perbarui Daftar Isi (Update Table)">
                        <span>🔄 Perbarui Tabel...</span>
                    </button>
                    <span class="w-px h-3 bg-gray-700 dark:bg-gray-300"></span>
                    <button type="button" class="btn-toc-customize hover:text-indigo-400 dark:hover:text-indigo-600 flex items-center gap-1 cursor-pointer" title="Kustomisasi Format Daftar Isi">
                        <span>⚙️ Format</span>
                    </button>
                    <span class="w-px h-3 bg-gray-700 dark:bg-gray-300"></span>
                    <button type="button" class="btn-toc-delete hover:text-red-400 text-red-300 dark:text-red-600 cursor-pointer" title="Hapus Daftar Isi dari Dokumen">
                        <span>🗑️ Hapus</span>
                    </button>
                </div>

                <h2 class="toc-header text-center font-bold text-lg mb-6 uppercase tracking-wider text-gray-900 dark:text-white font-serif outline-none" contenteditable="true">${this.escapeHtml(headingTitle)}</h2>

                <div class="toc-entries space-y-1 font-serif" contenteditable="true">
                    ${entriesHtml}
                </div>
            </div>
        `;
    }

    generateTableOfContents(options = {}) {
        const levels = parseInt(options.levels || '3', 10);
        const headings = this.extractHeadings(levels);

        if ((!headings || headings.length === 0) && options.templateType !== 'manual') {
            return false;
        }

        const tocHtml = this.generateTableOfContentsHtml({ ...options, headings });
        const activeContainer = this.pagesWrapper || this.canvasEl || document;
        const existingToc = activeContainer.querySelector('.table-of-contents, [data-toc-block="true"]');

        if (existingToc) {
            const temp = document.createElement('div');
            temp.innerHTML = tocHtml.trim();
            const newToc = temp.firstElementChild;
            existingToc.replaceWith(newToc);
        } else {
            const active = this.getActiveEditor();
            if (active) {
                this.insertAtCursor(tocHtml + '<p><br></p>');
            } else {
                const pages = activeContainer.querySelectorAll('.document-page-sheet');
                const targetPage = pages.length > 1 ? pages[1] : (pages[0] || activeContainer);
                const body = targetPage.querySelector('.page-sheet-body') || targetPage;
                body.insertAdjacentHTML('afterbegin', tocHtml + '<p><br></p>');
            }
        }

        this.handleInput();
        return true;
    }

    updateTableOfContents(updateType = 'entire', targetToc = null) {
        const activeContainer = this.pagesWrapper || this.canvasEl || document;
        const existingToc = targetToc || this.activeTocBlock || activeContainer.querySelector('.table-of-contents, [data-toc-block="true"]');
        if (!existingToc) {
            return this.generateTableOfContents();
        }

        const style = existingToc.getAttribute('data-toc-style') || 'template';
        const leader = existingToc.getAttribute('data-toc-leader') || 'dots';
        const levels = parseInt(existingToc.getAttribute('data-toc-levels') || '3', 10);
        const showPageNumbers = existingToc.getAttribute('data-toc-shownum') !== 'false';
        const rightAlignPageNumbers = existingToc.getAttribute('data-toc-rightalign') !== 'false';
        const headingEl = existingToc.querySelector('.toc-header, h2, h1');
        const heading = headingEl ? headingEl.textContent.trim() : 'DAFTAR ISI';

        const headings = this.extractHeadings(levels);

        if (updateType === 'page_numbers') {
            const pageMap = new Map();
            headings.forEach(h => {
                if (h.id) pageMap.set(h.id, h.page);
                pageMap.set(h.text.toLowerCase().trim(), h.page);
            });

            const items = existingToc.querySelectorAll('.toc-item');
            items.forEach(item => {
                const targetId = item.getAttribute('data-target');
                const link = item.querySelector('.toc-link');
                const titleText = link ? link.textContent.toLowerCase().trim() : '';
                const newPage = pageMap.get(targetId) || pageMap.get(titleText);
                if (newPage !== undefined) {
                    const pageSpan = item.querySelector('.toc-page');
                    if (pageSpan) pageSpan.textContent = String(newPage);
                }
            });
            this.activeTocBlock = null;
            this.handleInput();
            return true;
        }

        // Entire table update
        const updatedHtml = this.generateTableOfContentsHtml({
            heading,
            style,
            leader,
            levels,
            showPageNumbers,
            rightAlignPageNumbers,
            headings
        });

        const temp = document.createElement('div');
        temp.innerHTML = updatedHtml.trim();
        const newToc = temp.firstElementChild;
        existingToc.replaceWith(newToc);
        this.activeTocBlock = null;

        this.handleInput();
        const content = this.getContent();
        this.renderPaginatedDocument(content);
        return true;
    }

    removeTableOfContents(targetToc = null) {
        const activeContainer = this.pagesWrapper || this.canvasEl || document;
        let removed = false;

        if (targetToc && targetToc.isConnected) {
            targetToc.remove();
            removed = true;
        } else {
            const existingTocs = activeContainer.querySelectorAll('.table-of-contents, [data-toc-block="true"]');
            existingTocs.forEach(toc => {
                toc.remove();
                removed = true;
            });
        }

        if (removed) {
            this.activeTocBlock = null;
            this.handleInput();
            const content = this.getContent();
            this.renderPaginatedDocument(content);
            return true;
        }
        return false;
    }

    setSelectionHeadingLevel(level) {
        const active = this.getActiveEditor();
        if (!active) return;
        active.focus();

        const lvl = String(level).toLowerCase();
        if (lvl === '1') {
            document.execCommand('formatBlock', false, '<h1>');
        } else if (lvl === '2') {
            document.execCommand('formatBlock', false, '<h2>');
        } else if (lvl === '3') {
            document.execCommand('formatBlock', false, '<h3>');
        } else {
            document.execCommand('formatBlock', false, '<p>');
        }
        this.handleInput();
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
        const active = this.getActiveEditor();
        if (active) active.focus();
        try {
            document.execCommand('styleWithCSS', false, true);
        } catch (e) {}
        this.execute('foreColor', hexColor);
        const picker = document.getElementById('text-color-picker');
        if (picker) picker.value = hexColor;
    }

    setHighlightColor(hexColor) {
        const active = this.getActiveEditor();
        if (active) active.focus();
        try {
            document.execCommand('styleWithCSS', false, true);
        } catch (e) {}
        this.execute('hiliteColor', hexColor);
    }

    clearFormatting() {
        this.execute('removeFormat');
    }

    growFontSize() {
        const sizes = ['8pt', '9pt', '10pt', '11pt', '12pt', '14pt', '16pt', '18pt', '20pt', '24pt', '28pt', '32pt', '36pt', '48pt', '72pt'];
        const fontSelect = document.getElementById('font-size-select');
        let current = fontSelect?.value || '12pt';
        let idx = sizes.indexOf(current);
        if (idx === -1) idx = 4;
        if (idx < sizes.length - 1) {
            const next = sizes[idx + 1];
            if (fontSelect) fontSelect.value = next;
            this.setFontSize(next);
        }
    }

    shrinkFontSize() {
        const sizes = ['8pt', '9pt', '10pt', '11pt', '12pt', '14pt', '16pt', '18pt', '20pt', '24pt', '28pt', '32pt', '36pt', '48pt', '72pt'];
        const fontSelect = document.getElementById('font-size-select');
        let current = fontSelect?.value || '12pt';
        let idx = sizes.indexOf(current);
        if (idx === -1) idx = 4;
        if (idx > 0) {
            const prev = sizes[idx - 1];
            if (fontSelect) fontSelect.value = prev;
            this.setFontSize(prev);
        }
    }

    copyFormat() {
        const sel = window.getSelection();
        if (!sel || sel.rangeCount === 0) return null;
        const node = sel.anchorNode?.nodeType === Node.ELEMENT_NODE ? sel.anchorNode : sel.anchorNode?.parentElement;
        if (!node) return null;
        const computed = window.getComputedStyle(node);
        this.copiedFormat = {
            fontFamily: computed.fontFamily,
            fontSize: computed.fontSize,
            fontWeight: computed.fontWeight,
            fontStyle: computed.fontStyle,
            textDecoration: computed.textDecoration,
            color: computed.color,
            backgroundColor: computed.backgroundColor,
        };
        return this.copiedFormat;
    }

    applyCopiedFormat() {
        if (!this.copiedFormat) return;
        const sel = window.getSelection();
        if (!sel || sel.rangeCount === 0 || sel.isCollapsed) return;
        const range = sel.getRangeAt(0);
        const span = document.createElement('span');
        if (this.copiedFormat.fontFamily) span.style.fontFamily = this.copiedFormat.fontFamily;
        if (this.copiedFormat.fontSize) span.style.fontSize = this.copiedFormat.fontSize;
        if (this.copiedFormat.fontWeight) span.style.fontWeight = this.copiedFormat.fontWeight;
        if (this.copiedFormat.fontStyle) span.style.fontStyle = this.copiedFormat.fontStyle;
        if (this.copiedFormat.textDecoration) span.style.textDecoration = this.copiedFormat.textDecoration;
        if (this.copiedFormat.color) span.style.color = this.copiedFormat.color;
        if (this.copiedFormat.backgroundColor && this.copiedFormat.backgroundColor !== 'rgba(0, 0, 0, 0)') {
            span.style.backgroundColor = this.copiedFormat.backgroundColor;
        }

        try {
            range.surroundContents(span);
        } catch (e) {
            this.execute('fontName', this.copiedFormat.fontFamily);
            this.execute('foreColor', this.copiedFormat.color);
        }
        this.copiedFormat = null;
        this.handleInput();
    }

    newBlankDocument() {
        this.setTitle('Dokumen Baru Tanpa Judul.docx');
        this.customHeaderText = '';
        this.customFooterText = '';
        this.setContent('<p><br></p>');
        this.setFormatBadge('docx');
        this.setMarginPreset('normal');
        this.handleInput();
    }

    insertCoverPage(type = 'skripsi') {
        const title = this.getTitle() || 'JUDUL NASKAH DOKUMEN';
        let coverHtml = '';
        if (type === 'skripsi') {
            coverHtml = `
<div class="academic-cover text-center my-8 p-6">
    <h1 class="cover-badge text-xl font-bold tracking-widest uppercase my-4">SKRIPSI / TUGAS AKHIR</h1>
    <h2 class="cover-title text-2xl font-bold my-6 uppercase max-w-xl mx-auto leading-snug">${this.escapeHtml(title)}</h2>
    <p class="cover-subtitle italic text-sm text-gray-600 dark:text-gray-400 my-4 max-w-md mx-auto">Diajukan sebagai salah satu syarat untuk memperoleh gelar sarjana</p>
    <div class="my-8 py-4">
        <div class="w-24 h-24 mx-auto rounded-full bg-indigo-100 dark:bg-indigo-950 flex items-center justify-center text-indigo-600 font-bold border-2 border-dashed border-indigo-400">LOGO</div>
    </div>
    <div class="my-6">
        <p class="cover-author-label text-sm font-medium">Disusun Oleh:</p>
        <p class="font-bold text-base text-gray-900 dark:text-gray-100">NAMA PENYUSUN DOKUMEN</p>
        <p class="cover-author-nim font-mono text-sm">NIM. 2026101001</p>
    </div>
    <div class="my-8">
        <p class="cover-institution font-bold text-sm uppercase">PROGRAM STUDI TEKNIK INFORMATIKA</p>
        <p class="cover-institution font-bold text-sm uppercase">FAKULTAS TEKNOLOGI INFORMASI</p>
        <p class="cover-institution font-bold text-sm uppercase">UNIVERSITAS PENELITIAN INDONESIA</p>
        <p class="cover-institution font-bold text-sm my-2">2026</p>
    </div>
</div>
<div class="page-break my-8 border-b-2 border-dashed border-gray-400 text-center py-2 select-none" data-page-break="true"><span class="bg-gray-100 px-3 py-1 rounded-full text-xs text-gray-500 font-mono">Pemisah Halaman (Akhir Sampul)</span></div>
`;
        } else {
            coverHtml = `
<div class="business-cover text-center my-8 p-8 border-b-4 border-indigo-600">
    <p class="text-xs uppercase tracking-widest text-indigo-600 font-bold mb-2">LAPORAN RESMI PERUSAHAAN</p>
    <h1 class="text-3xl font-extrabold text-gray-900 dark:text-gray-100 my-4">${this.escapeHtml(title)}</h1>
    <p class="text-base text-gray-600 dark:text-gray-400 max-w-xl mx-auto my-3">Dokumen evaluasi strategis, metodologi riset, dan analisis implementasi sistem 2026.</p>
    <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-800 flex justify-between items-center text-xs text-gray-500">
        <span>Disiapkan oleh: Tim Pengembang VelScribe</span>
        <span>Tanggal: ${new Date().toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' })}</span>
    </div>
</div>
<div class="page-break my-8 border-b-2 border-dashed border-gray-400 text-center py-2 select-none" data-page-break="true"><span class="bg-gray-100 px-3 py-1 rounded-full text-xs text-gray-500 font-mono">Pemisah Halaman</span></div>
`;
        }

        const currentContent = this.getContent();
        this.setContent(coverHtml + '\n' + currentContent);
        this.handleInput();
    }

    insertSymbol(char) {
        if (!char) return;
        this.insertAtCursor(char);
    }

    insertChecklist(itemText = 'Poin tugas / tindak lanjut...') {
        const active = this.getActiveEditor();
        if (active) active.focus();
        const html = `<div class="task-item flex items-center gap-2 my-1.5"><input type="checkbox" class="task-checkbox rounded border-gray-400 text-indigo-600 focus:ring-indigo-500 w-4 h-4 cursor-pointer"> <span>${this.escapeHtml(itemText)}</span></div><p><br></p>`;
        this.insertAtCursor(html);
    }

    /* -------------------------------------------------------------
     * Citations & Bibliography Engine (Microsoft Word Standard)
     * Supports: APA 7th, IEEE, Harvard, MLA 9th, Chicago 17th, Vancouver
     * ------------------------------------------------------------- */

    loadSavedSources() {
        try {
            const raw = localStorage.getItem('velscribe_citation_sources');
            if (raw) {
                const parsed = JSON.parse(raw);
                if (Array.isArray(parsed) && parsed.length > 0) return parsed;
            }
        } catch (e) {}

        return [
            {
                id: 'src-sugiyono-2024',
                type: 'book',
                title: 'Metode Penelitian Kuantitatif, Kualitatif, dan R&D',
                author: 'Sugiyono',
                year: '2024',
                publisher: 'Alfabeta',
                city: 'Bandung',
                journal: '',
                volume: '',
                issue: '',
                pages: '350',
                doi: '',
                url: ''
            },
            {
                id: 'src-santoso-2024',
                type: 'journal',
                title: 'Implementasi Sistem Kendali Kualitas Air pada Prototipe Pengolahan Limbah Tekstil Berbasis Sensor TCS34725 dan ESP32',
                author: 'Santoso, Budi & Pratama, Aditya',
                year: '2024',
                publisher: '',
                city: '',
                journal: 'Jurnal Rekayasa Sistem dan Teknologi Informasi',
                volume: '8',
                issue: '2',
                pages: '112-120',
                doi: '10.29207/resti.v8i2.5678',
                url: 'https://doi.org/10.29207/resti.v8i2.5678'
            },
            {
                id: 'src-pressman-2020',
                type: 'book',
                title: "Software Engineering: A Practitioner's Approach (9th Edition)",
                author: 'Pressman, Roger S. & Maxim, Bruce R.',
                year: '2020',
                publisher: 'McGraw-Hill Education',
                city: 'New York',
                journal: '',
                volume: '',
                issue: '',
                pages: '980',
                doi: '',
                url: ''
            }
        ];
    }

    saveSources() {
        try {
            localStorage.setItem('velscribe_citation_sources', JSON.stringify(this.sources || []));
        } catch (e) {}
    }

    getSources() {
        return this.sources || [];
    }

    getSource(id) {
        return (this.sources || []).find(s => s.id === id) || null;
    }

    addSource(source) {
        if (!source || !source.title) return null;
        if (!this.sources) this.sources = [];

        const normalized = {
            id: source.id || ('src-' + Date.now() + '-' + Math.random().toString(36).substring(2, 6)),
            type: source.type || 'journal',
            title: source.title || 'Judul Referensi',
            author: source.author || 'Anonim',
            year: String(source.year || new Date().getFullYear()),
            journal: source.journal || '',
            publisher: source.publisher || '',
            city: source.city || '',
            volume: String(source.volume || ''),
            issue: String(source.issue || ''),
            pages: String(source.pages || ''),
            doi: source.doi || '',
            url: source.url || ''
        };

        const existingIdx = this.sources.findIndex(s => s.id === normalized.id || (s.title.toLowerCase() === normalized.title.toLowerCase() && s.author.toLowerCase() === normalized.author.toLowerCase()));
        if (existingIdx >= 0) {
            this.sources[existingIdx] = { ...this.sources[existingIdx], ...normalized };
            this.saveSources();
            return this.sources[existingIdx];
        }

        this.sources.push(normalized);
        this.saveSources();
        return normalized;
    }

    deleteSource(id) {
        if (!this.sources) return;
        this.sources = this.sources.filter(s => s.id !== id);
        this.saveSources();
        this.updateBibliography();
    }

    setCitationStyle(style) {
        const allowed = ['apa', 'ieee', 'harvard', 'mla', 'chicago', 'vancouver'];
        if (!allowed.includes(style)) return;
        this.citationStyle = style;
        this.updateBibliography(style);
    }

    formatCitationInText(source, style = 'apa', index = 1) {
        style = (style || this.citationStyle || 'apa').toLowerCase();
        const author = source.author || 'Anonim';
        const year = source.year || new Date().getFullYear();

        let authorLabel = author.trim();
        if (authorLabel.includes(';')) {
            const first = authorLabel.split(';')[0].trim();
            const lastName = first.includes(',') ? first.split(',')[0].trim() : first;
            authorLabel = lastName + ' et al.';
        } else if (authorLabel.includes('&')) {
            const parts = authorLabel.split('&').map(p => p.trim());
            const firstLastName = parts[0].includes(',') ? parts[0].split(',')[0].trim() : parts[0];
            const secondLastName = parts[1].includes(',') ? parts[1].split(',')[0].trim() : parts[1];
            authorLabel = `${firstLastName} & ${secondLastName}`;
        } else if (authorLabel.includes(',')) {
            authorLabel = authorLabel.split(',')[0].trim();
        }

        switch (style) {
            case 'ieee':
                return `[${index}]`;
            case 'vancouver':
                return `(${index})`;
            case 'mla':
                return `(${authorLabel})`;
            case 'chicago':
                return `(${authorLabel} ${year})`;
            case 'harvard':
                return `(${authorLabel}, ${year})`;
            case 'apa':
            default:
                return `(${authorLabel}, ${year})`;
        }
    }

    formatBibliographyEntry(source, style = 'apa', index = 1) {
        style = (style || this.citationStyle || 'apa').toLowerCase();
        const author = (source.author || 'Anonim').trim();
        const year = String(source.year || new Date().getFullYear()).trim();
        const title = (source.title || 'Judul Tidak Diketahui').trim();
        const journal = (source.journal || '').trim();
        const publisher = (source.publisher || '').trim();
        const city = (source.city || '').trim();
        const volume = (source.volume || '').trim();
        const issue = (source.issue || '').trim();
        const pages = (source.pages || '').trim();
        const doi = (source.doi || '').trim();
        const url = (source.url || '').trim();
        const type = (source.type || 'journal').trim();

        const doiUrl = doi ? (doi.startsWith('http') ? doi : `https://doi.org/${doi}`) : url;

        switch (style) {
            case 'ieee': {
                let venuePart = '';
                if (journal) {
                    venuePart = ` <em>${journal}</em>`;
                    if (volume) venuePart += `, vol. ${volume}`;
                    if (issue) venuePart += `, no. ${issue}`;
                    if (pages) venuePart += `, pp. ${pages}`;
                } else if (publisher) {
                    venuePart = (city ? ` ${city}:` : '') + ` <em>${publisher}</em>`;
                    if (pages) venuePart += `, pp. ${pages}`;
                }
                const doiPart = doiUrl ? `, doi: ${doiUrl}` : '';
                return `[${index}] ${author}, "${title}," ${venuePart}, ${year}${doiPart}.`;
            }
            case 'harvard': {
                let venue = '';
                if (journal) {
                    const volIssue = volume + (issue ? `(${issue})` : '');
                    venue = ` <em>${journal}</em>, ${volIssue}` + (pages ? `, pp. ${pages}` : '');
                } else if (publisher) {
                    venue = ` <em>${publisher}</em>` + (city ? `: ${city}` : '');
                }
                const online = doiUrl ? ` Available at: ${doiUrl}.` : '';
                return `${author} (${year}) '${title}',${venue}.${online}`;
            }
            case 'mla': {
                let venue = '';
                if (journal) {
                    const volIssue = (volume ? ` vol. ${volume},` : '') + (issue ? ` no. ${issue},` : '');
                    venue = ` <em>${journal}</em>,${volIssue} ${year}` + (pages ? `, pp. ${pages}` : '');
                } else if (publisher) {
                    venue = ` <em>${publisher}</em>, ${year}`;
                }
                const loc = doiUrl ? `, ${doiUrl}` : '';
                return `${author}. "${title}."${venue}${loc}.`;
            }
            case 'chicago': {
                let venue = '';
                if (journal) {
                    const volIssue = volume + (issue ? ` (${issue})` : '');
                    venue = ` <em>${journal}</em> ${volIssue}` + (pages ? `: ${pages}` : '');
                } else if (publisher) {
                    venue = (city ? ` ${city}:` : '') + ` ${publisher}`;
                }
                const link = doiUrl ? ` ${doiUrl}.` : '';
                return `${author}. ${year}. "${title}."${venue}.${link}`;
            }
            case 'vancouver': {
                let venue = '';
                if (journal) {
                    venue = ` ${journal}. ${year};${volume}` + (issue ? `(${issue})` : '') + (pages ? `:${pages}` : '');
                } else if (publisher) {
                    venue = (city ? ` ${city}:` : '') + ` ${publisher}; ${year}`;
                }
                return `${index}. ${author}. ${title}.${venue}.`;
            }
            case 'apa':
            default: {
                const titleStyled = type === 'book' ? `<em>${title}</em>` : title;
                let venue = '';
                if (journal) {
                    const volStyled = volume ? `<em>${volume}</em>` : '';
                    const issueStyled = issue ? `(${issue})` : '';
                    const pagesStyled = pages ? `, ${pages}` : '';
                    venue = ` <em>${journal}</em>, ${volStyled}${issueStyled}${pagesStyled}.`;
                } else if (publisher) {
                    const cityPart = city ? `${city}: ` : '';
                    venue = ` ${cityPart}${publisher}.`;
                }
                const doiStyled = doiUrl ? ` ${doiUrl}` : '';
                return `${author} (${year}). ${titleStyled}.${venue}${doiStyled}`;
            }
        }
    }

    insertInTextCitation(sourceOrData) {
        let source = sourceOrData;
        if (typeof sourceOrData === 'string') {
            source = this.getSource(sourceOrData);
        }
        if (!source) return;

        source = this.addSource(source);
        const style = this.citationStyle || 'apa';

        // Find existing citation marks in document to compute index if numbered style
        const activeContainer = this.pagesWrapper || this.canvasEl || document;
        const existingMarks = Array.from(activeContainer.querySelectorAll('.citation-mark'));
        const markIndexMap = new Map();
        existingMarks.forEach(m => {
            const sid = m.getAttribute('data-source-id');
            if (sid && !markIndexMap.has(sid)) {
                markIndexMap.set(sid, markIndexMap.size + 1);
            }
        });
        let index = 1;
        if (markIndexMap.has(source.id)) {
            index = markIndexMap.get(source.id);
        } else {
            index = markIndexMap.size + 1;
        }

        const citationText = this.formatCitationInText(source, style, index);
        const safeTitle = this.escapeHtml(source.title || '');
        const safeAuthor = this.escapeHtml(source.author || '');

        const citationHtml = `<span class="citation-mark inline-block font-semibold text-indigo-700 dark:text-indigo-400 bg-indigo-50/80 dark:bg-indigo-950/40 px-1 py-0.5 rounded cursor-pointer select-none hover:underline" data-source-id="${this.escapeHtml(source.id)}" data-author="${safeAuthor}" data-year="${this.escapeHtml(source.year)}" contenteditable="false" title="Sitasi: ${safeTitle} (${source.year}) - Klik untuk opsi sitasi">${this.escapeHtml(citationText)}</span>&nbsp;`;

        this.insertAtCursor(citationHtml);
        this.updateBibliography();
        this.handleInput();
    }

    insertCitation(author, year, title, source) {
        // Backwards compatible method
        const src = {
            id: 'src-' + Date.now(),
            type: 'journal',
            title: title || 'Judul Referensi',
            author: author || 'Penulis',
            year: year || '2026',
            journal: source || 'Jurnal / Penerbit',
            publisher: source || ''
        };
        this.addSource(src);

        const biblioEntry = `<p class="citation-entry bibliography-entry my-2 pl-8 -indent-8 text-sm text-gray-800 dark:text-gray-200" data-source-id="${src.id}">${this.escapeHtml(author)} (${this.escapeHtml(year)}). <em>${this.escapeHtml(title)}</em>. ${this.escapeHtml(source)}.</p>`;
        this.insertAtCursor(biblioEntry);
        this.handleInput();
    }

    generateBibliographyHtml(style = null, heading = 'DAFTAR PUSTAKA') {
        style = (style || this.citationStyle || 'apa').toLowerCase();
        heading = (heading || 'DAFTAR PUSTAKA').trim().toUpperCase();

        const sources = this.getSources();
        let sorted = [...sources];

        if (['ieee', 'vancouver'].includes(style)) {
            const activeContainer = this.pagesWrapper || this.canvasEl || document;
            const marks = Array.from(activeContainer.querySelectorAll('.citation-mark'));
            const orderedIds = [];
            marks.forEach(m => {
                const sid = m.getAttribute('data-source-id');
                if (sid && !orderedIds.includes(sid)) orderedIds.push(sid);
            });

            sorted.sort((a, b) => {
                const idxA = orderedIds.indexOf(a.id);
                const idxB = orderedIds.indexOf(b.id);
                if (idxA !== -1 && idxB !== -1) return idxA - idxB;
                if (idxA !== -1) return -1;
                if (idxB !== -1) return 1;
                return 0;
            });
        } else {
            sorted.sort((a, b) => (a.author || '').localeCompare(b.author || ''));
        }

        let entriesHtml = '';
        sorted.forEach((src, idx) => {
            const num = idx + 1;
            const entryText = this.formatBibliographyEntry(src, style, num);
            const sid = this.escapeHtml(src.id);

            if (['ieee', 'vancouver'].includes(style)) {
                entriesHtml += `        <div class="citation-entry bibliography-entry flex items-start gap-2 text-sm leading-relaxed my-2 text-gray-900 dark:text-gray-100" data-source-id="${sid}">
            <span class="font-bold text-indigo-600 dark:text-indigo-400 shrink-0">[${num}]</span>
            <div class="flex-1">${entryText}</div>
        </div>\n`;
            } else {
                entriesHtml += `        <p class="citation-entry bibliography-entry pl-8 -indent-8 text-sm leading-relaxed my-2.5 text-gray-900 dark:text-gray-100" data-source-id="${sid}">${entryText}</p>\n`;
            }
        });

        if (!entriesHtml) {
            entriesHtml = '        <p class="text-xs text-gray-400 italic text-center py-4">Belum ada sumber referensi. Buka Kelola Sumber untuk menambahkan sumber sitasi.</p>\n';
        }

        return `<div class="bibliography-container my-8 pt-6 border-t border-gray-200 dark:border-gray-800" data-bibliography-block="true" data-citation-style="${style}">
    <h1 class="heading-chapter text-center font-bold text-xl my-6 text-gray-900 dark:text-white uppercase">${this.escapeHtml(heading)}</h1>
    <div class="bibliography-entries space-y-2">
${entriesHtml}    </div>
</div>`;
    }

    insertBibliography(style = null, heading = 'DAFTAR PUSTAKA') {
        style = style || this.citationStyle || 'apa';
        this.citationStyle = style;

        const activeContainer = this.pagesWrapper || this.canvasEl || document;
        const existingBlock = activeContainer.querySelector('.bibliography-container, [data-bibliography-block="true"]');

        const html = this.generateBibliographyHtml(style, heading);

        if (existingBlock) {
            const temp = document.createElement('div');
            temp.innerHTML = html.trim();
            const newBlock = temp.firstElementChild;
            existingBlock.replaceWith(newBlock);
            this.handleInput();
            return newBlock;
        }

        const active = this.getActiveEditor();
        if (active) {
            this.insertAtCursor(html + '<p><br></p>');
        } else {
            const pages = activeContainer.querySelectorAll('.document-page-sheet');
            const lastPage = pages.length > 0 ? pages[pages.length - 1] : this.canvasEl;
            const body = lastPage ? (lastPage.querySelector('.page-sheet-body') || lastPage) : activeContainer;
            body.insertAdjacentHTML('beforeend', html);
        }

        this.handleInput();
        return activeContainer.querySelector('.bibliography-container');
    }

    updateBibliography(style = null) {
        if (style) {
            this.citationStyle = style;
        }
        const activeStyle = this.citationStyle || 'apa';
        const activeContainer = this.pagesWrapper || this.canvasEl || document;

        const marks = Array.from(activeContainer.querySelectorAll('.citation-mark'));
        const sourceAppearanceOrder = [];

        marks.forEach(m => {
            const sid = m.getAttribute('data-source-id');
            if (sid && !sourceAppearanceOrder.includes(sid)) {
                sourceAppearanceOrder.push(sid);
            }
        });

        marks.forEach(m => {
            const sid = m.getAttribute('data-source-id');
            let source = this.getSource(sid);
            if (!source) {
                source = {
                    id: sid,
                    author: m.getAttribute('data-author') || 'Anonim',
                    year: m.getAttribute('data-year') || '2024',
                    title: m.getAttribute('title') || 'Referensi'
                };
            }
            const orderIndex = sourceAppearanceOrder.indexOf(sid) + 1;
            const newLabel = this.formatCitationInText(source, activeStyle, orderIndex || 1);
            m.textContent = newLabel;
        });

        const existingBlock = activeContainer.querySelector('.bibliography-container, [data-bibliography-block="true"]');
        if (existingBlock) {
            const headingEl = existingBlock.querySelector('.heading-chapter, h1, h2');
            const heading = headingEl ? headingEl.textContent.trim() : 'DAFTAR PUSTAKA';
            const updatedHtml = this.generateBibliographyHtml(activeStyle, heading);

            const temp = document.createElement('div');
            temp.innerHTML = updatedHtml.trim();
            const newBlock = temp.firstElementChild;
            existingBlock.replaceWith(newBlock);
        }

        this.handleInput();
    }

    /* -------------------------------------------------------------
     * Mathematical Formulas & LaTeX Equations Engine (KaTeX)
     * ------------------------------------------------------------- */

    insertEquation(latex = '', isDisplay = true) {
        const cleanLatex = latex.trim();
        if (!cleanLatex) return;

        const rendered = DocumentEngine.renderLatexHtml(cleanLatex, isDisplay);
        const tag = isDisplay ? 'div' : 'span';
        const displayClass = isDisplay ? 'display-equation my-4 text-center' : 'inline-equation mx-0.5 inline-block align-baseline';
        const safe = DocumentEngine.escapeHtmlStatic(cleanLatex);
        const html = `<${tag} class="latex-equation ${displayClass} cursor-pointer select-none" contenteditable="false" data-latex="${safe}" data-display="${isDisplay}" title="Klik dua kali untuk mengedit rumus LaTeX"><div class="latex-math-wrapper inline-block px-3 py-1.5 rounded transition">${rendered}</div></${tag}>`;

        this.insertAtCursor(html + (isDisplay ? '<p><br></p>' : '&nbsp;'));
        this.handleInput();
    }

    updateEquation(el, newLatex, isDisplay = true) {
        if (!el) return;
        const cleanLatex = newLatex.trim();
        if (!cleanLatex) return;

        const rendered = DocumentEngine.renderLatexHtml(cleanLatex, isDisplay);
        const safe = DocumentEngine.escapeHtmlStatic(cleanLatex);
        el.setAttribute('data-latex', safe);
        el.setAttribute('data-display', String(isDisplay));
        if (isDisplay) {
            el.className = 'latex-equation display-equation my-4 text-center cursor-pointer select-none';
        } else {
            el.className = 'latex-equation inline-equation mx-0.5 inline-block align-baseline cursor-pointer select-none';
        }
        let wrapper = el.querySelector('.latex-math-wrapper');
        if (!wrapper) {
            wrapper = document.createElement('div');
            wrapper.className = 'latex-math-wrapper inline-block px-3 py-1.5 rounded transition';
            el.innerHTML = '';
            el.appendChild(wrapper);
        }
        wrapper.innerHTML = rendered;
        this.handleInput();
    }

    deleteEquation(el) {
        if (!el) return;
        el.remove();
        this.handleInput();
    }

    formatLatexEquationsInHtml(html) {
        if (!html || typeof html !== 'string') return html || '';
        if (!html.includes('$$') && !html.includes('\\(') && !html.includes('\\[') && !html.includes('latex-equation') && !html.includes('$')) {
            return html;
        }

        // 1. Convert display math blocks wrapped in <p> or standalone: <p>$$...$$</p>
        let result = html.replace(/<p\b[^>]*>(?:<[^>]+>)*\s*(?:\$\$|\\\[)([\s\S]+?)(?:\$\$|\\\])\s*(?:<[^>]+>)*<\/p>/gi, (match, formula) => {
            const clean = formula.trim();
            if (!clean) return match;
            const rendered = DocumentEngine.renderLatexHtml(clean, true);
            const safe = DocumentEngine.escapeHtmlStatic(clean);
            return `<div class="latex-equation display-equation my-4 text-center cursor-pointer select-none" contenteditable="false" data-latex="${safe}" data-display="true" title="Klik dua kali untuk mengedit rumus LaTeX"><div class="latex-math-wrapper inline-block px-3 py-1.5 rounded transition">${rendered}</div></div>`;
        });

        // 2. Convert display math: $$...$$ or \[...\]
        result = result.replace(/(?:\$\$([\s\S]+?)\$\$|\\\[([\s\S]+?)\\\])/g, (match, f1, f2) => {
            const clean = (f1 || f2 || '').trim();
            if (!clean) return match;
            const rendered = DocumentEngine.renderLatexHtml(clean, true);
            const safe = DocumentEngine.escapeHtmlStatic(clean);
            return `<div class="latex-equation display-equation my-4 text-center cursor-pointer select-none" contenteditable="false" data-latex="${safe}" data-display="true" title="Klik dua kali untuk mengedit rumus LaTeX"><div class="latex-math-wrapper inline-block px-3 py-1.5 rounded transition">${rendered}</div></div>`;
        });

        // 3. Convert inline math: $formula$ (avoid matching currency or plain numbers)
        result = result.replace(/(?<!\$)\$(?!\$)([^\$\n]+?)(?<!\$)\$(?!\$)/g, (match, formula) => {
            const clean = formula.trim();
            if (!clean || /^\d+([.,]\d+)?$/.test(clean)) return match;
            const isMath = /[\\_{}^=+\-<>*/\(\)\[\]]/.test(clean) || /[a-zA-Z]/.test(clean);
            if (!isMath) return match;
            const rendered = DocumentEngine.renderLatexHtml(clean, false);
            const safe = DocumentEngine.escapeHtmlStatic(clean);
            return `<span class="latex-equation inline-equation mx-0.5 inline-block align-baseline cursor-pointer select-none" contenteditable="false" data-latex="${safe}" data-display="false" title="Klik dua kali untuk mengedit rumus LaTeX"><span class="latex-math-wrapper inline-block px-1 rounded transition">${rendered}</span></span>`;
        });

        return result;
    }

    renderLatexInContainer(container) {
        if (!container) return;
        const equations = container.querySelectorAll('.latex-equation');
        equations.forEach(eq => {
            const latex = eq.getAttribute('data-latex');
            const isDisplay = eq.getAttribute('data-display') !== 'false';
            let wrapper = eq.querySelector('.latex-math-wrapper');
            if (latex && (!wrapper || !wrapper.querySelector('.katex'))) {
                if (!wrapper) {
                    wrapper = document.createElement(isDisplay ? 'div' : 'span');
                    wrapper.className = 'latex-math-wrapper inline-block px-2 py-1 rounded transition';
                    eq.innerHTML = '';
                    eq.appendChild(wrapper);
                }
                wrapper.innerHTML = DocumentEngine.renderLatexHtml(latex, isDisplay);
            }
        });
    }

    readAloud() {
        if (!('speechSynthesis' in window)) {
            return false;
        }

        if (window.speechSynthesis.speaking) {
            window.speechSynthesis.cancel();
            return false;
        }

        const sel = window.getSelection();
        let textToRead = sel && sel.toString().trim() ? sel.toString().trim() : '';

        if (!textToRead) {
            const activeEditor = this.getActiveEditor();
            textToRead = (activeEditor?.innerText || activeEditor?.textContent || '').trim();
        }

        if (!textToRead) {
            return false;
        }

        const utterance = new SpeechSynthesisUtterance(textToRead.slice(0, 4000));
        utterance.lang = 'id-ID';
        utterance.rate = 1.0;
        utterance.pitch = 1.0;
        window.speechSynthesis.speak(utterance);
        return true;
    }

    stopReadAloud() {
        if ('speechSynthesis' in window && window.speechSynthesis.speaking) {
            window.speechSynthesis.cancel();
        }
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
        newSheet.className = 'document-page-sheet relative flex flex-col justify-between shadow-2xl';
        this.applyPageSetupToSheet(newSheet);

        const header = document.createElement('header');
        header.className = 'page-sheet-header flex items-center justify-between text-[11px] text-gray-400 pb-2 border-b border-gray-100 dark:border-gray-800 select-none';
        const docTitle = this.getTitle() || 'Dokumen VelScribe Wordcraft';
        const hLeft = this.customHeaderText || docTitle;
        header.innerHTML = `
            <span class="header-doc-title truncate max-w-[55%] font-medium" title="${this.escapeHtml(hLeft)}">${this.escapeHtml(hLeft)}</span>
            <span class="header-chapter-name text-gray-400 font-serif italic text-[11px] truncate max-w-[42%]"></span>
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
        footer.className = 'page-sheet-footer flex items-center justify-between text-[11px] text-gray-400 pt-2 border-t border-gray-100 dark:border-gray-800 select-none';
        const fLeft = this.customFooterText || '';
        footer.innerHTML = `
            <span class="footer-doc-title text-[11px] text-gray-400 font-mono truncate max-w-[85%]" title="${this.escapeHtml(fLeft)}">${this.escapeHtml(fLeft)}</span>
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

        let computedFont = '';
        let computedSize = '';
        let computedColor = '';
        const node = sel.anchorNode;
        const elem = node?.nodeType === Node.ELEMENT_NODE ? node : node?.parentElement;
        if (elem) {
            try {
                const cs = window.getComputedStyle(elem);
                computedFont = cs.fontFamily || '';
                computedSize = cs.fontSize || '';
                computedColor = cs.color || '';
            } catch (e) {}
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
            fontFamily: computedFont,
            fontSize: computedSize,
            color: computedColor,
        };
    }

    updateToolbarSelectionState(state) {
        if (!state) return;

        // Sync font family select
        const fontSelect = document.getElementById('font-family-select');
        if (fontSelect && state.fontFamily) {
            const cleanComputed = state.fontFamily.toLowerCase().replace(/['"]/g, '');
            for (let i = 0; i < fontSelect.options.length; i++) {
                const optVal = fontSelect.options[i].value.toLowerCase().replace(/['"]/g, '');
                const optText = fontSelect.options[i].text.toLowerCase();
                if (cleanComputed.includes(optText) || optVal.includes(cleanComputed.split(',')[0].trim())) {
                    fontSelect.selectedIndex = i;
                    break;
                }
            }
        }

        // Sync font size select
        const sizeSelect = document.getElementById('font-size-select');
        if (sizeSelect && state.fontSize) {
            const px = parseFloat(state.fontSize);
            if (!isNaN(px)) {
                const pt = Math.round(px * 0.75);
                const targetVal = `${pt}pt`;
                for (let i = 0; i < sizeSelect.options.length; i++) {
                    if (sizeSelect.options[i].value === targetVal) {
                        sizeSelect.selectedIndex = i;
                        break;
                    }
                }
            }
        }

        // Sync text color picker
        const colorPicker = document.getElementById('text-color-picker');
        if (colorPicker && state.color) {
            const rgbMatch = state.color.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/i);
            if (rgbMatch) {
                const r = parseInt(rgbMatch[1], 10).toString(16).padStart(2, '0');
                const g = parseInt(rgbMatch[2], 10).toString(16).padStart(2, '0');
                const b = parseInt(rgbMatch[3], 10).toString(16).padStart(2, '0');
                colorPicker.value = `#${r}${g}${b}`;
            }
        }

        // Sync formatting buttons active state
        const boldBtn = document.querySelector('[data-cmd="bold"]');
        if (boldBtn) {
            boldBtn.classList.toggle('bg-gray-200', Boolean(state.isBold));
            boldBtn.classList.toggle('dark:bg-gray-700', Boolean(state.isBold));
        }
        const italicBtn = document.querySelector('[data-cmd="italic"]');
        if (italicBtn) {
            italicBtn.classList.toggle('bg-gray-200', Boolean(state.isItalic));
            italicBtn.classList.toggle('dark:bg-gray-700', Boolean(state.isItalic));
        }
        const underlineBtn = document.querySelector('[data-cmd="underline"]');
        if (underlineBtn) {
            underlineBtn.classList.toggle('bg-gray-200', Boolean(state.isUnderline));
            underlineBtn.classList.toggle('dark:bg-gray-700', Boolean(state.isUnderline));
        }
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
            if (/^(NIM|NPM|NIS|NIK|OLEH|DISUSUN|BY|PROGRAM\s+STUDI|JURUSAN|FAKULTAS|POLITEKNIK|UNIVERSITAS|DEPARTMENT|STUDY\s+PROGRAM|SEKOLAH\s+TINGGI|INSTITUT|SEBAGAI\s+SALAH\s+SATU\s+SYARAT|SUBMITTED\s+AS)\b/i.test(clean)) {
                return false;
            }
            if (/(\.{2,}|…|·{2,}|\.\s*\.\s*\.)/.test(clean)) return true;
            if (/[\s\t]{2,}(\d+|[ivxlcdm]+)$/i.test(clean)) return true;
            return false;
        };

        const isCoverMetadata = (text) => {
            if (!text) return true;
            const clean = text.trim();
            if (/^(OLEH|DISUSUN|BY|NIM|NPM|NIS|NIK|PROGRAM\s+STUDI|JURUSAN|FAKULTAS|POLITEKNIK|UNIVERSITAS|DEPARTMENT|STUDY\s+PROGRAM|SEKOLAH\s+TINGGI|INSTITUT|SEBAGAI\s+SALAH\s+SATU\s+SYARAT|SUBMITTED\s+AS|KEMENTERIAN|\d{4})\b/i.test(clean)) {
                return true;
            }
            if (/^(SKRIPSI|THESIS|TESIS|DISERTASI|PROPOSAL|LAPORAN|TUGAS\s+AKHIR)$/i.test(clean)) {
                return true;
            }
            return false;
        };

        bodies.forEach((body, bodyIdx) => {
            const pageNum = bodyIdx + 1;
            const headingEls = body.querySelectorAll('h1, h2, h3, h4');
            headingEls.forEach(el => {
                if (el.classList?.contains('toc-entry') || el.closest('.toc-entry')) return;
                if (el.classList?.contains('cover-title') || el.classList?.contains('cover-badge') || el.classList?.contains('doc-title')) return;

                let text = (el.innerText || el.textContent || '').trim();
                if (!text || text.length < 2 || text.length > 120) return;
                if (isTOC(text) || isCoverMetadata(text)) return;

                // Normalize BAB chapter text (BAB 1PENDAHULUAN -> BAB 1 PENDAHULUAN)
                text = text.replace(/^(BAB\s*(?:\d+|[IVXLCDM]+))\s*([A-Za-z])/i, '$1 $2');

                const id = el.id || `heading-${headingIndex++}`;
                el.id = id;
                headings.push({
                    id,
                    level: parseInt(el.tagName.replace('H', ''), 10),
                    text,
                    pageNumber: pageNum,
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
                    header_text: this.customHeaderText,
                    footer_text: this.customFooterText,
                    paper_size: this.currentPaperSize,
                    width_mm: this.getDocumentWidthMm(),
                    height_mm: this.getDocumentHeightMm(),
                    orientation: this.currentOrientation,
                    margin_preset: this.currentMarginPreset,
                    margins: this.currentMargins,
                    line_spacing: this.currentLineSpacing,
                    paragraph_spacing: this.paragraphSpacing,
                    paragraph_indent: this.paragraphIndent,
                    columns: this.columns,
                    hyphenation: this.hyphenation,
                    tab_stops: this.tabStops,
                    default_tab_stop: this.defaultTabStop,
                    multiple_pages: this.multiplePagesMode,
                    vertical_align: this.verticalAlign,
                    watermark: this.watermark,
                    page_color: this.pageColor,
                    page_border: this.pageBorder,
                    line_numbers: this.lineNumbers,
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

        const wMm = this.getDocumentWidthMm();
        const hMm = this.getDocumentHeightMm();
        const pageSizeStr = `${wMm}mm ${hMm}mm`;
        const msoOrient = this.currentOrientation;
        const lineSpacing = this.currentLineSpacing || '1.5';
        const spaceBefore = `${this.paragraphSpacing?.before || 0}pt`;
        const spaceAfter = `${this.paragraphSpacing?.after || 6}pt`;

        let marginStr = '40mm 30mm 30mm 40mm';
        if (this.currentMargins) {
            marginStr = `${this.currentMargins.top}mm ${this.currentMargins.right}mm ${this.currentMargins.bottom}mm ${this.currentMargins.left}mm`;
        }

        if (format === 'html') {
            mime = 'text/html; charset=utf-8';
            ext = 'html';
            content = `<!DOCTYPE html><html><head><meta charset="utf-8"><title>${title}</title><style>@page { size: ${pageSizeStr}; margin: ${marginStr}; } body{font-family:'Times New Roman',serif;max-width:${wMm}mm;margin:40px auto;padding:${marginStr};line-height:${lineSpacing};}h1{text-align:center;}p{text-align:justify;margin-top:${spaceBefore};margin-bottom:${spaceAfter};line-height:${lineSpacing};text-indent:1.25cm;}</style></head><body>${htmlContent}</body></html>`;
        } else if (format === 'json') {
            mime = 'application/json; charset=utf-8';
            ext = 'json';
            content = JSON.stringify({
                title,
                content: htmlContent,
                layout: {
                    paper_size: this.currentPaperSize,
                    width_mm: wMm,
                    height_mm: hMm,
                    orientation: this.currentOrientation,
                    margin_preset: this.currentMarginPreset,
                    margins: this.currentMargins,
                    line_spacing: this.currentLineSpacing,
                    paragraph_spacing: this.paragraphSpacing,
                    paragraph_indent: this.paragraphIndent,
                    columns: this.columns,
                    hyphenation: this.hyphenation,
                    tab_stops: this.tabStops,
                    default_tab_stop: this.defaultTabStop,
                    multiple_pages: this.multiplePagesMode,
                    vertical_align: this.verticalAlign,
                    watermark: this.watermark,
                    page_color: this.pageColor,
                    page_border: this.pageBorder,
                    line_numbers: this.lineNumbers,
                },
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
            const hText = this.customHeaderText || title;
            const fText = this.customFooterText || 'Dokumen VelScribe Wordcraft';
            content = `<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'><head><meta charset='utf-8'><title>${title}</title><style>@page Section1{size:${pageSizeStr};margin:${marginStr};mso-page-orientation:${msoOrient};mso-header:h1;mso-footer:f1;}div.Section1{page:Section1;}p.MsoHeader{border-bottom:1px solid #ccc;padding-bottom:4px;font-size:10pt;color:#666;}p.MsoFooter{border-top:1px solid #ccc;padding-top:4px;font-size:10pt;color:#666;}body{font-family:'Times New Roman',serif;font-size:12pt;line-height:${lineSpacing};}h1{text-align:center;}p{text-align:justify;margin-top:${spaceBefore};margin-bottom:${spaceAfter};line-height:${lineSpacing};text-indent:1.25cm;}</style></head><body><div class="Section1"><div style="mso-element:header" id="h1"><p class="MsoHeader">${this.escapeHtml(hText)}</p></div>${htmlContent}<div style="mso-element:footer" id="f1"><p class="MsoFooter">${this.escapeHtml(fText)}</p></div></div></body></html>`;
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
                margins: this.currentMargins,
                paperSize: this.currentPaperSize,
                orientation: this.currentOrientation,
                customWidthMm: this.customWidthMm,
                customHeightMm: this.customHeightMm,
                lineSpacing: this.currentLineSpacing,
                paragraphSpacing: this.paragraphSpacing,
                paragraphIndent: this.paragraphIndent,
                columns: this.columns || 1,
                hyphenation: this.hyphenation || 'none',
                tabStops: this.tabStops || [],
                defaultTabStop: this.defaultTabStop || 1.27,
                watermark: this.watermark || null,
                pageColor: this.pageColor || '#ffffff',
                pageBorder: this.pageBorder || null,
                lineNumbers: this.lineNumbers || 'none',
                multiplePagesMode: this.multiplePagesMode || 'normal',
                verticalAlign: this.verticalAlign || 'top',
                differentOddEven: this.differentOddEven || false,
                edgeHeader: this.edgeHeader || 1.5,
                edgeFooter: this.edgeFooter || 1.5,
                customHeaderText: this.customHeaderText || '',
                customFooterText: this.customFooterText || '',
                differentFirstPage: this.differentFirstPage || false,
                pageNumberFormat: this.pageNumberFormat || 'full',
                showPageNumber: this.showPageNumber !== false,
                startPageNumber: this.startPageNumber || 1,
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
                this.customHeaderText = data.customHeaderText || '';
                this.customFooterText = data.customFooterText || '';
                this.differentFirstPage = Boolean(data.differentFirstPage);
                this.pageNumberFormat = data.pageNumberFormat || 'full';
                this.showPageNumber = data.showPageNumber !== false;
                this.startPageNumber = parseInt(data.startPageNumber, 10) || 1;
                this.setTitle(data.title || 'Dokumen Tanpa Judul');
                this.setContent(data.content);
                this.setFormatBadge(data.format || 'docx');
                if (data.paperSize) this.setPaperSize(data.paperSize, data.customWidthMm, data.customHeightMm);
                if (data.orientation) this.setOrientation(data.orientation);
                if (data.margins) this.setCustomMargins(data.margins);
                else if (data.marginPreset) this.setMarginPreset(data.marginPreset);
                if (data.lineSpacing) this.setLineSpacing(data.lineSpacing);
                if (data.paragraphSpacing) this.paragraphSpacing = data.paragraphSpacing;
                if (data.paragraphIndent) this.paragraphIndent = data.paragraphIndent;
                if (data.columns) this.setColumns(data.columns);
                if (data.hyphenation) this.setHyphenation(data.hyphenation);
                if (data.tabStops) this.setTabStops(data.tabStops, data.defaultTabStop || 1.27);
                if (data.multiplePagesMode) this.multiplePagesMode = data.multiplePagesMode;
                if (data.verticalAlign) this.verticalAlign = data.verticalAlign;
                if (data.differentOddEven !== undefined) this.differentOddEven = Boolean(data.differentOddEven);
                if (data.watermark) this.setWatermark(data.watermark);
                if (data.pageColor) this.setPageColor(data.pageColor);
                if (data.pageBorder) this.setPageBorder(data.pageBorder);
                if (data.lineNumbers) this.setLineNumbers(data.lineNumbers);
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
            if (hTitle) {
                const text = this.customHeaderText || title;
                hTitle.textContent = text;
                hTitle.title = text;
            }
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
