/**
 * VelScribe Document Engine
 * Core rich text editing, local file ingestion, auto-formatting adaptation,
 * and export handlers inspired by Microsoft Word & Google Docs.
 */

export class DocumentEngine {
    constructor(options = {}) {
        this.editorEl = options.editorEl;
        this.titleInput = options.titleInput;
        this.formatBadge = options.formatBadge;
        this.onStatsChange = options.onStatsChange || (() => {});
        this.onOutlineChange = options.onOutlineChange || (() => {});
        this.onSaveStateChange = options.onSaveStateChange || (() => {});
        this.onSelectionChange = options.onSelectionChange || (() => {});

        this.currentFormat = 'docx';
        this.history = [];
        this.historyIndex = -1;
        this.maxHistory = 50;
        this.autoSaveTimer = null;
        this.isTyping = false;

        this.init();
    }

    init() {
        if (!this.editorEl) return;

        // Save initial state to history
        this.recordHistory();

        // Editor input listeners
        this.editorEl.addEventListener('input', () => {
            this.handleInput();
        });

        // Selection listeners for active styles and bubble menu
        document.addEventListener('selectionchange', () => {
            if (this.isEditorFocused()) {
                this.onSelectionChange(this.getSelectionState());
            }
        });

        // Paste handler to clean and adapt rich text or pasted images
        this.editorEl.addEventListener('paste', (e) => {
            this.handlePaste(e);
        });

        // Keyboard shortcuts
        this.editorEl.addEventListener('keydown', (e) => {
            this.handleKeydown(e);
        });

        // Initial stats and outline update
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
        // Tab key handling for indentation / table cells
        if (e.key === 'Tab') {
            e.preventDefault();
            if (e.shiftKey) {
                document.execCommand('outdent', false, null);
            } else {
                document.execCommand('indent', false, null);
            }
            this.handleInput();
        }
    }

    handlePaste(e) {
        // Check for pasted images from clipboard
        const items = (e.clipboardData || e.originalEvent.clipboardData).items;
        for (let item of items) {
            if (item.type.indexOf('image') === 0) {
                e.preventDefault();
                const blob = item.getAsFile();
                const reader = new FileReader();
                reader.onload = (event) => {
                    this.insertImage(event.target.result, 'Gambar yang ditempel');
                };
                reader.readAsDataURL(blob);
                return;
            }
        }
    }

    /* -------------------------------------------------------------
     * Local File Ingestion & Format Adaptation
     * ------------------------------------------------------------- */

    /**
     * Ingest local file from File object, detect format, and adapt layout automatically.
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
                    const result = await window.mammoth.convertToHtml({ arrayBuffer });
                    contentHtml = result.value;
                } else {
                    // Fallback to backend API
                    contentHtml = await this.uploadToBackend(file);
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
                // Default plain text (.txt, etc.)
                detectedFormat = 'txt';
                const text = await file.text();
                const paragraphs = text.split(/\n\s*\n/);
                contentHtml = paragraphs
                    .map(p => `<p>${p.trim().replace(/\n/g, '<br>')}</p>`)
                    .join('');
            }

            // Set document title from filename if not set by JSON
            if (extension !== 'json' || !this.titleInput.value) {
                const baseTitle = filename.replace(/\.[^/.]+$/, '').replace(/[-_]/g, ' ');
                this.setTitle(this.capitalizeWords(baseTitle));
            }

            // Adapt and inject content
            this.setContent(contentHtml);
            this.setFormatBadge(detectedFormat);
            this.adaptDocumentFormat(detectedFormat, contentHtml);

            return {
                success: true,
                filename,
                format: detectedFormat,
                message: `Format ${detectedFormat.toUpperCase()} otomatis disesuaikan.`,
            };
        } catch (err) {
            console.error('Gagal membaca file lokal:', err);
            // Attempt server-side import fallback
            try {
                const result = await this.uploadToBackend(file);
                this.setContent(result.content_html);
                this.setTitle(result.title);
                this.setFormatBadge(result.format);
                this.adaptDocumentFormat(result.format, result.content_html);
                return {
                    success: true,
                    filename,
                    format: result.format,
                    message: `Format ${result.format.toUpperCase()} otomatis disesuaikan via backend.`,
                };
            } catch (backendErr) {
                throw new Error('Tidak dapat memproses berkas lokal: ' + (err.message || backendErr.message));
            }
        }
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

    /**
     * Automatic format and layout adaptation based on document content characteristics.
     */
    adaptDocumentFormat(format, htmlContent) {
        const hasWideTables = htmlContent.includes('<table') && htmlContent.length > 3000;
        const pageContainer = document.getElementById('document-page-canvas');

        if (pageContainer) {
            if (hasWideTables) {
                pageContainer.dataset.orientation = 'landscape';
                pageContainer.classList.add('page-landscape');
            } else {
                pageContainer.dataset.orientation = 'portrait';
                pageContainer.classList.remove('page-landscape');
            }
        }

        // Apply font styling preset matching document origin
        if (format === 'markdown') {
            this.editorEl.style.fontFamily = "'Instrument Sans', ui-sans-serif, system-ui, sans-serif";
            this.editorEl.style.lineHeight = '1.7';
        } else if (format === 'docx') {
            this.editorEl.style.fontFamily = "'Instrument Sans', Arial, sans-serif";
            this.editorEl.style.lineHeight = '1.5';
        } else if (format === 'txt') {
            this.editorEl.style.lineHeight = '1.6';
        }

        // Clean empty tags and normalize table borders
        this.editorEl.querySelectorAll('table').forEach(table => {
            table.classList.add('w-full', 'border-collapse', 'my-4');
            table.querySelectorAll('th, td').forEach(cell => {
                cell.classList.add('border', 'border-gray-300', 'dark:border-gray-700', 'p-2', 'text-left');
            });
            table.querySelectorAll('th').forEach(th => {
                th.classList.add('bg-gray-50', 'dark:bg-gray-800', 'font-semibold');
            });
        });

        this.updateStats();
        this.updateOutline();
        this.recordHistory();
    }

    /* -------------------------------------------------------------
     * Document Editing Commands (Ribbon & Bubble)
     * ------------------------------------------------------------- */

    execute(command, value = null) {
        this.editorEl.focus();
        document.execCommand(command, false, value);
        this.handleInput();
    }

    setFontFamily(fontName) {
        this.execute('fontName', fontName);
    }

    setFontSize(ptSize) {
        // Use span with style or fontSize
        this.editorEl.focus();
        const sel = window.getSelection();
        if (sel && sel.rangeCount > 0 && !sel.isCollapsed) {
            const span = document.createElement('span');
            span.style.fontSize = ptSize;
            span.appendChild(sel.getRangeAt(0).extractContents());
            sel.getRangeAt(0).insertNode(span);
            this.handleInput();
        } else {
            this.execute('fontSize', '3');
        }
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

    insertImage(src, alt = '') {
        if (!src) return;
        this.editorEl.focus();
        const figure = document.createElement('figure');
        figure.className = 'my-4 text-center inline-block max-w-full';

        const img = document.createElement('img');
        img.src = src;
        img.alt = alt;
        img.className = 'max-w-full h-auto rounded-lg shadow-sm mx-auto cursor-pointer border border-gray-200 dark:border-gray-700';

        figure.appendChild(img);
        if (alt) {
            const figcaption = document.createElement('figcaption');
            figcaption.className = 'text-xs text-gray-500 mt-1 italic';
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

    insertCallout(type = 'info', title = 'Catatan', message = 'Masukkan catatan penting Anda di sini.') {
        this.editorEl.focus();
        const callout = document.createElement('div');
        let borderClass = 'border-blue-500 bg-blue-50 dark:bg-blue-950/40 text-blue-900 dark:text-blue-200';
        let icon = 'ℹ️';

        if (type === 'warning') {
            borderClass = 'border-amber-500 bg-amber-50 dark:bg-amber-950/40 text-amber-900 dark:text-amber-200';
            icon = '⚠️';
        } else if (type === 'success') {
            borderClass = 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-900 dark:text-emerald-200';
            icon = '✅';
        }

        callout.className = `p-4 my-4 rounded-lg border-l-4 ${borderClass}`;
        callout.innerHTML = `
            <div class="font-bold flex items-center gap-2 mb-1">
                <span>${icon}</span>
                <span>${title}</span>
            </div>
            <p class="text-sm m-0">${message}</p>
        `;

        const sel = window.getSelection();
        if (sel && sel.rangeCount > 0) {
            sel.getRangeAt(0).insertNode(callout);
        } else {
            this.editorEl.appendChild(callout);
        }
        this.handleInput();
    }

    insertPageBreak() {
        this.editorEl.focus();
        const pb = document.createElement('div');
        pb.className = 'page-break-indicator my-6 border-b-2 border-dashed border-gray-400 dark:border-gray-600 flex items-center justify-center text-xs text-gray-500';
        pb.setAttribute('data-page-break', 'true');
        pb.innerHTML = '<span class="bg-gray-100 dark:bg-gray-800 px-3 py-1 rounded-full uppercase tracking-wider">Pemisah Halaman (Page Break)</span>';

        const sel = window.getSelection();
        if (sel && sel.rangeCount > 0) {
            sel.getRangeAt(0).insertNode(pb);
        } else {
            this.editorEl.appendChild(pb);
        }
        this.handleInput();
    }

    insertCurrentDateTime() {
        const now = new Date();
        const formatted = now.toLocaleDateString('id-ID', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
        this.execute('insertText', formatted);
    }

    /* -------------------------------------------------------------
     * Selection & State Queries
     * ------------------------------------------------------------- */

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
            isListUl: document.queryCommandState('insertUnorderedList'),
            isListOl: document.queryCommandState('insertOrderedList'),
        };
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

    /* -------------------------------------------------------------
     * Undo / Redo History Stack
     * ------------------------------------------------------------- */

    recordHistory() {
        const content = this.getContent();
        if (this.history[this.historyIndex] === content) return;

        // Cut off any redo states if we made a new action
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

    /* -------------------------------------------------------------
     * Document Statistics & Dynamic Outline
     * ------------------------------------------------------------- */

    updateStats() {
        const text = this.editorEl.innerText || '';
        const cleanText = text.trim();

        const charCount = cleanText.length;
        const words = cleanText ? cleanText.split(/\s+/).filter(Boolean) : [];
        const wordCount = words.length;

        const paragraphs = cleanText ? cleanText.split(/\n\s*\n/).filter(Boolean) : [];
        const paragraphCount = paragraphs.length;

        const readingTimeMinutes = Math.max(1, Math.ceil(wordCount / 200));

        // Estimate pages (standard 300 words per A4 page)
        const pageCount = Math.max(1, Math.ceil(wordCount / 300));

        this.onStatsChange({
            wordCount,
            charCount,
            paragraphCount,
            readingTimeMinutes,
            readingTime: `${readingTimeMinutes} min`,
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

    /* -------------------------------------------------------------
     * Export Generators
     * ------------------------------------------------------------- */

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
            console.error('Ekspor via API gagal, menjalankan ekspor sisi klien:', err);
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
            content = `<!DOCTYPE html><html><head><meta charset="utf-8"><title>${title}</title><style>body{font-family:sans-serif;max-width:800px;margin:40px auto;padding:0 20px;line-height:1.6;}</style></head><body><h1>${title}</h1>${htmlContent}</body></html>`;
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
            // DOCX / HTML wrapper
            mime = 'application/msword; charset=utf-8';
            ext = 'doc';
            content = `<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'><head><meta charset='utf-8'><title>${title}</title></head><body><h1>${title}</h1>${htmlContent}</body></html>`;
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

    /* -------------------------------------------------------------
     * Persistence & Helpers
     * ------------------------------------------------------------- */

    saveToLocalStorage() {
        try {
            const data = {
                title: this.getTitle(),
                content: this.getContent(),
                format: this.currentFormat,
                savedAt: new Date().toISOString(),
            };
            localStorage.setItem('velscribe_document_state', JSON.stringify(data));
        } catch (e) {
            console.warn('LocalStorage save failed:', e);
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
                return true;
            }
        } catch (e) {
            console.warn('LocalStorage load failed:', e);
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
