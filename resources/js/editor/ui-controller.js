/**
 * VelScribe UI Controller
 * Manages Microsoft Word-style Ribbon tabs, Google Docs-style Outline and Bubble Menu,
 * Zoom, Rulers, Modals, Drag-and-Drop, and Keyboard Shortcuts.
 */

export class UiController {
    constructor(engine, wordcraftAi) {
        this.engine = engine;
        this.wordcraft = wordcraftAi;

        this.currentZoom = 100;
        this.layoutMode = 'print'; // 'print' | 'web' | 'zen'
        this.isRulerVisible = true;
        this.isOutlineVisible = true;
        this.isWordcraftVisible = true;

        this.init();
    }

    init() {
        this.bindRibbonTabs();
        this.bindRibbonTools();
        this.bindBubbleMenu();
        this.bindZoomControls();
        this.bindLayoutModeSwitchers();
        this.bindModals();
        this.bindDragAndDrop();
        this.bindGlobalKeyboardShortcuts();
        this.renderRuler();
    }

    /* -------------------------------------------------------------
     * Ribbon Tabs Switching
     * ------------------------------------------------------------- */

    bindRibbonTabs() {
        const tabBtns = document.querySelectorAll('[data-ribbon-tab]');
        const tabPanels = document.querySelectorAll('[data-ribbon-panel]');

        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const targetTab = btn.getAttribute('data-ribbon-tab');

                tabBtns.forEach(b => {
                    b.classList.remove('active', 'border-indigo-600', 'text-indigo-600', 'dark:border-indigo-400', 'dark:text-indigo-400', 'font-semibold');
                    b.classList.add('border-transparent', 'text-gray-600', 'dark:text-gray-400');
                });
                btn.classList.add('active', 'border-indigo-600', 'text-indigo-600', 'dark:border-indigo-400', 'dark:text-indigo-400', 'font-semibold');
                btn.classList.remove('border-transparent', 'text-gray-600', 'dark:text-gray-400');

                tabPanels.forEach(p => {
                    if (p.getAttribute('data-ribbon-panel') === targetTab) {
                        p.classList.remove('hidden');
                        p.classList.add('flex');
                    } else {
                        p.classList.add('hidden');
                        p.classList.remove('flex');
                    }
                });

                // If AI tab is clicked, also open the Wordcraft sidebar if closed
                if (targetTab === 'ai') {
                    this.toggleWordcraftSidebar(true);
                }
            });
        });
    }

    /* -------------------------------------------------------------
     * Ribbon Tools & Action Buttons
     * ------------------------------------------------------------- */

    bindRibbonTools() {
        // Undo / Redo
        document.querySelectorAll('[data-action="undo"]').forEach(b => b.addEventListener('click', () => this.engine.undo()));
        document.querySelectorAll('[data-action="redo"]').forEach(b => b.addEventListener('click', () => this.engine.redo()));

        // Basic formatting
        document.querySelectorAll('[data-cmd]').forEach(b => {
            b.addEventListener('click', () => {
                const cmd = b.getAttribute('data-cmd');
                const val = b.getAttribute('data-val') || null;
                this.engine.execute(cmd, val);
            });
        });

        // Font family
        const fontSelect = document.getElementById('font-family-select');
        if (fontSelect) {
            fontSelect.addEventListener('change', (e) => {
                this.engine.setFontFamily(e.target.value);
            });
        }

        // Font size
        const fontSizeSelect = document.getElementById('font-size-select');
        if (fontSizeSelect) {
            fontSizeSelect.addEventListener('change', (e) => {
                this.engine.setFontSize(e.target.value);
            });
        }

        // Text Color & Highlight Color
        const textColorInput = document.getElementById('text-color-picker');
        if (textColorInput) {
            textColorInput.addEventListener('input', (e) => this.engine.setTextColor(e.target.value));
        }

        const highlightColorInput = document.getElementById('highlight-color-picker');
        if (highlightColorInput) {
            highlightColorInput.addEventListener('input', (e) => this.engine.setHighlightColor(e.target.value));
        }

        // Clear formatting
        const clearFormatBtn = document.getElementById('btn-clear-format');
        if (clearFormatBtn) {
            clearFormatBtn.addEventListener('click', () => this.engine.clearFormatting());
        }

        // Headings dropdown
        const headingSelect = document.getElementById('heading-preset-select');
        if (headingSelect) {
            headingSelect.addEventListener('change', (e) => {
                this.engine.setHeading(e.target.value);
                e.target.value = '';
            });
        }

        // Page Layout tab settings
        const paperSizeSelect = document.getElementById('layout-paper-size');
        if (paperSizeSelect) {
            paperSizeSelect.addEventListener('change', (e) => {
                const canvas = document.getElementById('document-page-canvas');
                if (canvas) {
                    canvas.classList.remove('page-a4', 'page-letter', 'page-legal');
                    canvas.classList.add(`page-${e.target.value}`);
                }
            });
        }

        const orientationSelect = document.getElementById('layout-orientation');
        if (orientationSelect) {
            orientationSelect.addEventListener('change', (e) => {
                const canvas = document.getElementById('document-page-canvas');
                if (canvas) {
                    if (e.target.value === 'landscape') {
                        canvas.classList.add('page-landscape');
                    } else {
                        canvas.classList.remove('page-landscape');
                    }
                }
            });
        }

        const marginsSelect = document.getElementById('layout-margins');
        if (marginsSelect) {
            marginsSelect.addEventListener('change', (e) => {
                const editor = this.engine.editorEl;
                if (!editor) return;
                const val = e.target.value;
                if (val === 'narrow') {
                    editor.style.padding = '12mm 15mm';
                } else if (val === 'wide') {
                    editor.style.padding = '35mm 30mm';
                } else if (val === 'none') {
                    editor.style.padding = '5mm 5mm';
                } else {
                    editor.style.padding = '25mm 25mm';
                }
            });
        }

        // View Toggles
        const toggleRulerBtn = document.getElementById('btn-toggle-ruler');
        if (toggleRulerBtn) {
            toggleRulerBtn.addEventListener('click', () => {
                this.isRulerVisible = !this.isRulerVisible;
                const ruler = document.getElementById('ruler-bar');
                if (ruler) ruler.classList.toggle('hidden', !this.isRulerVisible);
            });
        }

        const toggleOutlineBtn = document.getElementById('btn-toggle-outline');
        if (toggleOutlineBtn) {
            toggleOutlineBtn.addEventListener('click', () => {
                this.isOutlineVisible = !this.isOutlineVisible;
                const sidebar = document.getElementById('outline-sidebar');
                if (sidebar) sidebar.classList.toggle('hidden', !this.isOutlineVisible);
            });
        }

        const toggleAiBtn = document.getElementById('btn-toggle-ai');
        if (toggleAiBtn) {
            toggleAiBtn.addEventListener('click', () => {
                this.toggleWordcraftSidebar();
            });
        }
    }

    toggleWordcraftSidebar(forceOpen = null) {
        const sidebar = document.getElementById('wordcraft-sidebar');
        if (!sidebar) return;

        if (forceOpen !== null) {
            this.isWordcraftVisible = forceOpen;
        } else {
            this.isWordcraftVisible = !this.isWordcraftVisible;
        }

        sidebar.classList.toggle('hidden', !this.isWordcraftVisible);
    }

    /* -------------------------------------------------------------
     * Floating Bubble Menu (Google Docs style)
     * ------------------------------------------------------------- */

    bindBubbleMenu() {
        const bubble = document.getElementById('bubble-menu');
        if (!bubble) return;

        this.engine.onSelectionChange = (state) => {
            if (state.hasSelection && state.rect && state.text.trim().length > 0) {
                const editorRect = this.engine.editorEl.getBoundingClientRect();
                const bubbleRect = bubble.getBoundingClientRect();

                const top = Math.max(10, state.rect.top - bubbleRect.height - 10);
                const left = Math.max(10, Math.min(window.innerWidth - bubbleRect.width - 20, state.rect.left + (state.rect.width / 2) - (bubbleRect.width / 2)));

                bubble.style.top = `${top}px`;
                bubble.style.left = `${left}px`;
                bubble.classList.remove('hidden');
                bubble.classList.add('flex');
            } else {
                bubble.classList.add('hidden');
                bubble.classList.remove('flex');
            }
        };

        // Quick ask AI from bubble
        const bubbleAiBtn = bubble.querySelector('#bubble-ask-ai');
        if (bubbleAiBtn) {
            bubbleAiBtn.addEventListener('click', () => {
                this.toggleWordcraftSidebar(true);
                if (this.wordcraft) {
                    this.wordcraft.executeAction('elaborate');
                }
            });
        }
    }

    /* -------------------------------------------------------------
     * Zoom & Canvas Scaling
     * ------------------------------------------------------------- */

    bindZoomControls() {
        const zoomSlider = document.getElementById('zoom-slider');
        const zoomLabel = document.getElementById('zoom-label');
        const zoomInBtn = document.getElementById('btn-zoom-in');
        const zoomOutBtn = document.getElementById('btn-zoom-out');
        const canvas = document.getElementById('document-page-canvas');

        const applyZoom = (val) => {
            this.currentZoom = Math.min(200, Math.max(50, val));
            if (zoomSlider) zoomSlider.value = this.currentZoom;
            if (zoomLabel) zoomLabel.textContent = `${this.currentZoom}%`;
            if (canvas) {
                canvas.style.transform = `scale(${this.currentZoom / 100})`;
                canvas.style.transformOrigin = 'top center';
            }
        };

        if (zoomSlider) {
            zoomSlider.addEventListener('input', (e) => applyZoom(parseInt(e.target.value, 10)));
        }
        if (zoomInBtn) {
            zoomInBtn.addEventListener('click', () => applyZoom(this.currentZoom + 10));
        }
        if (zoomOutBtn) {
            zoomOutBtn.addEventListener('click', () => applyZoom(this.currentZoom - 10));
        }
    }

    /* -------------------------------------------------------------
     * Layout Mode Switchers (Print A4, Web Flow, Zen Mode)
     * ------------------------------------------------------------- */

    bindLayoutModeSwitchers() {
        const canvas = document.getElementById('document-page-canvas');
        const ribbon = document.getElementById('ribbon-bar');
        const topNav = document.getElementById('top-nav');
        const ruler = document.getElementById('ruler-bar');
        const outline = document.getElementById('outline-sidebar');
        const wordcraft = document.getElementById('wordcraft-sidebar');

        const setMode = (mode) => {
            this.layoutMode = mode;

            document.querySelectorAll('[data-layout-mode]').forEach(btn => {
                const active = btn.getAttribute('data-layout-mode') === mode;
                btn.classList.toggle('text-indigo-600', active);
                btn.classList.toggle('font-bold', active);
            });

            if (mode === 'print') {
                if (canvas) {
                    canvas.classList.add('page-a4', 'shadow-2xl', 'rounded-sm');
                    canvas.classList.remove('w-full', 'max-w-4xl', 'shadow-none');
                }
                if (ribbon) ribbon.classList.remove('hidden');
                if (topNav) topNav.classList.remove('hidden');
                if (ruler) ruler.classList.toggle('hidden', !this.isRulerVisible);
            } else if (mode === 'web') {
                if (canvas) {
                    canvas.classList.remove('page-a4', 'shadow-2xl');
                    canvas.classList.add('w-full', 'max-w-4xl', 'shadow-sm');
                }
                if (ribbon) ribbon.classList.remove('hidden');
                if (topNav) topNav.classList.remove('hidden');
                if (ruler) ruler.classList.add('hidden');
            } else if (mode === 'zen') {
                // Focus / Zen Mode
                if (canvas) {
                    canvas.classList.remove('page-a4', 'shadow-2xl');
                    canvas.classList.add('w-full', 'max-w-3xl', 'shadow-none');
                }
                if (ribbon) ribbon.classList.add('hidden');
                if (ruler) ruler.classList.add('hidden');
                if (outline) outline.classList.add('hidden');
                if (wordcraft) wordcraft.classList.add('hidden');
                this.showToast('Mode Zen aktif. Tekan Escape atau klik tombol keluar untuk kembali.');
            }
        };

        document.querySelectorAll('[data-layout-mode]').forEach(btn => {
            btn.addEventListener('click', () => setMode(btn.getAttribute('data-layout-mode')));
        });

        // Exit zen mode on Escape
        window.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.layoutMode === 'zen') {
                setMode('print');
            }
        });
    }

    /* -------------------------------------------------------------
     * Modals (Open File, Find & Replace, Link, Table, Image, Export)
     * ------------------------------------------------------------- */

    bindModals() {
        // Open Local File Modal
        const openFileModal = document.getElementById('modal-open-file');
        const openFileBtns = document.querySelectorAll('[data-action="open-file-modal"]');
        const fileInput = document.getElementById('local-file-input');

        openFileBtns.forEach(b => b.addEventListener('click', () => this.openModal(openFileModal)));

        if (fileInput) {
            fileInput.addEventListener('change', async (e) => {
                if (e.target.files.length > 0) {
                    const file = e.target.files[0];
                    this.closeModal(openFileModal);
                    this.showToast(`Memuat ${file.name} dan menyesuaikan format...`);
                    try {
                        const res = await this.engine.loadLocalFile(file);
                        this.showToast(res.message);
                    } catch (err) {
                        this.showToast('Gagal memuat dokumen: ' + err.message, 'error');
                    }
                }
            });
        }

        // Find & Replace Modal
        const findModal = document.getElementById('modal-find-replace');
        const findBtns = document.querySelectorAll('[data-action="find-replace"]');
        findBtns.forEach(b => b.addEventListener('click', () => this.openModal(findModal)));

        const findInput = document.getElementById('find-search-input');
        const replaceInput = document.getElementById('find-replace-input');
        const btnReplaceAll = document.getElementById('btn-replace-all');

        if (btnReplaceAll && findInput && replaceInput) {
            btnReplaceAll.addEventListener('click', () => {
                const query = findInput.value;
                const replacement = replaceInput.value;
                if (!query) return;

                const content = this.engine.getContent();
                const regex = new RegExp(query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'g');
                const count = (content.match(regex) || []).length;

                if (count > 0) {
                    const updated = content.replace(regex, replacement);
                    this.engine.setContent(updated);
                    this.closeModal(findModal);
                    this.showToast(`Berhasil mengganti ${count} kata "${query}"!`);
                } else {
                    this.showToast(`Kata "${query}" tidak ditemukan dalam dokumen.`, 'warning');
                }
            });
        }

        // Insert Link Modal
        const linkModal = document.getElementById('modal-insert-link');
        const insertLinkBtns = document.querySelectorAll('[data-action="insert-link-modal"]');
        insertLinkBtns.forEach(b => b.addEventListener('click', () => this.openModal(linkModal)));

        const btnConfirmLink = document.getElementById('btn-confirm-link');
        const linkUrlInput = document.getElementById('link-url-input');
        const linkTextInput = document.getElementById('link-text-input');

        if (btnConfirmLink && linkUrlInput) {
            btnConfirmLink.addEventListener('click', () => {
                const url = linkUrlInput.value.trim();
                const text = linkTextInput ? linkTextInput.value.trim() : null;
                if (url) {
                    this.engine.insertLink(url, text);
                    this.closeModal(linkModal);
                    linkUrlInput.value = '';
                    if (linkTextInput) linkTextInput.value = '';
                }
            });
        }

        // Insert Table Modal
        const tableModal = document.getElementById('modal-insert-table');
        const insertTableBtns = document.querySelectorAll('[data-action="insert-table-modal"]');
        insertTableBtns.forEach(b => b.addEventListener('click', () => this.openModal(tableModal)));

        const btnConfirmTable = document.getElementById('btn-confirm-table');
        const tableRowsInput = document.getElementById('table-rows-input');
        const tableColsInput = document.getElementById('table-cols-input');

        if (btnConfirmTable && tableRowsInput && tableColsInput) {
            btnConfirmTable.addEventListener('click', () => {
                const rows = parseInt(tableRowsInput.value, 10) || 3;
                const cols = parseInt(tableColsInput.value, 10) || 3;
                this.engine.insertTable(rows, cols);
                this.closeModal(tableModal);
                this.showToast(`Tabel ${rows}x${cols} berhasil disisipkan.`);
            });
        }

        // Insert Image Modal
        const imageModal = document.getElementById('modal-insert-image');
        const insertImageBtns = document.querySelectorAll('[data-action="insert-image-modal"]');
        insertImageBtns.forEach(b => b.addEventListener('click', () => this.openModal(imageModal)));

        const imageFileInput = document.getElementById('modal-image-file');
        const imageUrlInput = document.getElementById('modal-image-url');
        const imageAltInput = document.getElementById('modal-image-alt');
        const btnConfirmImage = document.getElementById('btn-confirm-image');

        if (btnConfirmImage) {
            btnConfirmImage.addEventListener('click', () => {
                const alt = imageAltInput ? imageAltInput.value.trim() : '';
                if (imageFileInput && imageFileInput.files.length > 0) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.engine.insertImage(e.target.result, alt);
                        this.closeModal(imageModal);
                        imageFileInput.value = '';
                    };
                    reader.readAsDataURL(imageFileInput.files[0]);
                } else if (imageUrlInput && imageUrlInput.value.trim()) {
                    this.engine.insertImage(imageUrlInput.value.trim(), alt);
                    this.closeModal(imageModal);
                    imageUrlInput.value = '';
                }
            });
        }

        // Export Modal & Quick Export buttons
        const exportModal = document.getElementById('modal-export');
        const exportBtns = document.querySelectorAll('[data-action="export-modal"]');
        exportBtns.forEach(b => b.addEventListener('click', () => this.openModal(exportModal)));

        document.querySelectorAll('[data-export-format]').forEach(btn => {
            btn.addEventListener('click', () => {
                const format = btn.getAttribute('data-export-format');
                if (exportModal) this.closeModal(exportModal);
                this.showToast(`Menyiapkan pengunduhan format ${format.toUpperCase()}...`);
                this.engine.exportAs(format);
            });
        });

        // Close buttons on all modals
        document.querySelectorAll('[data-close-modal]').forEach(btn => {
            btn.addEventListener('click', () => {
                const modal = btn.closest('.editor-modal');
                if (modal) this.closeModal(modal);
            });
        });
    }

    openModal(modal) {
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    closeModal(modal) {
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    /* -------------------------------------------------------------
     * Drag & Drop File Loader
     * ------------------------------------------------------------- */

    bindDragAndDrop() {
        const dropOverlay = document.getElementById('drag-drop-overlay');
        const dropZone = document.getElementById('document-canvas-container');

        if (!dropZone || !dropOverlay) return;

        ['dragenter', 'dragover'].forEach(eventName => {
            window.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropOverlay.classList.remove('hidden');
                dropOverlay.classList.add('flex');
            });
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropOverlay.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                if (e.type === 'drop') {
                    dropOverlay.classList.add('hidden');
                    dropOverlay.classList.remove('flex');

                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        const file = files[0];
                        this.showToast(`Memuat ${file.name} dan menyesuaikan format...`);
                        this.engine.loadLocalFile(file)
                            .then(res => this.showToast(res.message))
                            .catch(err => this.showToast(err.message, 'error'));
                    }
                } else if (e.target === dropOverlay) {
                    dropOverlay.classList.add('hidden');
                    dropOverlay.classList.remove('flex');
                }
            });
        });
    }

    /* -------------------------------------------------------------
     * Global Keyboard Shortcuts
     * ------------------------------------------------------------- */

    bindGlobalKeyboardShortcuts() {
        window.addEventListener('keydown', (e) => {
            const isCtrl = e.ctrlKey || e.metaKey;

            if (isCtrl && e.key.toLowerCase() === 's') {
                e.preventDefault();
                this.engine.saveToLocalStorage();
                this.showToast('Dokumen tersimpan di penyimpanan lokal!');
            } else if (isCtrl && e.key.toLowerCase() === 'o') {
                e.preventDefault();
                const modal = document.getElementById('modal-open-file');
                this.openModal(modal);
            } else if (isCtrl && e.key.toLowerCase() === 'p') {
                e.preventDefault();
                this.engine.exportAs('pdf');
            } else if (isCtrl && e.key.toLowerCase() === 'f') {
                e.preventDefault();
                const modal = document.getElementById('modal-find-replace');
                this.openModal(modal);
            }
        });
    }

    /* -------------------------------------------------------------
     * Rulers & Measurements
     * ------------------------------------------------------------- */

    renderRuler() {
        const ruler = document.getElementById('ruler-ticks');
        if (!ruler) return;

        ruler.innerHTML = '';
        // Standard A4 width is 21 cm
        for (let cm = 0; cm <= 21; cm++) {
            const cmTick = document.createElement('div');
            cmTick.className = 'flex-1 border-r border-gray-300 dark:border-gray-700 h-full relative';
            cmTick.innerHTML = `
                <span class="absolute right-0.5 top-0.5 text-[9px] text-gray-400 font-mono select-none">${cm}</span>
                <div class="absolute right-1/2 bottom-0 w-px h-1.5 bg-gray-200 dark:bg-gray-800"></div>
            `;
            ruler.appendChild(cmTick);
        }
    }

    /* -------------------------------------------------------------
     * Toast Notifications
     * ------------------------------------------------------------- */

    showToast(message, type = 'info') {
        const container = document.getElementById('toast-container');
        if (!container) return;

        const toast = document.createElement('div');
        let bg = 'bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900';
        if (type === 'error') bg = 'bg-red-600 text-white';
        if (type === 'warning') bg = 'bg-amber-600 text-white';
        if (type === 'success') bg = 'bg-emerald-600 text-white';

        toast.className = `${bg} px-4 py-2.5 rounded-lg shadow-lg text-xs font-medium flex items-center gap-2 transform transition-all duration-300 translate-y-2 opacity-0 pointer-events-auto`;
        toast.innerHTML = `
            <span>${message}</span>
        `;

        container.appendChild(toast);

        // Animate in
        requestAnimationFrame(() => {
            toast.classList.remove('translate-y-2', 'opacity-0');
            toast.classList.add('translate-y-0', 'opacity-100');
        });

        // Auto remove
        setTimeout(() => {
            toast.classList.remove('translate-y-0', 'opacity-100');
            toast.classList.add('translate-y-2', 'opacity-0');
            setTimeout(() => toast.remove(), 300);
        }, 3500);
    }
}
