/**
 * VelScribe Document Engine
 * Comprehensive Word Processor Engine inspired by Microsoft Word & Google Documents.
 * Features:
 * - Intelligent local file ingestion (.docx, .md, .html, .txt, .json)
 * - Automatic document structure adaptation (Academic/Skripsi 4-4-3-3 margins, Times New Roman 12pt, justified text)
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
        this.history = [];
        this.historyIndex = -1;
        this.maxHistory = 50;
        this.autoSaveTimer = null;
        this.isTyping = false;
        this.activeTable = null;
        this.activeImage = null;

        this.init();
    }

    init() {
        if (!this.editorEl) return;

        // Record initial state
        this.recordHistory();

        // Editor input listeners
        this.editorEl.addEventListener('input', () => {
            this.handleInput();
        });

        // Selection listeners
        document.addEventListener('selectionchange', () => {
            if (this.isEditorFocused()) {
                this.onSelectionChange(this.getSelectionState());
            }
        });

        // Click listeners for tables and images to show context toolbars
        this.editorEl.addEventListener('click', (e) => {
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
        });

        // Paste handler
        this.editorEl.addEventListener('paste', (e) => {
            this.handlePaste(e);
        });

        // Keyboard shortcuts
        this.editorEl.addEventListener('keydown', (e) => {
            this.handleKeydown(e);
        });

        // Initial stats and outline
        this.updateStats();
        this.updateOutline();
    }

    isEditorFocused() {
        const sel = window.getSelection();
        if (!sel || sel.rangeCount === 0) return false;
        return this.editorEl.contains(sel.anchorNode);
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
     * Local File Ingestion & Intelligent Auto-Adaptation
     * ------------------------------------------------------------- */

    /**
     * Ingest local file, detect format, and adapt layout, headings, and margins automatically.
     */
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
                    message: `Format Skripsi Terdeteksi: Margin 4-4-3-3 cm, Font Times New Roman 12pt, Spasi 1.5, dan ${adapted.headingCount} Bab/Subbab berhasil diselaraskan.`,
                });
            } else {
                this.setMarginPreset('normal');
                this.onAdaptationNotice({
                    type: 'normal',
                    message: `Format ${detectedFormat.toUpperCase()} berhasil dimuat dan diselaraskan ke dalam kanvas A4.`,
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

    /**
     * Semantic document structure & formatting adapter.
     * Identifies academic skripsi patterns, centers cover page, promotes chapters/subheadings,
     * and splits pages cleanly.
     */
    adaptDocumentStructure(html, filename = '') {
        const parser = new DOMParser();
        const doc = parser.parseFromString(`<div>${html}</div>`, 'text/html');
        const container = doc.body.firstElementChild;

        const allText = container.textContent.toLowerCase();
        const isAcademic =
            allText.includes('skripsi') ||
            allText.includes('tesis') ||
            allText.includes('disertasi') ||
            allText.includes('politeknik') ||
            allText.includes('universitas') ||
            allText.includes('nim.') ||
            allText.includes('bab i') ||
            filename.toLowerCase().includes('skripsi');

        let inCover = isAcademic;
        let coverCount = 0;
        let headingCount = 0;

        // Process all direct children
        const children = Array.from(container.children);
        for (let el of children) {
            const text = el.textContent.trim();

            // Check if we hit a chapter heading or preliminary section, which ends cover page immediately
            if (inCover && (
                /^(BAB\s+[IVXLCDM]+|\bBAB\s+\d+)/i.test(text) ||
                /^(ABSTRAK|ABSTRACT|KATA\s+PENGANTAR|PRAKATA|LEMBAR\s+PENGESAHAN|HALAMAN\s+PENGESAHAN|PERNYATAAN\s+KEASLIAN|PERNYATAAN\s+ORISINALITAS|DAFTAR\s+ISI|DAFTAR\s+TABEL|DAFTAR\s+GAMBAR|DAFTAR\s+PUSTAKA)/i.test(text)
            )) {
                inCover = false;
                const pb = doc.createElement('div');
                pb.className = 'page-break my-8 border-b-2 border-dashed border-gray-400 dark:border-gray-600 text-center py-2 select-none';
                pb.setAttribute('data-page-break', 'true');
                pb.innerHTML = '<span class="bg-gray-100 dark:bg-gray-800 px-3 py-1 rounded-full text-xs text-gray-500 font-mono">Pemisah Halaman (Akhir Sampul)</span>';
                el.before(pb);
            }

            // Cover Page centering
            if (inCover) {
                coverCount++;

                // If image in cover (e.g. university logo), center and size nicely
                const img = el.querySelector('img') || (el.tagName === 'IMG' ? el : null);
                if (img) {
                    img.className = 'mx-auto block my-6 max-h-44 object-contain';
                    el.className = 'text-center my-4';
                } else if (text) {
                    el.className = 'text-center font-bold text-gray-900 dark:text-gray-100 my-2 leading-snug';
                }

                // Year detection or cover length limit: end cover page
                if (/\b(19|20)\d{2}\b/.test(text) || coverCount > 14) {
                    inCover = false;
                    const pb = doc.createElement('div');
                    pb.className = 'page-break my-8 border-b-2 border-dashed border-gray-400 dark:border-gray-600 text-center py-2 select-none';
                    pb.setAttribute('data-page-break', 'true');
                    pb.innerHTML = '<span class="bg-gray-100 dark:bg-gray-800 px-3 py-1 rounded-full text-xs text-gray-500 font-mono">Pemisah Halaman (Akhir Sampul)</span>';
                    el.after(pb);
                }
                continue;
            }

            // Chapter Headings: BAB I, BAB II, etc. (Handles single line and two-line chapter title)
            const babMatch = text.match(/^(BAB\s+[IVXLCDM]+|\bBAB\s+\d+)(.*)$/i);
            if (babMatch) {
                headingCount++;
                let titleText = text;
                const nextEl = el.nextElementSibling;
                // If chapter title is on the next line (e.g. BAB I followed by PENDAHULUAN)
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

                // Add page break before chapter if not already at page break
                const pb = doc.createElement('div');
                pb.className = 'page-break my-6 border-b border-dashed border-gray-300 dark:border-gray-700 text-center py-1 select-none';
                pb.setAttribute('data-page-break', 'true');
                pb.innerHTML = '<span class="text-[10px] text-gray-400 font-mono">Awal Bab Baru</span>';

                el.before(pb);
                el.replaceWith(h1);
                continue;
            }

            // Academic Preliminaries: ABSTRAK, DAFTAR ISI, KATA PENGANTAR, LEMBAR PENGESAHAN, etc.
            if (/^(SKRIPSI|ABSTRAK|ABSTRACT|KATA\s+PENGANTAR|PRAKATA|LEMBAR\s+PENGESAHAN|HALAMAN\s+PENGESAHAN|PERNYATAAN\s+KEASLIAN|PERNYATAAN\s+ORISINALITAS|DAFTAR\s+ISI|DAFTAR\s+TABEL|DAFTAR\s+GAMBAR|DAFTAR\s+LAMPIRAN|DAFTAR\s+PUSTAKA|RIWAYAT\s+HIDUP|BIODATA\s+PENULIS|LAMPIRAN)$/i.test(text)) {
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
            if (/^(\d+\.\d+)\s+(.+)$/.test(text)) {
                headingCount++;
                const h2 = doc.createElement('h2');
                h2.className = 'heading-section font-bold text-lg my-4 text-gray-900 dark:text-gray-100';
                h2.innerHTML = el.innerHTML;
                el.replaceWith(h2);
                continue;
            }

            // Sub-subsections: 1.1.1, 2.3.1
            if (/^(\d+\.\d+\.\d+)\s+(.+)$/.test(text)) {
                headingCount++;
                const h3 = doc.createElement('h3');
                h3.className = 'heading-subsection font-semibold text-base my-3 text-gray-800 dark:text-gray-200';
                h3.innerHTML = el.innerHTML;
                el.replaceWith(h3);
                continue;
            }

            // Standalone short uppercase lines
            if (text.length > 3 && text.length < 70 && text === text.toUpperCase() && !/[.,;]$/.test(text) && !el.querySelector('img')) {
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
        const canvas = this.canvasEl || document.getElementById('document-page-canvas');
        if (!canvas) return;

        // Apply margins to canvas container
        if (preset === 'skripsi') {
            // Skripsi standard: Top 4cm, Left 4cm, Right 3cm, Bottom 3cm
            canvas.style.paddingTop = '40mm';
            canvas.style.paddingLeft = '40mm';
            canvas.style.paddingRight = '30mm';
            canvas.style.paddingBottom = '30mm';
            this.updateRulerMarkers(4, 18);
        } else if (preset === 'narrow') {
            // Sempit: 1.27cm
            canvas.style.padding = '12.7mm';
            this.updateRulerMarkers(1.3, 19.7);
        } else if (preset === 'wide') {
            // Lebar: 3.81cm left/right, 2.54cm top/bottom
            canvas.style.paddingTop = '25.4mm';
            canvas.style.paddingBottom = '25.4mm';
            canvas.style.paddingLeft = '38.1mm';
            canvas.style.paddingRight = '38.1mm';
            this.updateRulerMarkers(3.8, 17.2);
        } else {
            // Standar Normal: 2.54cm (1 inch)
            canvas.style.padding = '25.4mm';
            this.updateRulerMarkers(2.5, 18.5);
        }

        // Sync with layout margin dropdown if present
        const marginSelect = document.getElementById('layout-margins');
        if (marginSelect) {
            marginSelect.value = preset;
        }
    }

    updateRulerMarkers(leftCm, rightCm) {
        const leftMarker = document.getElementById('ruler-left-margin');
        const rightMarker = document.getElementById('ruler-right-margin');

        // A4 width = 21cm
        if (leftMarker) {
            leftMarker.style.left = `${(leftCm / 21) * 100}%`;
        }
        if (rightMarker) {
            rightMarker.style.left = `${(rightCm / 21) * 100}%`;
        }
    }

    setFontFamily(fontName) {
        this.editorEl.style.fontFamily = fontName;
        this.execute('fontName', fontName);

        const fontSelect = document.getElementById('font-family-select');
        if (fontSelect) {
            for (let i = 0; i < fontSelect.options.length; i++) {
                if (fontSelect.options[i].value.includes(fontName) || fontName.includes(fontSelect.options[i].text) || fontName.includes('Times') && fontSelect.options[i].value.includes('Times')) {
                    fontSelect.selectedIndex = i;
                    break;
                }
            }
        }
    }

    setFontSize(ptSize) {
        this.editorEl.focus();
        const sel = window.getSelection();
        if (sel && sel.rangeCount > 0 && !sel.isCollapsed) {
            const span = document.createElement('span');
            span.style.fontSize = ptSize;
            span.appendChild(sel.getRangeAt(0).extractContents());
            sel.getRangeAt(0).insertNode(span);
            this.handleInput();
        } else {
            this.editorEl.style.fontSize = ptSize;
        }

        const sizeSelect = document.getElementById('font-size-select');
        if (sizeSelect) {
            sizeSelect.value = ptSize;
        }
    }

    setLineSpacing(val) {
        this.editorEl.style.lineHeight = val;
        this.editorEl.querySelectorAll('p').forEach(p => {
            p.style.lineHeight = val;
        });

        const lineSelect = document.getElementById('line-spacing-select');
        if (lineSelect) {
            lineSelect.value = val;
        }
        this.handleInput();
    }

    setTextAlignment(align) {
        this.editorEl.focus();
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
        const headings = this.editorEl.querySelectorAll('h1, h2, h3');
        if (headings.length === 0) {
            return false;
        }

        let pageCounter = 1;
        const tocEntries = [];

        headings.forEach((h, index) => {
            const text = h.textContent.trim();
            const level = h.tagName.toLowerCase();
            const id = h.id || `heading-${index}`;
            h.id = id;

            // Simple heuristic page count estimate based on DOM position
            if (h.classList.contains('heading-chapter') || h.textContent.toLowerCase().includes('bab')) {
                pageCounter = Math.max(pageCounter + 1, index * 2 + 1);
            }

            const indentClass = level === 'h1' ? 'font-bold mt-2' :
                                level === 'h2' ? 'pl-4 text-sm' :
                                'pl-8 text-xs text-gray-600 dark:text-gray-400';

            tocEntries.push(`
                <div class="flex items-baseline justify-between py-1 ${indentClass}">
                    <a href="#${id}" class="hover:text-indigo-600 hover:underline truncate max-w-[80%]">${text}</a>
                    <span class="flex-1 mx-2 border-b border-dotted border-gray-400 dark:border-gray-600"></span>
                    <span class="font-mono text-xs">${pageCounter}</span>
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
        this.editorEl.focus();
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
        this.editorEl.focus();
        const pb = document.createElement('div');
        pb.className = 'page-break my-8 border-b-2 border-dashed border-gray-400 dark:border-gray-600 flex items-center justify-center text-xs text-gray-500 select-none';
        pb.setAttribute('data-page-break', 'true');
        pb.innerHTML = '<span class="bg-gray-100 dark:bg-gray-800 px-3 py-1 rounded-full uppercase tracking-wider font-mono">Pemisah Halaman (Page Break)</span>';

        const sel = window.getSelection();
        if (sel && sel.rangeCount > 0) {
            sel.getRangeAt(0).insertNode(pb);
        } else {
            this.editorEl.appendChild(pb);
        }
        this.handleInput();
    }

    insertImage(src, alt = '') {
        if (!src) return;
        this.editorEl.focus();
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
        if (sel && sel.rangeCount > 0) {
            sel.getRangeAt(0).insertNode(figure);
        } else {
            this.editorEl.appendChild(figure);
        }
        this.handleInput();
    }

    insertTable(rows = 3, cols = 3) {
        this.editorEl.focus();
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
        if (sel && sel.rangeCount > 0) {
            sel.getRangeAt(0).insertNode(table);
        } else {
            this.editorEl.appendChild(table);
        }
        this.handleInput();
    }

    insertLink(url, text = null) {
        if (!url) return;
        this.editorEl.focus();
        const sel = window.getSelection();
        if (sel && sel.isCollapsed && text) {
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
        this.editorEl.focus();
        const sel = window.getSelection();
        if (sel && sel.rangeCount > 0) {
            const range = sel.getRangeAt(0);
            const temp = document.createElement('div');
            temp.innerHTML = htmlText;
            const frag = document.createDocumentFragment();
            while (temp.firstChild) {
                frag.appendChild(temp.firstChild);
            }
            range.insertNode(frag);
        } else {
            this.editorEl.innerHTML += htmlText;
        }
        this.handleInput();
    }

    replaceSelectedText(newText) {
        this.editorEl.focus();
        const sel = window.getSelection();
        if (!sel || sel.rangeCount === 0) return;
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
        const text = this.editorEl.innerText || '';
        const cleanText = text.trim();

        const charCount = cleanText.length;
        const words = cleanText ? cleanText.split(/\s+/).filter(Boolean) : [];
        const wordCount = words.length;

        const paragraphs = cleanText ? cleanText.split(/\n\s*\n/).filter(Boolean) : [];
        const paragraphCount = paragraphs.length;

        // Sentences count
        const sentences = cleanText ? cleanText.split(/[.!?]+/).filter(Boolean) : [];
        const sentenceCount = sentences.length;

        const readingTimeMinutes = Math.max(1, Math.ceil(wordCount / 200));

        // Count real page breaks or calculate based on words
        const pageBreaks = this.editorEl.querySelectorAll('[data-page-break]').length;
        const pageCount = Math.max(pageBreaks + 1, Math.max(1, Math.ceil(wordCount / 300)));

        this.onStatsChange({
            wordCount,
            charCount,
            sentenceCount,
            paragraphCount,
            readingTimeMinutes,
            readingTime: `${readingTimeMinutes} min baca`,
            pageCount,
        });
    }

    updateOutline() {
        const headings = [];
        const headingEls = this.editorEl.querySelectorAll('h1, h2, h3, h4');

        headingEls.forEach((el, index) => {
            const id = el.id || `heading-${index}`;
            el.id = id;
            headings.push({
                id,
                level: parseInt(el.tagName.replace('H', ''), 10),
                text: el.innerText.trim(),
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
            content = `${title}\n\n${this.editorEl.innerText}`;
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
    }

    getContent() {
        return this.editorEl ? this.editorEl.innerHTML : '';
    }

    setContent(html, record = true) {
        if (this.editorEl) {
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
}
