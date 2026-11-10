/**
 * VelScribe UI Controller
 * Manages Microsoft Word-style Ribbon tabs, Google Docs-style Outline and Bubble Menu,
 * Interactive Table & Image Toolbars, Zoom, Rulers, Modals, Drag-and-Drop, and Keyboard Shortcuts.
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
        this.findMatches = [];
        this.currentMatchIndex = -1;

        window.uiController = this;
        this.init();
    }

    init() {
        this.bindRibbonTabs();
        this.bindRibbonTools();
        this.bindThemeSwitcher();
        this.bindFormatPainter();
        this.bindFontSteppers();
        this.bindQuickStyles();
        this.bindChecklist();
        this.bindNewDocument();
        this.bindReadAloud();
        this.bindDocumentAdaptationBanner();
        this.bindOutlineSearch();
        this.bindBubbleMenu();
        this.bindContextToolbars();
        this.bindZoomControls();
        this.bindLayoutModeSwitchers();
        this.bindModals();
        this.bindCoverPageModal();
        this.bindSymbolsModal();
        this.bindCitationModal();
        this.bindLatexEquationModal();
        this.bindHeaderFooterModal();
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

        // Basic formatting commands
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

        // Line Spacing
        const lineSpacingSelect = document.getElementById('line-spacing-select');
        if (lineSpacingSelect) {
            lineSpacingSelect.addEventListener('change', (e) => {
                this.engine.setLineSpacing(e.target.value);
                this.showToast(`Spasi baris diatur ke ${e.target.value}`);
            });
        }

        // Text Alignment buttons
        document.querySelectorAll('[data-align]').forEach(btn => {
            btn.addEventListener('click', () => {
                const align = btn.getAttribute('data-align');
                this.engine.setTextAlignment(align);
            });
        });

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

        // Insert Table of Contents
        const btnInsertToc = document.getElementById('btn-insert-toc');
        if (btnInsertToc) {
            btnInsertToc.addEventListener('click', () => {
                const ok = this.engine.generateTableOfContents();
                if (ok) {
                    this.showToast('Daftar Isi Otomatis berhasil dibuat dan disisipkan!');
                } else {
                    this.showToast('Tidak ada judul/heading untuk dibuatkan Daftar Isi.', 'warning');
                }
            });
        }

        // Page Layout tab settings (Margins preset)
        const marginsSelect = document.getElementById('layout-margins');
        if (marginsSelect) {
            marginsSelect.addEventListener('change', (e) => {
                this.engine.setMarginPreset(e.target.value);
                this.showToast(`Margin halaman diatur ke preset: ${e.target.value.toUpperCase()}`);
            });
        }

        // Paper Size
        const paperSizeSelect = document.getElementById('layout-paper-size');
        if (paperSizeSelect) {
            paperSizeSelect.addEventListener('change', (e) => {
                const sheets = document.querySelectorAll('.document-page-sheet');
                sheets.forEach(sheet => {
                    sheet.classList.remove('page-a4', 'page-letter', 'page-legal');
                    sheet.classList.add(`page-${e.target.value}`);
                });
                this.showToast(`Ukuran kertas diatur ke ${e.target.value.toUpperCase()}`);
            });
        }

        // Orientation
        const orientationSelect = document.getElementById('layout-orientation');
        if (orientationSelect) {
            orientationSelect.addEventListener('change', (e) => {
                const sheets = document.querySelectorAll('.document-page-sheet');
                sheets.forEach(sheet => {
                    if (e.target.value === 'landscape') {
                        sheet.classList.add('page-landscape');
                    } else {
                        sheet.classList.remove('page-landscape');
                    }
                });
                this.showToast(`Orientasi halaman: ${e.target.value}`);
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

    /* -------------------------------------------------------------
     * Theme Switcher (Dark & Light Theme Visual Switch)
     * ------------------------------------------------------------- */

    bindThemeSwitcher() {
        const themeToggleBtn = document.getElementById('theme-toggle-btn');
        const ribbonThemeBtn = document.getElementById('btn-ribbon-theme-toggle');
        const statusThemeBtn = document.getElementById('btn-status-theme-toggle');
        const floatingThemeBtn = document.getElementById('floating-theme-toggle');
        const darkSheetBtn = document.getElementById('btn-toggle-dark-sheet');
        const themeModal = document.getElementById('modal-theme-selector');

        // Initial theme detection:
        // 1. Check localStorage ('light' | 'dark' | 'system')
        // 2. Fallback to html.dark presence (from head script) or system preference
        let savedTheme = null;
        try {
            savedTheme = localStorage.getItem('velscribe_theme');
        } catch (e) {}

        if (!savedTheme) {
            savedTheme = document.documentElement.classList.contains('dark') ? 'dark' :
                (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        }

        this.applyTheme(savedTheme, false);

        // Header toggle button
        if (themeToggleBtn) {
            themeToggleBtn.addEventListener('click', () => {
                const next = this.currentTheme === 'dark' ? 'light' : 'dark';
                this.applyTheme(next, true);
            });
        }

        // Floating quick-toggle button
        if (floatingThemeBtn) {
            floatingThemeBtn.addEventListener('click', () => {
                const next = this.currentTheme === 'dark' ? 'light' : 'dark';
                this.applyTheme(next, true);
            });
        }

        // Ribbon view tab toggle button
        if (ribbonThemeBtn) {
            ribbonThemeBtn.addEventListener('click', () => {
                const next = this.currentTheme === 'dark' ? 'light' : 'dark';
                this.applyTheme(next, true);
            });
        }

        // Status bar toggle button
        if (statusThemeBtn) {
            statusThemeBtn.addEventListener('click', () => {
                const next = this.currentTheme === 'dark' ? 'light' : 'dark';
                this.applyTheme(next, true);
            });
        }

        // Ribbon segmented control buttons: data-theme-set="light|dark|system"
        document.querySelectorAll('.theme-segment-btn[data-theme-set]').forEach(btn => {
            btn.addEventListener('click', () => {
                const targetTheme = btn.getAttribute('data-theme-set');
                if (targetTheme) {
                    this.applyTheme(targetTheme, true);
                }
            });
        });

        // Theme Modal Trigger Buttons: data-action="open-theme-modal"
        document.querySelectorAll('[data-action="open-theme-modal"]').forEach(btn => {
            btn.addEventListener('click', () => {
                if (themeModal) {
                    this.syncThemeModal();
                    this.openModal(themeModal);
                }
            });
        });

        // Theme Modal Visual Cards: data-theme-card="light|dark|system"
        document.querySelectorAll('[data-theme-card]').forEach(card => {
            card.addEventListener('click', () => {
                const targetTheme = card.getAttribute('data-theme-card');
                if (targetTheme) {
                    this.applyTheme(targetTheme, true);
                    this.syncThemeModal();
                }
            });
        });

        // Theme Modal Sheet Canvas Radios: name="sheet-canvas-theme"
        document.querySelectorAll('input[name="sheet-canvas-theme"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                const isDark = e.target.value === 'dark';
                this.setDarkSheetMode(isDark);
            });
        });

        // OS system preference listener
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                let explicit = null;
                try {
                    explicit = localStorage.getItem('velscribe_theme');
                } catch (err) {}
                if (!explicit || explicit === 'system') {
                    this.applyTheme('system', false);
                }
            });
        }

        // Dark Sheet Canvas Toggle (Paper Canvas)
        if (darkSheetBtn) {
            darkSheetBtn.addEventListener('click', () => {
                const isCurrentlyDark = darkSheetBtn.classList.contains('active-dark');
                this.setDarkSheetMode(!isCurrentlyDark);
            });
        }
    }

    setDarkSheetMode(isDark) {
        const sheets = document.querySelectorAll('.document-page-sheet');
        sheets.forEach(sheet => {
            sheet.classList.toggle('canvas-dark-sheet', isDark);
        });

        const darkSheetBtn = document.getElementById('btn-toggle-dark-sheet');
        if (darkSheetBtn) {
            darkSheetBtn.classList.toggle('active-dark', isDark);
            const icon = document.getElementById('sheet-theme-icon') || darkSheetBtn.querySelector('span:first-child');
            const label = document.getElementById('sheet-theme-label') || darkSheetBtn.querySelector('span:last-child');
            if (icon && label && icon !== label) {
                icon.textContent = isDark ? '🌙' : '📄';
                label.textContent = isDark ? 'Lembar Gelap' : 'Lembar Putih';
            } else if (darkSheetBtn.querySelector('span')) {
                darkSheetBtn.querySelector('span').textContent = isDark ? '🌙 Lembar Gelap' : '📄 Lembar Putih';
            }
        }

        // Update modal radios and cards if open
        const whiteRadio = document.querySelector('input[name="sheet-canvas-theme"][value="white"]');
        const darkRadio = document.querySelector('input[name="sheet-canvas-theme"][value="dark"]');
        if (whiteRadio && darkRadio) {
            whiteRadio.checked = !isDark;
            darkRadio.checked = isDark;
        }

        const whiteCard = document.getElementById('sheet-card-white');
        const darkCard = document.getElementById('sheet-card-dark');
        if (whiteCard && darkCard) {
            whiteCard.classList.toggle('border-indigo-600', !isDark);
            whiteCard.classList.toggle('bg-indigo-50/20', !isDark);
            darkCard.classList.toggle('border-indigo-600', isDark);
            darkCard.classList.toggle('bg-indigo-50/20', isDark);
        }

        this.showToast(isDark ? 'Mode Lembar Gelap aktif.' : 'Mode Lembar Putih (Standar Kertas) aktif.');
    }

    syncThemeModal() {
        const theme = this.currentTheme || 'light';
        document.querySelectorAll('[data-theme-card]').forEach(card => {
            const cardTheme = card.getAttribute('data-theme-card');
            const isActive = cardTheme === theme;
            card.classList.toggle('active', isActive);
            card.classList.toggle('border-indigo-600', isActive);
            card.classList.toggle('dark:border-indigo-500', isActive);
            const badge = card.querySelector('.theme-check-badge');
            if (badge) {
                badge.classList.toggle('hidden', !isActive);
                badge.classList.toggle('flex', isActive);
            }
        });

        const isDarkSheet = document.getElementById('btn-toggle-dark-sheet')?.classList.contains('active-dark') || false;
        const whiteRadio = document.querySelector('input[name="sheet-canvas-theme"][value="white"]');
        const darkRadio = document.querySelector('input[name="sheet-canvas-theme"][value="dark"]');
        if (whiteRadio && darkRadio) {
            whiteRadio.checked = !isDarkSheet;
            darkRadio.checked = isDarkSheet;
        }
    }

    applyTheme(theme, notify = false) {
        this.currentTheme = ['light', 'dark', 'system'].includes(theme) ? theme : 'light';

        try {
            localStorage.setItem('velscribe_theme', this.currentTheme);
        } catch (e) {}

        // Resolve effective dark/light mode
        let effectiveDark = false;
        if (this.currentTheme === 'system') {
            effectiveDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        } else {
            effectiveDark = this.currentTheme === 'dark';
        }

        // Add temporary smooth transition class to avoid jarring jumps
        document.documentElement.classList.add('theme-transitioning');
        if (effectiveDark) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }

        setTimeout(() => {
            document.documentElement.classList.remove('theme-transitioning');
        }, 300);

        // 1. Update Header Theme Toggle Button visuals
        const themeToggleBtn = document.getElementById('theme-toggle-btn');
        const knob = document.getElementById('theme-toggle-knob');
        const knobIcon = document.getElementById('knob-icon');
        const text = document.getElementById('theme-toggle-text');
        const sun = document.getElementById('theme-icon-sun');
        const moon = document.getElementById('theme-icon-moon');

        if (themeToggleBtn) {
            themeToggleBtn.setAttribute('aria-checked', effectiveDark ? 'true' : 'false');
            themeToggleBtn.classList.toggle('is-dark', effectiveDark);
            themeToggleBtn.setAttribute('title', effectiveDark ? 'Tema Gelap aktif (Klik untuk ganti ke Mode Terang)' : 'Tema Terang aktif (Klik untuk ganti ke Mode Gelap)');
        }

        if (knob) {
            if (effectiveDark) {
                knob.classList.remove('translate-x-0', 'bg-white', 'border-gray-200');
                knob.classList.add('translate-x-5', 'bg-indigo-600', 'border-indigo-400');
            } else {
                knob.classList.remove('translate-x-5', 'bg-indigo-600', 'border-indigo-400');
                knob.classList.add('translate-x-0', 'bg-white', 'border-gray-200');
            }
        }

        if (knobIcon) {
            knobIcon.textContent = effectiveDark ? '🌙' : '☀️';
        }

        if (text) {
            text.textContent = this.currentTheme === 'system' ? 'Mode Sistem' : (effectiveDark ? 'Mode Gelap' : 'Mode Terang');
        }

        if (sun) {
            sun.classList.toggle('opacity-40', effectiveDark);
            sun.classList.toggle('opacity-100', !effectiveDark);
        }

        if (moon) {
            moon.classList.toggle('opacity-100', effectiveDark);
            moon.classList.toggle('opacity-40', !effectiveDark);
        }

        // 2. Update Ribbon Segmented Theme Switcher
        document.querySelectorAll('.theme-segment-btn[data-theme-set]').forEach(btn => {
            const isMatch = btn.getAttribute('data-theme-set') === this.currentTheme;
            btn.classList.toggle('active', isMatch);
        });

        // 3. Update Status Bar Switcher visuals
        const statusIcon = document.getElementById('status-theme-icon');
        if (statusIcon) statusIcon.textContent = effectiveDark ? '🌙' : '☀️';

        // 4. Update Floating Theme Widget visuals
        const floatingIcon = document.getElementById('floating-theme-icon');
        if (floatingIcon) floatingIcon.textContent = effectiveDark ? '🌙' : '☀️';

        // 5. Update Modal cards if modal elements exist
        this.syncThemeModal();

        // 6. Dispatch custom event
        window.dispatchEvent(new CustomEvent('velscribe:theme-changed', {
            detail: { theme: this.currentTheme, effectiveDark, isDark: effectiveDark }
        }));

        if (notify) {
            const msg = this.currentTheme === 'system' ? 'Tema Otomatis (Sistem) diterapkan.' :
                (effectiveDark ? 'Tema Gelap diaktifkan.' : 'Tema Terang diaktifkan.');
            this.showToast(msg);
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
     * Advanced Formatting & Styling Tools
     * ------------------------------------------------------------- */

    bindFormatPainter() {
        const btn = document.getElementById('btn-format-painter');
        if (!btn) return;

        btn.addEventListener('click', () => {
            const fmt = this.engine.copyFormat();
            if (fmt) {
                btn.classList.add('bg-indigo-100', 'text-indigo-700', 'dark:bg-indigo-950', 'dark:text-indigo-300');
                this.showToast('Format disalin! Pilih teks lain untuk menerapkan gaya yang disalin.', 'info');

                const applyHandler = () => {
                    this.engine.applyCopiedFormat();
                    btn.classList.remove('bg-indigo-100', 'text-indigo-700', 'dark:bg-indigo-950', 'dark:text-indigo-300');
                    document.removeEventListener('mouseup', applyHandler);
                };
                setTimeout(() => {
                    document.addEventListener('mouseup', applyHandler, { once: true });
                }, 120);
            } else {
                this.showToast('Pilih teks terlebih dahulu untuk menyalin format.', 'warning');
            }
        });
    }

    bindFontSteppers() {
        const btnGrow = document.getElementById('btn-font-grow');
        const btnShrink = document.getElementById('btn-font-shrink');
        if (btnGrow) btnGrow.addEventListener('click', () => this.engine.growFontSize());
        if (btnShrink) btnShrink.addEventListener('click', () => this.engine.shrinkFontSize());
    }

    bindQuickStyles() {
        document.querySelectorAll('[data-quick-style]').forEach(btn => {
            btn.addEventListener('click', () => {
                const style = btn.getAttribute('data-quick-style');
                this.engine.setHeading(style);
                this.showToast(`Gaya teks diatur: ${btn.textContent.trim()}`);
            });
        });
    }

    bindChecklist() {
        document.querySelectorAll('[data-cmd="insertChecklist"]').forEach(btn => {
            btn.addEventListener('click', () => {
                this.engine.insertChecklist();
                this.showToast('Item checklist disisipkan!');
            });
        });
    }

    bindNewDocument() {
        document.querySelectorAll('[data-action="new-document"]').forEach(b => {
            b.addEventListener('click', () => {
                if (confirm('Buat dokumen baru yang kosong? Pastikan dokumen yang sedang aktif sudah tersimpan.')) {
                    this.engine.newBlankDocument();
                    this.showToast('Lembar dokumen baru siap digunakan!');
                }
            });
        });
    }

    bindReadAloud() {
        const btn = document.getElementById('btn-read-aloud');
        if (!btn) return;

        btn.addEventListener('click', () => {
            if ('speechSynthesis' in window && window.speechSynthesis.speaking) {
                this.engine.stopReadAloud();
                btn.classList.remove('bg-indigo-100', 'text-indigo-700', 'dark:bg-indigo-950', 'dark:text-indigo-300');
                this.showToast('Pembacaan naskah dihentikan.');
            } else {
                const ok = this.engine.readAloud();
                if (ok) {
                    btn.classList.add('bg-indigo-100', 'text-indigo-700', 'dark:bg-indigo-950', 'dark:text-indigo-300');
                    this.showToast('Membacakan isi naskah...');
                    const checkInterval = setInterval(() => {
                        if (!window.speechSynthesis.speaking) {
                            btn.classList.remove('bg-indigo-100', 'text-indigo-700', 'dark:bg-indigo-950', 'dark:text-indigo-300');
                            clearInterval(checkInterval);
                        }
                    }, 500);
                } else {
                    this.showToast('Pilih teks atau buka dokumen untuk mendengarkan naskah.', 'warning');
                }
            }
        });
    }

    bindDocumentAdaptationBanner() {
        const banner = document.getElementById('document-adaptation-banner');
        const btnSkripsi = document.getElementById('banner-btn-skripsi');
        const btnNormal = document.getElementById('banner-btn-normal');
        const btnClose = document.getElementById('banner-btn-close');

        if (btnSkripsi) {
            btnSkripsi.addEventListener('click', () => {
                this.engine.setMarginPreset('skripsi');
                this.engine.setFontFamily("'Times New Roman', serif");
                this.engine.setFontSize('12pt');
                this.engine.setLineSpacing('1.5');
                this.showToast('Format Skripsi (Margin 4-4-3-3 cm, Times New Roman 12pt, Spasi 1.5) berhasil diterapkan!');
            });
        }

        if (btnNormal) {
            btnNormal.addEventListener('click', () => {
                this.engine.setMarginPreset('normal');
                this.engine.setFontFamily("'Instrument Sans', sans-serif");
                this.engine.setFontSize('11pt');
                this.engine.setLineSpacing('1.15');
                this.showToast('Format Normal (Margin 2.54 cm, Instrument Sans 11pt, Spasi 1.15) berhasil diterapkan!');
            });
        }

        if (btnClose && banner) {
            btnClose.addEventListener('click', () => {
                banner.classList.add('hidden');
            });
        }
    }

    updateAdaptationBanner(notice) {
        const banner = document.getElementById('document-adaptation-banner');
        const titleEl = document.getElementById('adaptation-banner-title');
        const descEl = document.getElementById('adaptation-banner-desc');
        if (!banner || !descEl) return;

        if (titleEl) {
            titleEl.textContent = notice.type === 'academic' ? '🎓 Format Skripsi/Akademik Otomatis Disesuaikan:' : '📄 Format Dokumen Otomatis Disesuaikan:';
        }
        descEl.textContent = notice.message;
        banner.classList.remove('hidden');
    }

    bindOutlineSearch() {
        const searchInput = document.getElementById('outline-search-input');
        if (!searchInput) return;

        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase().trim();
            const outlineList = document.getElementById('outline-list');
            if (!outlineList) return;

            const items = outlineList.querySelectorAll('li');
            items.forEach(item => {
                const text = item.textContent.toLowerCase();
                if (!query || text.includes(query)) {
                    item.classList.remove('hidden');
                } else {
                    item.classList.add('hidden');
                }
            });
        });
    }

    bindCoverPageModal() {
        const modal = document.getElementById('modal-cover-page');
        document.querySelectorAll('[data-action="insert-cover-modal"]').forEach(b => {
            b.addEventListener('click', () => this.openModal(modal));
        });

        document.querySelectorAll('[data-insert-cover]').forEach(btn => {
            btn.addEventListener('click', () => {
                const type = btn.getAttribute('data-insert-cover');
                this.engine.insertCoverPage(type);
                this.closeModal(modal);
                this.showToast(`Halaman Sampul (${type.toUpperCase()}) berhasil disisipkan di awal naskah!`);
            });
        });
    }

    bindSymbolsModal() {
        const modal = document.getElementById('modal-symbols');
        document.querySelectorAll('[data-action="insert-symbols-modal"]').forEach(b => {
            b.addEventListener('click', () => this.openModal(modal));
        });

        document.querySelectorAll('[data-insert-symbol]').forEach(btn => {
            btn.addEventListener('click', () => {
                const char = btn.getAttribute('data-insert-symbol');
                this.engine.insertSymbol(char);
                this.closeModal(modal);
                this.showToast(`Simbol ${char} berhasil disisipkan!`);
            });
        });
    }

    bindCitationModal() {
        const modal = document.getElementById('modal-citation');
        document.querySelectorAll('[data-action="insert-citation-modal"]').forEach(b => {
            b.addEventListener('click', () => this.openModal(modal));
        });

        const btnConfirm = document.getElementById('btn-confirm-citation');
        if (btnConfirm) {
            btnConfirm.addEventListener('click', () => {
                const author = document.getElementById('citation-author')?.value.trim() || 'Penulis';
                const year = document.getElementById('citation-year')?.value.trim() || '2026';
                const title = document.getElementById('citation-title')?.value.trim() || 'Judul Referensi';
                const source = document.getElementById('citation-source')?.value.trim() || 'Jurnal / Penerbit';
                this.engine.insertCitation(author, year, title, source);
                this.closeModal(modal);
                this.showToast('Sitasi & Entri Daftar Pustaka berhasil disisipkan!');
            });
        }
    }

    bindLatexEquationModal() {
        const modal = document.getElementById('modal-latex-equation');
        if (!modal) return;

        const input = document.getElementById('latex-input');
        const preview = document.getElementById('latex-preview');
        const titleEl = document.getElementById('latex-modal-title');
        const btnSave = document.getElementById('btn-save-equation');
        const btnDelete = document.getElementById('btn-delete-equation');
        let activeEquationEl = null;

        const updatePreview = () => {
            const val = input ? input.value.trim() : '';
            const isDisplay = document.querySelector('input[name="latex-mode"]:checked')?.value !== 'inline';
            if (!val) {
                if (preview) preview.innerHTML = '<span class="text-gray-400 italic text-xs">Pratinjau rumus matematika akan muncul di sini...</span>';
                return;
            }
            if (preview) {
                preview.innerHTML = this.engine.constructor.renderLatexHtml(val, isDisplay);
            }
        };

        const openForNew = () => {
            activeEquationEl = null;
            if (titleEl) titleEl.textContent = 'Sisipkan Rumus Matematika (LaTeX)';
            if (input) input.value = '';
            const displayRadio = document.querySelector('input[name="latex-mode"][value="display"]');
            if (displayRadio) displayRadio.checked = true;
            if (btnDelete) btnDelete.classList.add('hidden');
            if (btnSave) btnSave.textContent = 'Sisipkan Rumus';
            updatePreview();
            this.openModal(modal);
            setTimeout(() => input?.focus(), 50);
        };

        const openForEdit = (el) => {
            if (!el) return;
            activeEquationEl = el;
            const latex = el.getAttribute('data-latex') || '';
            const isDisplay = el.getAttribute('data-display') !== 'false';
            if (titleEl) titleEl.textContent = 'Ubah Rumus Matematika (LaTeX)';
            if (input) input.value = latex;
            const targetRadio = document.querySelector(`input[name="latex-mode"][value="${isDisplay ? 'display' : 'inline'}"]`);
            if (targetRadio) targetRadio.checked = true;
            if (btnDelete) btnDelete.classList.remove('hidden');
            if (btnSave) btnSave.textContent = 'Perbarui Rumus';
            updatePreview();
            this.openModal(modal);
            setTimeout(() => input?.focus(), 50);
        };

        document.querySelectorAll('[data-action="insert-equation-modal"]').forEach(b => {
            b.addEventListener('click', openForNew);
        });

        window.addEventListener('edit-latex-equation', (e) => {
            if (e.detail?.element) {
                openForEdit(e.detail.element);
            }
        });

        if (input) {
            input.addEventListener('input', updatePreview);
            input.addEventListener('keyup', updatePreview);
        }

        document.querySelectorAll('input[name="latex-mode"]').forEach(r => {
            r.addEventListener('change', updatePreview);
        });

        // Quick template buttons
        document.querySelectorAll('.latex-tpl-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const tpl = btn.getAttribute('data-tpl');
                if (!tpl || !input) return;
                const start = input.selectionStart || input.value.length;
                const end = input.selectionEnd || input.value.length;
                input.value = input.value.substring(0, start) + tpl + input.value.substring(end);
                input.focus();
                input.selectionStart = input.selectionEnd = start + tpl.length;
                updatePreview();
            });
        });

        if (btnSave) {
            btnSave.addEventListener('click', () => {
                const val = input ? input.value.trim() : '';
                if (!val) {
                    this.showToast('Masukkan rumus LaTeX terlebih dahulu.', 'warning');
                    return;
                }
                const isDisplay = document.querySelector('input[name="latex-mode"]:checked')?.value !== 'inline';

                if (activeEquationEl) {
                    this.engine.updateEquation(activeEquationEl, val, isDisplay);
                    this.showToast('Rumus matematika berhasil diperbarui!');
                } else {
                    this.engine.insertEquation(val, isDisplay);
                    this.showToast('Rumus matematika berhasil disisipkan!');
                }
                this.closeModal(modal);
            });
        }

        if (btnDelete) {
            btnDelete.addEventListener('click', () => {
                if (activeEquationEl) {
                    this.engine.deleteEquation(activeEquationEl);
                    activeEquationEl = null;
                    this.showToast('Rumus matematika dihapus.');
                }
                this.closeModal(modal);
            });
        }
    }

    bindHeaderFooterModal() {
        const modal = document.getElementById('modal-header-footer');
        if (!modal) return;

        const openBtns = document.querySelectorAll('[data-action="header-footer-modal"], [data-action="page-number-modal"]');
        const headerInput = document.getElementById('hf-header-input');
        const footerInput = document.getElementById('hf-footer-input');
        const formatSelect = document.getElementById('hf-page-format-select');
        const diffFirstToggle = document.getElementById('hf-different-first-toggle');
        const showNumToggle = document.getElementById('hf-show-page-number-toggle');
        const startNumInput = document.getElementById('hf-start-page-input');

        const populateFields = () => {
            if (headerInput) headerInput.value = this.engine.customHeaderText || this.engine.getTitle();
            if (footerInput) footerInput.value = this.engine.customFooterText || 'Dokumen VelScribe Wordcraft';
            if (formatSelect) formatSelect.value = this.engine.pageNumberFormat || 'full';
            if (diffFirstToggle) diffFirstToggle.checked = Boolean(this.engine.differentFirstPage);
            if (showNumToggle) showNumToggle.checked = this.engine.showPageNumber !== false;
            if (startNumInput) startNumInput.value = this.engine.startPageNumber || 1;
        };

        const openHfModal = () => {
            populateFields();
            this.openModal(modal);
        };

        openBtns.forEach(btn => btn.addEventListener('click', openHfModal));

        // Click / Double Click delegation on header & footer inside document pages
        const pagesWrapper = document.getElementById('document-pages-wrapper') || document.body;
        pagesWrapper.addEventListener('click', (e) => {
            const hf = e.target.closest('.page-sheet-header, .page-sheet-footer');
            if (hf) {
                openHfModal();
            }
        });
        pagesWrapper.addEventListener('dblclick', (e) => {
            const hf = e.target.closest('.page-sheet-header, .page-sheet-footer');
            if (hf) {
                openHfModal();
            }
        });

        // Apply changes button
        const btnApply = document.getElementById('btn-apply-header-footer');
        if (btnApply) {
            btnApply.addEventListener('click', () => {
                if (headerInput) this.engine.setHeaderText(headerInput.value);
                if (footerInput) this.engine.setFooterText(footerInput.value);
                if (formatSelect) this.engine.setPageNumberFormat(formatSelect.value);
                if (diffFirstToggle) this.engine.setDifferentFirstPage(diffFirstToggle.checked);
                if (showNumToggle) this.engine.setShowPageNumber(showNumToggle.checked);
                if (startNumInput) this.engine.setStartPageNumber(startNumInput.value);
                this.closeModal(modal);
                this.showToast('Header & Footer berhasil diperbarui!');
            });
        }

        // Reset / Clear button
        const btnReset = document.getElementById('btn-reset-header-footer');
        if (btnReset) {
            btnReset.addEventListener('click', () => {
                this.engine.setHeaderText('');
                this.engine.setFooterText('');
                this.engine.setDifferentFirstPage(false);
                this.engine.setPageNumberFormat('full');
                this.engine.setShowPageNumber(true);
                this.engine.setStartPageNumber(1);
                this.closeModal(modal);
                this.showToast('Header & Footer dikembalikan ke standar.');
            });
        }
    }

    /* -------------------------------------------------------------
     * Interactive Table & Image Context Toolbars
     * ------------------------------------------------------------- */

    bindContextToolbars() {
        // Table toolbar actions
        const tableToolbar = document.getElementById('floating-table-toolbar');
        if (tableToolbar) {
            tableToolbar.querySelector('[data-table-action="add-row-above"]')?.addEventListener('click', () => this.engine.addRowAbove());
            tableToolbar.querySelector('[data-table-action="add-row-below"]')?.addEventListener('click', () => this.engine.addRowBelow());
            tableToolbar.querySelector('[data-table-action="del-row"]')?.addEventListener('click', () => this.engine.deleteRow());
            tableToolbar.querySelector('[data-table-action="add-col"]')?.addEventListener('click', () => this.engine.addColumn());
            tableToolbar.querySelector('[data-table-action="del-col"]')?.addEventListener('click', () => this.engine.deleteColumn());
            tableToolbar.querySelector('[data-table-action="del-table"]')?.addEventListener('click', () => this.engine.deleteTable());
        }

        // Image toolbar actions
        const imageToolbar = document.getElementById('floating-image-toolbar');
        if (imageToolbar) {
            imageToolbar.querySelector('[data-img-align="left"]')?.addEventListener('click', () => this.engine.setImageAlignment('left'));
            imageToolbar.querySelector('[data-img-align="center"]')?.addEventListener('click', () => this.engine.setImageAlignment('center'));
            imageToolbar.querySelector('[data-img-align="right"]')?.addEventListener('click', () => this.engine.setImageAlignment('right'));
            imageToolbar.querySelector('[data-img-size="50%"]')?.addEventListener('click', () => this.engine.setImageWidth('50%'));
            imageToolbar.querySelector('[data-img-size="100%"]')?.addEventListener('click', () => this.engine.setImageWidth('100%'));
        }
    }

    /* -------------------------------------------------------------
     * Floating Bubble Menu (Google Docs style)
     * ------------------------------------------------------------- */

    bindBubbleMenu() {
        const bubble = document.getElementById('bubble-menu');
        if (!bubble) return;

        this.engine.onSelectionChange = (state) => {
            if (state.hasSelection && state.rect && state.text.trim().length > 0) {
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

        const bubbleAiBtn = bubble.querySelector('#bubble-ask-ai') || bubble.querySelector('#bubble-btn-ai-rewrite');
        if (bubbleAiBtn) {
            bubbleAiBtn.addEventListener('click', () => {
                this.toggleWordcraftSidebar(true);
                if (this.wordcraft) {
                    this.wordcraft.executeAction('rewrite');
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

        const applyZoom = (val) => {
            this.currentZoom = Math.min(200, Math.max(50, val));
            if (zoomSlider) zoomSlider.value = this.currentZoom;
            if (zoomLabel) zoomLabel.textContent = `${this.currentZoom}%`;
            const wrapper = document.getElementById('document-pages-wrapper') || document.getElementById('document-page-canvas');
            if (wrapper) {
                wrapper.style.transform = `scale(${this.currentZoom / 100})`;
                wrapper.style.transformOrigin = 'top center';
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
        const ribbon = document.getElementById('ribbon-bar');
        const topNav = document.getElementById('top-nav');
        const ruler = document.getElementById('ruler-bar');
        const outline = document.getElementById('outline-sidebar');
        const wordcraft = document.getElementById('wordcraft-sidebar');

        const setMode = (mode) => {
            this.layoutMode = mode;
            const sheets = document.querySelectorAll('.document-page-sheet');
            const gaps = document.querySelectorAll('.page-sheet-gap');

            document.querySelectorAll('[data-layout-mode]').forEach(btn => {
                const active = btn.getAttribute('data-layout-mode') === mode;
                btn.classList.toggle('text-indigo-600', active);
                btn.classList.toggle('font-bold', active);
            });

            if (mode === 'print') {
                sheets.forEach(sheet => {
                    sheet.classList.add('page-a4', 'shadow-2xl', 'rounded-sm');
                    sheet.classList.remove('w-full', 'max-w-4xl', 'shadow-none');
                });
                gaps.forEach(g => g.classList.remove('hidden'));
                if (ribbon) ribbon.classList.remove('hidden');
                if (topNav) topNav.classList.remove('hidden');
                if (ruler) ruler.classList.toggle('hidden', !this.isRulerVisible);
            } else if (mode === 'web') {
                sheets.forEach(sheet => {
                    sheet.classList.remove('page-a4', 'shadow-2xl');
                    sheet.classList.add('w-full', 'max-w-4xl', 'shadow-sm');
                });
                gaps.forEach(g => g.classList.add('hidden'));
                if (ribbon) ribbon.classList.remove('hidden');
                if (topNav) topNav.classList.remove('hidden');
                if (ruler) ruler.classList.add('hidden');
            } else if (mode === 'zen') {
                sheets.forEach(sheet => {
                    sheet.classList.remove('page-a4', 'shadow-2xl');
                    sheet.classList.add('w-full', 'max-w-3xl', 'shadow-none');
                });
                gaps.forEach(g => g.classList.add('hidden'));
                if (ribbon) ribbon.classList.add('hidden');
                if (ruler) ruler.classList.add('hidden');
                if (outline) outline.classList.add('hidden');
                if (wordcraft) wordcraft.classList.add('hidden');
                this.showToast('Mode Zen aktif. Tekan Escape untuk kembali.');
            }
        };

        document.querySelectorAll('[data-layout-mode]').forEach(btn => {
            btn.addEventListener('click', () => setMode(btn.getAttribute('data-layout-mode')));
        });

        window.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.layoutMode === 'zen') {
                setMode('print');
            }
        });
    }

    /* -------------------------------------------------------------
     * Modals (Open File, Find & Replace, Link, Table, Image, Export, Stats, Shortcuts)
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
                    this.showToast(`Membaca ${file.name} dan menyelaraskan format...`);
                    try {
                        const res = await this.engine.loadLocalFile(file);
                        this.showToast(res.message);
                    } catch (err) {
                        this.showToast('Gagal memuat dokumen: ' + err.message, 'error');
                    }
                }
            });
        }

        // Quick sample template loaders in open modal
        document.querySelectorAll('[data-load-sample]').forEach(btn => {
            btn.addEventListener('click', () => {
                const sampleType = btn.getAttribute('data-load-sample');
                this.closeModal(openFileModal);
                this.loadSampleTemplate(sampleType);
            });
        });

        // Find & Replace Modal with active search highlight
        const findModal = document.getElementById('modal-find-replace');
        const findBtns = document.querySelectorAll('[data-action="find-replace"]');
        findBtns.forEach(b => b.addEventListener('click', () => this.openModal(findModal)));

        const findInput = document.getElementById('find-search-input');
        const replaceInput = document.getElementById('find-replace-input');
        const btnReplaceAll = document.getElementById('btn-replace-all');
        const btnReplaceOne = document.getElementById('btn-replace-one');

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
                    this.showToast(`Kata "${query}" tidak ditemukan.`, 'warning');
                }
            });
        }

        if (btnReplaceOne && findInput && replaceInput) {
            btnReplaceOne.addEventListener('click', () => {
                const query = findInput.value;
                const replacement = replaceInput.value;
                if (!query) return;

                const content = this.engine.getContent();
                const regex = new RegExp(query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'));
                if (regex.test(content)) {
                    const updated = content.replace(regex, replacement);
                    this.engine.setContent(updated);
                    this.showToast(`Kata "${query}" pertama berhasil diganti.`);
                } else {
                    this.showToast(`Kata "${query}" tidak ditemukan.`, 'warning');
                }
            });
        }

        // Detailed Statistics Modal
        const statsModal = document.getElementById('modal-stats');
        document.querySelectorAll('[data-action="open-stats-modal"]').forEach(b => {
            b.addEventListener('click', () => {
                this.populateStatsModal();
                this.openModal(statsModal);
            });
        });

        // Keyboard Shortcuts Modal
        const shortcutsModal = document.getElementById('modal-shortcuts');
        document.querySelectorAll('[data-action="open-shortcuts-modal"]').forEach(b => {
            b.addEventListener('click', () => this.openModal(shortcutsModal));
        });

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

    populateStatsModal() {
        const text = this.engine.editorEl.innerText || '';
        const clean = text.trim();
        const words = clean ? clean.split(/\s+/).filter(Boolean).length : 0;
        const chars = clean.length;
        const charsNoSpaces = clean.replace(/\s+/g, '').length;
        const paragraphs = clean ? clean.split(/\n\s*\n/).filter(Boolean).length : 0;
        const sentences = clean ? clean.split(/[.!?]+/).filter(Boolean).length : 0;
        const readingTime = Math.max(1, Math.ceil(words / 200));
        const speakingTime = Math.max(1, Math.ceil(words / 130));

        const setVal = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.textContent = val;
        };

        setVal('modal-stat-words', words);
        setVal('modal-stat-chars', chars);
        setVal('modal-stat-chars-nospace', charsNoSpaces);
        setVal('modal-stat-sentences', sentences);
        setVal('modal-stat-paras', paragraphs);
        setVal('modal-stat-reading', `${readingTime} menit`);
        setVal('modal-stat-speaking', `${speakingTime} menit`);
    }

    loadSampleTemplate(type) {
        if (type === 'skripsi') {
            const title = 'SKRIPSI — PENERAPAN ENSEMBLE LEARNING PADA SISTEM MONITORING IOT';
            const sampleHtml = `
                <p class="text-center font-bold text-gray-900 dark:text-gray-100 my-2">SKRIPSI</p>
                <p class="text-center font-bold text-gray-900 dark:text-gray-100 my-2 text-lg">PENERAPAN ENSEMBLE LEARNING UNTUK KLASIFIKASI KUALITAS LIMBAH CAIR TEKSTIL BERBASIS SENSOR WARNA PADA SISTEM MONITORING INTERNET OF THINGS</p>
                <figure class="my-6 text-center">
                    <div class="w-32 h-32 mx-auto bg-emerald-100 dark:bg-emerald-950/60 rounded-full flex items-center justify-center text-emerald-700 font-bold text-xl border-2 border-emerald-500">LOGO</div>
                </figure>
                <p class="text-center font-bold text-gray-900 dark:text-gray-100 my-1">Oleh :</p>
                <p class="text-center font-bold text-gray-900 dark:text-gray-100 my-1">BAITS RIKA SAPUTRA</p>
                <p class="text-center font-bold text-gray-900 dark:text-gray-100 my-1">NIM. 23.6651.071</p>
                <p class="text-center font-bold text-gray-900 dark:text-gray-100 mt-6">PROGRAM STUDI SARJANA TERAPAN TEKNIK INFORMATIKA MULTIMEDIA</p>
                <p class="text-center font-bold text-gray-900 dark:text-gray-100">JURUSAN TEKNOLOGI INFORMASI</p>
                <p class="text-center font-bold text-gray-900 dark:text-gray-100">POLITEKNIK NEGERI SAMARINDA</p>
                <p class="text-center font-bold text-gray-900 dark:text-gray-100 mb-8">2026</p>

                <div class="page-break my-8 border-b-2 border-dashed border-gray-400 text-center py-2 select-none" data-page-break="true">
                    <span class="bg-gray-100 dark:bg-gray-800 px-3 py-1 rounded-full text-xs font-mono">Pemisah Halaman (Akhir Sampul)</span>
                </div>

                <h1 id="heading-0" class="heading-preliminary text-center font-bold text-xl my-6">ABSTRAK</h1>
                <p class="text-justify my-3 leading-relaxed indent-8">Pencemaran limbah cair industri tekstil membutuhkan mekanisme monitoring secara real-time dan berkesinambungan. Penelitian ini mengintegrasikan sensor warna TCS3200 dengan mikrokontroler ESP32 serta algoritma ensemble learning guna mengklasifikasikan baku mutu limbah dengan akurasi teruji.</p>

                <div class="page-break my-6 border-b border-dashed border-gray-300 text-center py-1 select-none" data-page-break="true">
                    <span class="text-[10px] font-mono">Awal Bab Baru</span>
                </div>

                <h1 id="heading-1" class="heading-chapter text-center font-bold text-xl my-6">BAB I PENDAHULUAN</h1>
                <h2 id="heading-2" class="heading-section font-bold text-lg my-4">1.1 Latar Belakang Masalah</h2>
                <p class="text-justify my-3 leading-relaxed indent-8">Perkembangan sektor perindustrian tekstil menghasilkan limbah cair dengan konsentrasi zat warna sintetis yang berdampak buruk pada kualitas perairan jika tidak dipantau secara ketat. Berdasarkan standar baku mutu lingkungan, pengujian konvensional di laboratorium membutuhkan waktu berhari-hari sehingga berisiko menunda penanganan pencemaran.</p>

                <h2 id="heading-3" class="heading-section font-bold text-lg my-4">1.2 Rumusan Masalah</h2>
                <p class="text-justify my-3 leading-relaxed indent-8">Bagaimana mengimplementasikan algoritma ensemble learning pada data sensor warna untuk mendeteksi ambang batas limbah cair tekstil secara akurat dan real-time?</p>

                <h2 id="heading-4" class="heading-section font-bold text-lg my-4">1.3 Tujuan Penelitian</h2>
                <p class="text-justify my-3 leading-relaxed indent-8">1. Merancang arsitektur perangkat keras IoT berbasis sensor warna.</p>
                <p class="text-justify my-3 leading-relaxed indent-8">2. Mengembangkan model klasifikasi ensemble learning dengan akurasi di atas 90%.</p>
            `;

            this.engine.setTitle(title);
            this.engine.setContent(sampleHtml);
            this.engine.setMarginPreset('skripsi');
            this.engine.setFontFamily("'Times New Roman', serif");
            this.engine.setFontSize('12pt');
            this.engine.setLineSpacing('1.5');
            this.showToast('Contoh Naskah Skripsi berhasil dimuat dengan format 4-4-3-3 cm!');
        } else if (type === 'report') {
            const title = 'Laporan Riset Evaluasi Sistem Monitoring IoT 2026';
            const sampleHtml = `
                <h1 id="heading-0" class="font-bold text-2xl mb-2 text-gray-900">Laporan Riset Evaluasi Sistem Monitoring IoT 2026</h1>
                <p class="text-gray-600 text-sm mb-4">Disusun oleh Tim Laboratorium Multimedia & IoT — Versi 2.4 Final</p>
                <hr class="my-4 border-gray-200" />
                <h2 id="heading-1" class="font-bold text-lg my-3 text-gray-800">1. Ringkasan Eksekutif</h2>
                <p class="text-justify my-2 leading-relaxed">Pengujian komputasi edge dan transmisi data sensor warna telah dievaluasi dengan throughput tinggi. Hasil integrasi sistem membuktikan efisiensi kompresi dan kecepatan deteksi mencapai 99.4% reliabilitas operasional.</p>
                <h2 id="heading-2" class="font-bold text-lg my-3 text-gray-800">2. Matriks Pengujian Sensor</h2>
                <table class="w-full border-collapse my-4 border border-gray-300 text-sm">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border border-gray-300 p-2 text-left font-semibold">Komponen</th>
                            <th class="border border-gray-300 p-2 text-left font-semibold">Rentang Deteksi</th>
                            <th class="border border-gray-300 p-2 text-left font-semibold">Toleransi Error</th>
                            <th class="border border-gray-300 p-2 text-left font-semibold">Hasil</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="border border-gray-300 p-2">TCS3200 Warna</td>
                            <td class="border border-gray-300 p-2">RGB 400 - 700 nm</td>
                            <td class="border border-gray-300 p-2">&plusmn; 1.2%</td>
                            <td class="border border-gray-300 p-2 text-emerald-600 font-bold">Lolos Uji</td>
                        </tr>
                        <tr>
                            <td class="border border-gray-300 p-2">ESP32 MCU</td>
                            <td class="border border-gray-300 p-2">Wi-Fi 2.4 GHz</td>
                            <td class="border border-gray-300 p-2">&lt; 0.5% Loss</td>
                            <td class="border border-gray-300 p-2 text-emerald-600 font-bold">Lolos Uji</td>
                        </tr>
                    </tbody>
                </table>
            `;
            this.engine.setTitle(title);
            this.engine.setContent(sampleHtml);
            this.engine.setMarginPreset('normal');
            this.engine.setFontFamily("'Instrument Sans', sans-serif");
            this.engine.setFontSize('11pt');
            this.engine.setLineSpacing('1.5');
            this.showToast('Contoh Laporan Riset (Format Normal 2.54 cm) berhasil dimuat!');
        }
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
                        this.showToast(`Memuat ${file.name} dan menyelaraskan format...`);
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
            } else if (isCtrl && e.shiftKey && e.key.toLowerCase() === 'c') {
                e.preventDefault();
                this.populateStatsModal();
                this.openModal(document.getElementById('modal-stats'));
            } else if (isCtrl && e.key === '/') {
                e.preventDefault();
                this.openModal(document.getElementById('modal-shortcuts'));
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
        // A4 width = 21 cm
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

        requestAnimationFrame(() => {
            toast.classList.remove('translate-y-2', 'opacity-0');
            toast.classList.add('translate-y-0', 'opacity-100');
        });

        setTimeout(() => {
            toast.classList.remove('translate-y-0', 'opacity-100');
            toast.classList.add('translate-y-2', 'opacity-0');
            setTimeout(() => toast.remove(), 300);
        }, 3800);
    }
}
