/**
 * VelScribe UI Controller
 * Manages Microsoft Word-style Ribbon tabs, Google Docs-style Outline and Bubble Menu,
 * Interactive Table & Image Toolbars, Zoom, Rulers, Modals, Drag-and-Drop, and Keyboard Shortcuts.
 */

import { PAPER_DIMENSIONS, MARGIN_PRESETS } from './document-engine.js';
import { ReviewComments } from './review-comments.js';
import { WordToolsSuite } from './word-tools-suite.js';

export class UiController {
    constructor(engine, wordcraftAi) {
        this.engine = engine;
        this.wordcraft = wordcraftAi;

        this.currentZoom = 100;
        this.layoutMode = 'print'; // 'print' | 'web' | 'zen'
        const isWideScreen = typeof window !== 'undefined' && (window.innerWidth >= 1280 || window.innerWidth === undefined);
        this.isRulerVisible = true;
        this.isOutlineVisible = isWideScreen;
        this.isWordcraftVisible = false;
        this.findMatches = [];
        this.currentMatchIndex = -1;

        window.uiController = this;
        this.init();
    }

    init() {
        const tasks = [
            ['bindRibbonTabs', () => this.bindRibbonTabs()],
            ['bindRibbonTools', () => this.bindRibbonTools()],
            ['bindReviewComments', () => { this.reviewComments = new ReviewComments(this.engine); this.reviewComments.bind(); }],
            ['bindWordToolsSuite', () => { this.wordToolsSuite = new WordToolsSuite(this.engine, this); this.wordToolsSuite.bind(); }],
            ['bindThemeSwitcher', () => this.bindThemeSwitcher()],
            ['bindFormatPainter', () => this.bindFormatPainter()],
            ['bindFontSteppers', () => this.bindFontSteppers()],
            ['bindQuickStyles', () => this.bindQuickStyles()],
            ['bindChecklist', () => this.bindChecklist()],
            ['bindNewDocument', () => this.bindNewDocument()],
            ['bindReadAloud', () => this.bindReadAloud()],
            ['bindDocumentAdaptationBanner', () => this.bindDocumentAdaptationBanner()],
            ['bindOutlineSearch', () => this.bindOutlineSearch()],
            ['bindBubbleMenu', () => this.bindBubbleMenu()],
            ['bindContextToolbars', () => this.bindContextToolbars()],
            ['bindZoomControls', () => this.bindZoomControls()],
            ['bindLayoutModeSwitchers', () => this.bindLayoutModeSwitchers()],
            ['bindModals', () => this.bindModals()],
            ['bindCoverPageModal', () => this.bindCoverPageModal()],
            ['bindSymbolsModal', () => this.bindSymbolsModal()],
            ['bindCitationModal', () => this.bindCitationModal()],
            ['bindLatexEquationModal', () => this.bindLatexEquationModal()],
            ['bindHeaderFooterModal', () => this.bindHeaderFooterModal()],
            ['bindTableOfContentsSuite', () => this.bindTableOfContentsSuite()],
            ['bindPageSetupAndParagraphSuite', () => this.bindPageSetupAndParagraphSuite()],
            ['bindDragAndDrop', () => this.bindDragAndDrop()],
            ['bindGlobalKeyboardShortcuts', () => this.bindGlobalKeyboardShortcuts()],
            ['renderRuler', () => this.renderRuler()],
        ];

        for (const [name, task] of tasks) {
            try {
                task();
            } catch (err) {
                console.error(`UiController ${name} error:`, err);
            }
        }
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

        // Insert Table of Contents is handled in bindTableOfContentsSuite()

        // Page Layout tab settings (Margins preset)
        const marginsSelect = document.getElementById('layout-margins');
        if (marginsSelect) {
            marginsSelect.addEventListener('change', (e) => {
                if (e.target.value === 'custom') {
                    this.openPageSetupModal('margins');
                    return;
                }
                this.engine.setMarginPreset(e.target.value);
                this.showToast(`Margin halaman diatur ke preset: ${e.target.value.toUpperCase()}`);
            });
        }

        // Paper Size
        const paperSizeSelect = document.getElementById('layout-paper-size');
        if (paperSizeSelect) {
            paperSizeSelect.addEventListener('change', (e) => {
                if (e.target.value === 'custom') {
                    this.openPageSetupModal('paper');
                    return;
                }
                this.engine.setPaperSize(e.target.value);
                this.showToast(`Ukuran kertas diatur ke ${e.target.value.toUpperCase()}`);
            });
        }

        // Orientation
        const orientationSelect = document.getElementById('layout-orientation');
        if (orientationSelect) {
            orientationSelect.addEventListener('change', (e) => {
                this.engine.setOrientation(e.target.value);
                this.showToast(`Orientasi halaman: ${e.target.value === 'landscape' ? 'Lanskap' : 'Potret'}`);
            });
        }

        // View Toggles
        const toggleRulerBtn = document.getElementById('btn-toggle-ruler');
        if (toggleRulerBtn) {
            toggleRulerBtn.addEventListener('click', () => {
                this.isRulerVisible = !this.isRulerVisible;
                const ruler = document.getElementById('ruler-bar');
                const vRuler = document.getElementById('vertical-ruler');
                if (ruler) ruler.classList.toggle('hidden', !this.isRulerVisible);
                if (vRuler) vRuler.classList.toggle('hidden', !this.isRulerVisible);
            });
        }

        const toggleOutlineBtn = document.getElementById('btn-toggle-outline');
        if (toggleOutlineBtn) {
            toggleOutlineBtn.addEventListener('click', () => {
                this.toggleOutline();
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
                icon.innerHTML = isDark ? '<i class="fa-solid fa-moon"></i>' : '<i class="fa-solid fa-file"></i>';
                label.textContent = isDark ? 'Lembar Gelap' : 'Lembar Putih';
            } else if (darkSheetBtn.querySelector('span')) {
                darkSheetBtn.querySelector('span').innerHTML = isDark ? '<i class="fa-solid fa-moon mr-1"></i> Lembar Gelap' : '<i class="fa-solid fa-file mr-1"></i> Lembar Putih';
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
            knobIcon.innerHTML = effectiveDark ? '<i class="fa-solid fa-moon"></i>' : '<i class="fa-solid fa-sun"></i>';
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
        if (statusIcon) statusIcon.innerHTML = effectiveDark ? '<i class="fa-solid fa-moon"></i>' : '<i class="fa-solid fa-sun"></i>';

        // 4. Update Floating Theme Widget visuals
        const floatingIcon = document.getElementById('floating-theme-icon');
        if (floatingIcon) floatingIcon.innerHTML = effectiveDark ? '<i class="fa-solid fa-moon"></i>' : '<i class="fa-solid fa-sun"></i>';

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

    toggleOutline(forceOpen = null) {
        const sidebar = document.getElementById('outline-sidebar');
        const backdrop = document.getElementById('outline-backdrop');
        if (!sidebar) return;

        if (forceOpen !== null) {
            this.isOutlineVisible = forceOpen;
        } else {
            this.isOutlineVisible = !this.isOutlineVisible;
        }

        sidebar.classList.toggle('hidden', !this.isOutlineVisible);
        if (backdrop) {
            const isNarrow = typeof window !== 'undefined' && window.innerWidth < 1280;
            backdrop.classList.toggle('hidden', !this.isOutlineVisible || !isNarrow);
        }

        const outlineCb = document.querySelector('#btn-toggle-outline input[type="checkbox"]');
        if (outlineCb) {
            outlineCb.checked = this.isOutlineVisible;
        }
    }

    toggleWordcraftSidebar(forceOpen = null) {
        const sidebar = document.getElementById('wordcraft-sidebar');
        const backdrop = document.getElementById('wordcraft-backdrop');
        if (!sidebar) return;

        if (forceOpen !== null) {
            this.isWordcraftVisible = forceOpen;
        } else {
            this.isWordcraftVisible = !this.isWordcraftVisible;
        }

        sidebar.classList.toggle('hidden', !this.isWordcraftVisible);
        if (backdrop) {
            const isNarrow = typeof window !== 'undefined' && window.innerWidth < 1280;
            backdrop.classList.toggle('hidden', !this.isWordcraftVisible || !isNarrow);
        }
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
            titleEl.innerHTML = notice.type === 'academic' ? '<i class="fa-solid fa-graduation-cap mr-1"></i> Format Skripsi/Akademik Otomatis Disesuaikan:' : '<i class="fa-solid fa-file mr-1"></i> Format Dokumen Otomatis Disesuaikan:';
        }
        descEl.textContent = notice.message;
        banner.classList.remove('hidden');
    }

    bindOutlineSearch() {
        const searchInput = document.getElementById('outline-search-input');
        if (!searchInput) return;

        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase().trim();
            const outlineList = document.getElementById('outline-headings-list');
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
        if (!modal) return;

        const tabButtons = modal.querySelectorAll('.citation-tab-btn');
        const tabContents = modal.querySelectorAll('.citation-tab-content');
        const countBadge = document.getElementById('tab-source-count-badge');
        const footerStats = document.getElementById('citation-modal-footer-stats');
        const ribbonStyleSelect = document.getElementById('ribbon-citation-style-select');
        const modalStyleSelect = document.getElementById('modal-bibliography-style');
        const modalHeadingInput = document.getElementById('modal-bibliography-heading');
        const previewBox = document.getElementById('bibliography-preview-box');
        const previewBadge = document.getElementById('preview-style-badge');

        const updateSourceCountUI = () => {
            const count = (this.engine.getSources && this.engine.getSources().length) || 0;
            if (countBadge) countBadge.textContent = String(count);
            if (footerStats) footerStats.textContent = `${count} sumber aktif di dokumen saat ini.`;
        };

        const switchTab = (tabKey) => {
            tabButtons.forEach(btn => {
                const target = btn.getAttribute('data-citation-nav');
                if (target === tabKey) {
                    btn.className = 'citation-tab-btn py-2.5 px-3 border-b-2 border-indigo-600 text-indigo-600 dark:text-indigo-400 font-bold flex items-center gap-1.5 cursor-pointer';
                } else {
                    btn.className = 'citation-tab-btn py-2.5 px-3 border-b-2 border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 flex items-center gap-1.5 cursor-pointer';
                }
            });

            tabContents.forEach(content => {
                if (content.id === `citation-tab-${tabKey}`) {
                    content.classList.remove('hidden');
                } else {
                    content.classList.add('hidden');
                }
            });

            updateSourceCountUI();

            if (tabKey === 'manage') {
                renderManageSources();
            } else if (tabKey === 'bibliography') {
                renderBibliographyPreview();
            }
        };

        tabButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const nav = btn.getAttribute('data-citation-nav');
                if (nav) switchTab(nav);
            });
        });

        // Ribbon and action triggers
        document.querySelectorAll('[data-action="insert-citation-modal"]').forEach(b => {
            b.addEventListener('click', () => {
                this.openModal(modal);
                switchTab('add');
            });
        });

        document.querySelectorAll('[data-action="open-source-manager"]').forEach(b => {
            b.addEventListener('click', () => {
                this.openModal(modal);
                switchTab('manage');
            });
        });

        document.querySelectorAll('[data-action="open-academic-search"]').forEach(b => {
            b.addEventListener('click', () => {
                this.openModal(modal);
                switchTab('search');
                const searchInput = document.getElementById('search-source-query');
                if (searchInput) setTimeout(() => searchInput.focus(), 100);
            });
        });

        document.querySelectorAll('[data-action="insert-bibliography-quick"]').forEach(b => {
            b.addEventListener('click', () => {
                const style = (ribbonStyleSelect ? ribbonStyleSelect.value : null) || this.engine.citationStyle || 'apa';
                this.engine.insertBibliography(style);
                this.showToast('Daftar Pustaka berhasil disisipkan sesuai standar Word!');
            });
        });

        const btnUpdateBiblio = document.getElementById('btn-update-bibliography');
        if (btnUpdateBiblio) {
            btnUpdateBiblio.addEventListener('click', () => {
                const style = (ribbonStyleSelect ? ribbonStyleSelect.value : null) || this.engine.citationStyle || 'apa';
                this.engine.updateBibliography(style);
                this.showToast('Daftar Pustaka & nomor sitasi dalam naskah berhasil diperbarui!');
            });
        }

        if (ribbonStyleSelect) {
            ribbonStyleSelect.addEventListener('change', (e) => {
                const style = e.target.value;
                this.engine.setCitationStyle(style);
                if (modalStyleSelect) modalStyleSelect.value = style;
                this.showToast(`Gaya sitasi diubah ke standar: ${style.toUpperCase()}`);
            });
        }

        /* ------------------ Tab 1: Mesin Pencari Online ------------------ */
        const searchInput = document.getElementById('search-source-query');
        const searchTypeSelect = document.getElementById('search-source-type');
        const btnExecuteSearch = document.getElementById('btn-execute-source-search');
        const searchStatus = document.getElementById('search-source-status');
        const searchResultsList = document.getElementById('search-results-list');

        const executeSearch = async () => {
            const query = searchInput ? searchInput.value.trim() : '';
            if (!query) {
                this.showToast('Masukkan kata kunci pencarian atau DOI terlebih dahulu.', 'warning');
                if (searchInput) searchInput.focus();
                return;
            }

            const type = searchTypeSelect ? searchTypeSelect.value : 'all';
            if (searchStatus) searchStatus.classList.remove('hidden');
            if (searchResultsList) searchResultsList.innerHTML = '';

            try {
                const res = await fetch('/api/documents/search-sources', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify({ query, type })
                });

                const data = await res.json();
                if (searchStatus) searchStatus.classList.add('hidden');

                if (!data.success || !data.results || data.results.length === 0) {
                    if (searchResultsList) {
                        searchResultsList.innerHTML = `<div class="p-6 text-center text-gray-500 dark:text-gray-400">
                            <p class="font-semibold text-xs">Tidak ditemukan referensi untuk "${this.engine.escapeHtml(query)}".</p>
                            <p class="text-[11px] mt-1">Coba gunakan kata kunci umum, topik teknologi, nama pengarang, atau nomor DOI.</p>
                        </div>`;
                    }
                    return;
                }

                renderSearchResults(data.results);
            } catch (err) {
                if (searchStatus) searchStatus.classList.add('hidden');
                if (searchResultsList) {
                    searchResultsList.innerHTML = `<div class="p-4 text-center text-red-600 dark:text-red-400 text-xs">
                        Gagal menghubungi mesin pencari pustaka: ${this.engine.escapeHtml(err.message)}
                    </div>`;
                }
            }
        };

        const renderSearchResults = (results) => {
            if (!searchResultsList) return;
            searchResultsList.innerHTML = '';

            results.forEach((item) => {
                const card = document.createElement('div');
                card.className = 'p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-2xs hover:border-indigo-300 dark:hover:border-indigo-700 transition flex flex-col sm:flex-row sm:items-start justify-between gap-3';

                const title = this.engine.escapeHtml(item.title || 'Tanpa Judul');
                const author = this.engine.escapeHtml(item.author || 'Anonim');
                const year = this.engine.escapeHtml(String(item.year || ''));
                const venue = this.engine.escapeHtml(item.journal || item.publisher || '');
                const typeLabel = (item.type || 'journal').toUpperCase();
                const doi = item.doi ? `<span class="px-1.5 py-0.5 rounded text-[10px] bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 font-mono">DOI: ${this.engine.escapeHtml(item.doi)}</span>` : '';

                card.innerHTML = `
                    <div class="flex-1 space-y-1">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-indigo-100 text-indigo-800 dark:bg-indigo-950/80 dark:text-indigo-300">${typeLabel}</span>
                            ${doi}
                            <span class="text-gray-400 text-[11px]">${year}</span>
                        </div>
                        <h4 class="font-bold text-gray-900 dark:text-gray-100 text-xs">${title}</h4>
                        <p class="text-[11px] text-gray-600 dark:text-gray-300">Penulis: <span class="font-medium">${author}</span></p>
                        ${venue ? `<p class="text-[11px] text-gray-500 dark:text-gray-400 font-serif italic">${venue}</p>` : ''}
                    </div>
                    <div class="flex sm:flex-col gap-1.5 shrink-0 self-end sm:self-start">
                        <button type="button" class="btn-cite-item px-3 py-1.5 rounded bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-[11px] shadow-2xs transition cursor-pointer flex items-center justify-center gap-1">
                            <span><i class="fa-solid fa-pen-nib mr-1"></i> Sisipkan Sitasi</span>
                        </button>
                        <button type="button" class="btn-save-item px-2.5 py-1.5 rounded bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 font-medium text-[11px] transition cursor-pointer flex items-center justify-center gap-1">
                            <span><i class="fa-solid fa-plus mr-1"></i> Simpan Sumber</span>
                        </button>
                    </div>
                `;

                const btnCite = card.querySelector('.btn-cite-item');
                const btnSave = card.querySelector('.btn-save-item');

                btnCite.addEventListener('click', () => {
                    this.engine.insertInTextCitation(item);
                    this.closeModal(modal);
                    this.showToast(`Sitasi "${item.title.substring(0, 30)}..." berhasil disisipkan di naskah!`);
                    updateSourceCountUI();
                });

                btnSave.addEventListener('click', () => {
                    this.engine.addSource(item);
                    btnSave.innerHTML = '<span><i class="fa-solid fa-check mr-1"></i> Tersimpan</span>';
                    btnSave.classList.add('bg-emerald-100', 'text-emerald-800', 'dark:bg-emerald-950', 'dark:text-emerald-300');
                    updateSourceCountUI();
                    this.showToast('Sumber referensi berhasil disimpan ke Pengelola Sumber!');
                });

                searchResultsList.appendChild(card);
            });
        };

        if (btnExecuteSearch) {
            btnExecuteSearch.addEventListener('click', executeSearch);
        }
        if (searchInput) {
            searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    executeSearch();
                }
            });
        }

        modal.querySelectorAll('.btn-search-chip').forEach(chip => {
            chip.addEventListener('click', () => {
                if (searchInput) {
                    searchInput.value = chip.textContent.trim();
                    executeSearch();
                }
            });
        });

        /* ------------------ Tab 2: Tambah Sumber Manual ------------------ */
        const getManualSourceData = () => {
            return {
                id: 'src-' + Date.now(),
                type: document.getElementById('citation-type')?.value || 'journal',
                author: document.getElementById('citation-author')?.value.trim() || 'Penulis',
                year: document.getElementById('citation-year')?.value.trim() || String(new Date().getFullYear()),
                title: document.getElementById('citation-title')?.value.trim() || 'Judul Referensi',
                journal: document.getElementById('citation-source')?.value.trim() || 'Jurnal / Penerbit',
                publisher: document.getElementById('citation-source')?.value.trim() || '',
                volume: document.getElementById('citation-volume')?.value.trim() || '',
                issue: document.getElementById('citation-issue')?.value.trim() || '',
                pages: document.getElementById('citation-pages')?.value.trim() || '',
                doi: document.getElementById('citation-doi')?.value.trim() || '',
                url: document.getElementById('citation-doi')?.value.trim() || ''
            };
        };

        const clearManualForm = () => {
            ['citation-author', 'citation-year', 'citation-title', 'citation-source', 'citation-volume', 'citation-issue', 'citation-pages', 'citation-doi'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = '';
            });
        };

        const btnConfirmCitation = document.getElementById('btn-confirm-citation');
        if (btnConfirmCitation) {
            btnConfirmCitation.addEventListener('click', () => {
                const data = getManualSourceData();
                this.engine.insertCitation(data.author, data.year, data.title, data.journal);
                this.closeModal(modal);
                this.showToast('Sitasi & Entri Daftar Pustaka berhasil disisipkan!');
                updateSourceCountUI();
            });
        }

        const btnSaveSourceOnly = document.getElementById('btn-save-source-only');
        if (btnSaveSourceOnly) {
            btnSaveSourceOnly.addEventListener('click', () => {
                const data = getManualSourceData();
                this.engine.addSource(data);
                clearManualForm();
                updateSourceCountUI();
                this.showToast('Sumber berhasil disimpan ke Pengelola Sumber Word!');
                switchTab('manage');
            });
        }

        /* ------------------ Tab 3: Kelola Sumber (Manage Sources) ------------------ */
        const sourceManagerContainer = document.getElementById('source-manager-items');
        const manageSearchFilter = document.getElementById('manage-source-filter');
        const btnAddSampleSources = document.getElementById('btn-add-sample-sources');
        const btnClearAllSources = document.getElementById('btn-clear-all-sources');

        const renderManageSources = () => {
            if (!sourceManagerContainer) return;
            const filterText = manageSearchFilter ? manageSearchFilter.value.trim().toLowerCase() : '';
            const allSources = this.engine.getSources ? this.engine.getSources() : [];
            const sources = allSources.filter(s => {
                if (!filterText) return true;
                const hay = `${s.title || ''} ${s.author || ''} ${s.journal || ''} ${s.year || ''}`.toLowerCase();
                return hay.includes(filterText);
            });

            updateSourceCountUI();

            if (sources.length === 0) {
                sourceManagerContainer.innerHTML = `<div class="p-6 text-center text-gray-500 dark:text-gray-400">
                    <p class="font-semibold text-xs">Belum ada sumber referensi ${filterText ? 'yang cocok dengan filter' : 'terdaftar'}.</p>
                    <p class="text-[11px] mt-1">Gunakan tab Mesin Pencari atau klik "+ Sampel Sumber Ilmiah" untuk mencoba.</p>
                </div>`;
                return;
            }

            sourceManagerContainer.innerHTML = '';
            sources.forEach((src, idx) => {
                const card = document.createElement('div');
                card.className = 'p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 flex items-center justify-between gap-3 text-xs';

                const author = this.engine.escapeHtml(src.author || 'Anonim');
                const title = this.engine.escapeHtml(src.title || 'Tanpa Judul');
                const year = this.engine.escapeHtml(String(src.year || ''));
                const venue = this.engine.escapeHtml(src.journal || src.publisher || '');
                const type = (src.type || 'journal').toUpperCase();

                card.innerHTML = `
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-1.5">
                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300">${type}</span>
                            <span class="font-bold text-gray-900 dark:text-gray-100 truncate">${title}</span>
                        </div>
                        <p class="text-[11px] text-gray-600 dark:text-gray-300 truncate mt-0.5">${author} (${year}) ${venue ? `— ${venue}` : ''}</p>
                    </div>
                    <div class="flex items-center gap-1.5 shrink-0">
                        <button type="button" class="btn-manage-cite px-2.5 py-1 rounded bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-[11px] transition cursor-pointer" title="Sisipkan tanda sitasi ke naskah">
                            Sisipkan
                        </button>
                        <button type="button" class="btn-manage-delete p-1 rounded hover:bg-red-100 dark:hover:bg-red-950/60 text-red-600 dark:text-red-400 text-xs transition cursor-pointer" title="Hapus sumber ini">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </div>
                `;

                card.querySelector('.btn-manage-cite').addEventListener('click', () => {
                    this.engine.insertInTextCitation(src);
                    this.closeModal(modal);
                    this.showToast(`Sitasi "${title.substring(0, 30)}..." berhasil disisipkan di naskah!`);
                });

                card.querySelector('.btn-manage-delete').addEventListener('click', () => {
                    this.engine.deleteSource(src.id);
                    renderManageSources();
                    this.showToast('Sumber referensi dihapus.');
                });

                sourceManagerContainer.appendChild(card);
            });
        };

        if (manageSearchFilter) {
            manageSearchFilter.addEventListener('input', renderManageSources);
        }

        if (btnAddSampleSources) {
            btnAddSampleSources.addEventListener('click', () => {
                this.engine.sources = this.engine.loadSavedSources();
                this.engine.saveSources();
                renderManageSources();
                this.showToast('Sampel sumber referensi ilmiah berhasil dimuat!');
            });
        }

        if (btnClearAllSources) {
            btnClearAllSources.addEventListener('click', () => {
                if (confirm('Apakah Anda yakin ingin mengosongkan semua daftar sumber pustaka?')) {
                    this.engine.sources = [];
                    this.engine.saveSources();
                    this.engine.updateBibliography();
                    renderManageSources();
                    this.showToast('Semua sumber pustaka berhasil dikosongkan.');
                }
            });
        }

        /* ------------------ Tab 4: Format & Sisipkan Daftar Pustaka ------------------ */
        const renderBibliographyPreview = () => {
            if (!previewBox) return;
            const style = (modalStyleSelect ? modalStyleSelect.value : null) || this.engine.citationStyle || 'apa';
            const heading = (modalHeadingInput ? modalHeadingInput.value : 'DAFTAR PUSTAKA') || 'DAFTAR PUSTAKA';

            const styleLabels = {
                apa: 'Standar APA 7th (Hanging Indent 0.5 in)',
                ieee: 'Standar IEEE (Numbered [1])',
                harvard: 'Standar Harvard (Author-Date)',
                mla: 'Standar MLA 9th (Hanging Indent)',
                chicago: 'Standar Chicago 17th',
                vancouver: 'Standar Vancouver (Numbered (1))'
            };
            if (previewBadge) {
                previewBadge.textContent = styleLabels[style] || style.toUpperCase();
            }

            const html = this.engine.generateBibliographyHtml(style, heading);
            previewBox.innerHTML = html;
        };

        if (modalStyleSelect) {
            modalStyleSelect.addEventListener('change', (e) => {
                const style = e.target.value;
                this.engine.citationStyle = style;
                if (ribbonStyleSelect) ribbonStyleSelect.value = style;
                renderBibliographyPreview();
            });
        }

        if (modalHeadingInput) {
            modalHeadingInput.addEventListener('input', renderBibliographyPreview);
        }

        const btnInsertBiblioBlock = document.getElementById('btn-insert-bibliography-block');
        if (btnInsertBiblioBlock) {
            btnInsertBiblioBlock.addEventListener('click', () => {
                const style = (modalStyleSelect ? modalStyleSelect.value : null) || this.engine.citationStyle || 'apa';
                const heading = (modalHeadingInput ? modalHeadingInput.value : 'DAFTAR PUSTAKA') || 'DAFTAR PUSTAKA';
                this.engine.insertBibliography(style, heading);
                this.closeModal(modal);
                this.showToast('Daftar Pustaka lengkap berhasil disisipkan ke dokumen!');
            });
        }

        const btnSyncBiblioBlock = document.getElementById('btn-sync-bibliography-block');
        if (btnSyncBiblioBlock) {
            btnSyncBiblioBlock.addEventListener('click', () => {
                const style = (modalStyleSelect ? modalStyleSelect.value : null) || this.engine.citationStyle || 'apa';
                this.engine.updateBibliography(style);
                renderBibliographyPreview();
                this.showToast('Daftar Pustaka di lembar naskah berhasil disinkronkan!');
            });
        }

        updateSourceCountUI();
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
            if (headerInput) headerInput.value = this.engine.customHeaderText || '';
            if (footerInput) footerInput.value = this.engine.customFooterText || '';
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
            tableToolbar.querySelector('[data-table-action="move"]')?.addEventListener('pointerdown', (event) => {
                if (this.engine.activeTable?.isConnected) this.engine.startDocumentObjectDrag(this.engine.activeTable, event);
            });
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
            imageToolbar.querySelector('#image-layout-mode')?.addEventListener('change', (event) => this.engine.setImageLayout(event.target.value));
            for (const axis of ['x', 'y']) {
                imageToolbar.querySelector(`#image-offset-${axis}`)?.addEventListener('change', () => {
                    const frame = this.engine.activeImage?.closest('.wordcraft-image-frame');
                    if (!frame || frame.dataset.imageMode !== 'floating') return;
                    const x = Number(imageToolbar.querySelector('#image-offset-x').value);
                    const y = Number(imageToolbar.querySelector('#image-offset-y').value);
                    this.engine.positionImage(frame, x, y);
                    this.engine.handleInput();
                });
            }
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
                const vRuler = document.getElementById('vertical-ruler');
                if (vRuler) vRuler.classList.toggle('hidden', !this.isRulerVisible);
            } else if (mode === 'web') {
                sheets.forEach(sheet => {
                    sheet.classList.remove('page-a4', 'shadow-2xl');
                    sheet.classList.add('w-full', 'max-w-4xl', 'shadow-sm');
                });
                gaps.forEach(g => g.classList.add('hidden'));
                if (ribbon) ribbon.classList.remove('hidden');
                if (topNav) topNav.classList.remove('hidden');
                if (ruler) ruler.classList.add('hidden');
                const vRuler = document.getElementById('vertical-ruler');
                if (vRuler) vRuler.classList.add('hidden');
            } else if (mode === 'zen') {
                sheets.forEach(sheet => {
                    sheet.classList.remove('page-a4', 'shadow-2xl');
                    sheet.classList.add('w-full', 'max-w-3xl', 'shadow-none');
                });
                gaps.forEach(g => g.classList.add('hidden'));
                if (ribbon) ribbon.classList.add('hidden');
                if (ruler) ruler.classList.add('hidden');
                const vRuler = document.getElementById('vertical-ruler');
                if (vRuler) vRuler.classList.add('hidden');
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
            this.engine.setMarginPreset('skripsi', false);
            this.engine.setFontFamily("'Times New Roman', serif", false);
            this.engine.setFontSize('12pt', false);
            this.engine.setLineSpacing('1.5', false);
            this.engine.setContent(sampleHtml);
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
            this.engine.setMarginPreset('normal', false);
            this.engine.setFontFamily("'Instrument Sans', sans-serif", false);
            this.engine.setFontSize('11pt', false);
            this.engine.setLineSpacing('1.5', false);
            this.engine.setContent(sampleHtml);
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
            } else if (e.key === 'F9') {
                e.preventDefault();
                const updateModal = document.getElementById('modal-update-toc');
                if (updateModal) this.openModal(updateModal);
            }
        });
    }

    /* -------------------------------------------------------------
     * Table of Contents Suite (Microsoft Word Standard)
     * ------------------------------------------------------------- */

    bindTableOfContentsSuite() {
        const customModal = document.getElementById('modal-custom-toc');
        const updateModal = document.getElementById('modal-update-toc');

        // Dropdown Gallery Menu
        const btnTocDropdown = document.getElementById('btn-toc-dropdown');
        const tocDropdownMenu = document.getElementById('toc-dropdown-menu');

        if (btnTocDropdown && tocDropdownMenu) {
            btnTocDropdown.addEventListener('click', (e) => {
                e.stopPropagation();
                tocDropdownMenu.classList.toggle('hidden');
            });

            document.addEventListener('click', (e) => {
                if (!e.target.closest('#toc-dropdown-wrapper')) {
                    tocDropdownMenu.classList.add('hidden');
                }
            });
        }

        // Dropdown actions
        document.querySelectorAll('[data-toc-action="insert-auto1"]').forEach(btn => {
            btn.addEventListener('click', () => {
                if (tocDropdownMenu) tocDropdownMenu.classList.add('hidden');
                const ok = this.engine.generateTableOfContents({ heading: 'KONTEN', templateType: 'auto1' });
                if (ok) {
                    this.showToast('Tabel Otomatis 1 (Konten) berhasil disisipkan!');
                } else {
                    this.showToast('Tidak ada judul bab/heading yang terdeteksi di dokumen.', 'warning');
                }
            });
        });

        document.querySelectorAll('[data-toc-action="insert-auto2"]').forEach(btn => {
            btn.addEventListener('click', () => {
                if (tocDropdownMenu) tocDropdownMenu.classList.add('hidden');
                const ok = this.engine.generateTableOfContents({ heading: 'DAFTAR ISI', templateType: 'auto2' });
                if (ok) {
                    this.showToast('Tabel Otomatis 2 (Daftar Isi) berhasil disisipkan!');
                } else {
                    this.showToast('Tidak ada judul bab/heading yang terdeteksi di dokumen.', 'warning');
                }
            });
        });

        document.querySelectorAll('[data-toc-action="insert-manual"]').forEach(btn => {
            btn.addEventListener('click', () => {
                if (tocDropdownMenu) tocDropdownMenu.classList.add('hidden');
                this.engine.generateTableOfContents({ heading: 'DAFTAR ISI', templateType: 'manual' });
                this.showToast('Tabel Manual berhasil disisipkan dengan templat!');
            });
        });

        document.querySelectorAll('[data-action="open-custom-toc-modal"]').forEach(btn => {
            btn.addEventListener('click', () => {
                if (tocDropdownMenu) tocDropdownMenu.classList.add('hidden');
                this.openModal(customModal);
                renderCustomTocPreview();
            });
        });

        document.querySelectorAll('[data-toc-action="remove-toc"]').forEach(btn => {
            btn.addEventListener('click', () => {
                if (tocDropdownMenu) tocDropdownMenu.classList.add('hidden');
                const ok = this.engine.removeTableOfContents();
                if (ok) {
                    this.showToast('Daftar Isi berhasil dihapus.');
                } else {
                    this.showToast('Tidak ditemukan Daftar Isi di naskah.', 'info');
                }
            });
        });

        // Quick Insert from Ribbon "Sisipkan (Insert)"
        const btnInsertToc = document.getElementById('btn-insert-toc');
        if (btnInsertToc) {
            btnInsertToc.addEventListener('click', () => {
                const activeContainer = this.engine.pagesWrapper || this.engine.canvasEl || document;
                const existingToc = activeContainer.querySelector('.table-of-contents, [data-toc-block="true"]');
                if (existingToc) {
                    this.openModal(updateModal);
                } else {
                    const ok = this.engine.generateTableOfContents({ heading: 'DAFTAR ISI', templateType: 'auto2' });
                    if (ok) {
                        this.showToast('Daftar Isi Otomatis berhasil disisipkan!');
                    } else {
                        this.openModal(customModal);
                        renderCustomTocPreview();
                    }
                }
            });
        }

        // Ribbon Update Button
        const btnRibbonUpdateToc = document.getElementById('btn-ribbon-update-toc');
        if (btnRibbonUpdateToc) {
            btnRibbonUpdateToc.addEventListener('click', () => {
                const activeContainer = this.engine.pagesWrapper || this.engine.canvasEl || document;
                const existingToc = activeContainer.querySelector('.table-of-contents, [data-toc-block="true"]');
                if (!existingToc) {
                    this.showToast('Belum ada Daftar Isi. Buat Daftar Isi terlebih dahulu.', 'warning');
                    return;
                }
                this.openModal(updateModal);
            });
        }

        // Ribbon Heading Level Selector (Add Text)
        const ribbonTocLevelSelect = document.getElementById('ribbon-toc-level-select');
        if (ribbonTocLevelSelect) {
            ribbonTocLevelSelect.addEventListener('change', (e) => {
                const val = e.target.value;
                this.engine.setSelectionHeadingLevel(val);
                e.target.value = '';
                this.showToast(`Level teks diatur ke: ${val === 'none' ? 'Teks Normal' : 'Level ' + val}`);
            });
        }

        // Window Events from TOC block hover buttons
        window.addEventListener('open-update-toc-dialog', () => {
            this.openModal(updateModal);
        });

        window.addEventListener('open-custom-toc-dialog', () => {
            this.openModal(customModal);
            renderCustomTocPreview();
        });

        // Custom TOC Modal Live Preview & Insertion
        const headingInput = document.getElementById('toc-heading-input');
        const showPageNumCheckbox = document.getElementById('toc-show-page-numbers');
        const rightAlignCheckbox = document.getElementById('toc-right-align-pages');
        const leaderSelect = document.getElementById('toc-tab-leader-select');
        const styleSelect = document.getElementById('toc-style-select');
        const levelsSelect = document.getElementById('toc-levels-select');
        const previewContainer = document.getElementById('toc-preview-container');
        const previewBadge = document.getElementById('toc-preview-badge');

        const getCustomOptions = () => ({
            heading: (headingInput ? headingInput.value : 'DAFTAR ISI') || 'DAFTAR ISI',
            showPageNumbers: showPageNumCheckbox ? showPageNumCheckbox.checked : true,
            rightAlignPageNumbers: rightAlignCheckbox ? rightAlignCheckbox.checked : true,
            leader: leaderSelect ? leaderSelect.value : 'dots',
            style: styleSelect ? styleSelect.value : 'template',
            levels: levelsSelect ? parseInt(levelsSelect.value, 10) : 3,
        });

        const renderCustomTocPreview = () => {
            if (!previewContainer) return;
            const opts = getCustomOptions();
            if (previewBadge) previewBadge.textContent = `Level 1 - ${opts.levels}`;

            const html = this.engine.generateTableOfContentsHtml(opts);
            previewContainer.innerHTML = html;
        };

        [headingInput, showPageNumCheckbox, rightAlignCheckbox, leaderSelect, styleSelect, levelsSelect].forEach(el => {
            if (el) {
                el.addEventListener('input', renderCustomTocPreview);
                el.addEventListener('change', renderCustomTocPreview);
            }
        });

        const btnConfirmInsertToc = document.getElementById('btn-confirm-insert-toc');
        if (btnConfirmInsertToc) {
            btnConfirmInsertToc.addEventListener('click', () => {
                const opts = getCustomOptions();
                const ok = this.engine.generateTableOfContents(opts);
                this.closeModal(customModal);
                if (ok) {
                    this.showToast('Daftar Isi berhasil disisipkan sesuai kustomisasi!');
                } else {
                    this.showToast('Daftar Isi berhasil disisipkan dengan templat!', 'info');
                }
            });
        }

        // Update Modal Confirmation
        const btnConfirmUpdateToc = document.getElementById('btn-confirm-update-toc');
        if (btnConfirmUpdateToc) {
            btnConfirmUpdateToc.addEventListener('click', () => {
                const selectedRadio = document.querySelector('input[name="toc-update-scope"]:checked');
                const scope = selectedRadio ? selectedRadio.value : 'entire';

                this.engine.updateTableOfContents(scope);
                this.closeModal(updateModal);

                if (scope === 'page_numbers') {
                    this.showToast('Nomor halaman Daftar Isi berhasil diperbarui!');
                } else {
                    this.showToast('Seluruh Daftar Isi berhasil dipindai dan diperbarui!');
                }
            });
        }
    }

    /* -------------------------------------------------------------
     * Page Setup & Paragraph Formatting Suite (Microsoft Word Parity)
     * ------------------------------------------------------------- */

    bindPageSetupAndParagraphSuite() {
        // 1. Home Ribbon Line Spacing Dropdown Menu
        const btnLineSpacingDropdown = document.getElementById('btn-line-spacing-dropdown');
        const lineSpacingMenu = document.getElementById('line-spacing-dropdown-menu');

        if (btnLineSpacingDropdown && lineSpacingMenu) {
            btnLineSpacingDropdown.addEventListener('click', (e) => {
                e.stopPropagation();
                lineSpacingMenu.classList.toggle('hidden');
            });

            document.addEventListener('click', (e) => {
                if (!lineSpacingMenu.contains(e.target) && !btnLineSpacingDropdown.contains(e.target)) {
                    lineSpacingMenu.classList.add('hidden');
                }
            });

            lineSpacingMenu.querySelectorAll('[data-spacing-val]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const val = btn.getAttribute('data-spacing-val');
                    this.engine.setLineSpacing(val);
                    lineSpacingMenu.classList.add('hidden');
                    this.showToast(`Spasi baris diatur ke ${val}`);
                });
            });

            const btnAddBefore = lineSpacingMenu.querySelector('[data-action="add-space-before"]');
            if (btnAddBefore) {
                btnAddBefore.addEventListener('click', () => {
                    this.engine.addSpaceBeforeParagraph(12);
                    lineSpacingMenu.classList.add('hidden');
                    this.showToast('Menambahkan spasi sebelum paragraf');
                });
            }

            const btnRemBefore = lineSpacingMenu.querySelector('[data-action="remove-space-before"]');
            if (btnRemBefore) {
                btnRemBefore.addEventListener('click', () => {
                    this.engine.removeSpaceBeforeParagraph();
                    lineSpacingMenu.classList.add('hidden');
                    this.showToast('Menghapus spasi sebelum paragraf');
                });
            }

            const btnAddAfter = lineSpacingMenu.querySelector('[data-action="add-space-after"]');
            if (btnAddAfter) {
                btnAddAfter.addEventListener('click', () => {
                    this.engine.addSpaceAfterParagraph(12);
                    lineSpacingMenu.classList.add('hidden');
                    this.showToast('Menambahkan spasi sesudah paragraf');
                });
            }

            const btnRemAfter = lineSpacingMenu.querySelector('[data-action="remove-space-after"]');
            if (btnRemAfter) {
                btnRemAfter.addEventListener('click', () => {
                    this.engine.removeSpaceAfterParagraph();
                    lineSpacingMenu.classList.add('hidden');
                    this.showToast('Menghapus spasi sesudah paragraf');
                });
            }
        }

        // Paragraph Dialog Launchers (Home & Layout ribbons & line spacing menu)
        document.querySelectorAll('[data-action="open-paragraph-modal"], #btn-open-paragraph-modal, #btn-layout-open-paragraph-modal, #btn-menu-open-paragraph-modal').forEach(btn => {
            btn.addEventListener('click', () => {
                if (lineSpacingMenu) lineSpacingMenu.classList.add('hidden');
                this.openParagraphModal();
            });
        });

        // Page Setup Dialog Launchers
        document.querySelectorAll('[data-action="page-setup-modal"], #btn-open-page-setup-modal').forEach(btn => {
            btn.addEventListener('click', () => {
                this.openPageSetupModal();
            });
        });

        // Columns Dropdown
        const btnColumnsDropdown = document.getElementById('btn-columns-dropdown');
        const columnsMenu = document.getElementById('columns-dropdown-menu');
        if (btnColumnsDropdown && columnsMenu) {
            btnColumnsDropdown.addEventListener('click', (e) => {
                e.stopPropagation();
                columnsMenu.classList.toggle('hidden');
            });

            document.addEventListener('click', (e) => {
                if (!columnsMenu.contains(e.target) && !btnColumnsDropdown.contains(e.target)) {
                    columnsMenu.classList.add('hidden');
                }
            });

            columnsMenu.querySelectorAll('[data-column-val]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const val = btn.getAttribute('data-column-val');
                    this.engine.setColumns(val);
                    columnsMenu.classList.add('hidden');
                    const label = val === '1' ? '1 kolom' : val === '2' ? '2 kolom' : val === '3' ? '3 kolom' : `kolom ${val}`;
                    this.showToast(`Tata letak diatur ke ${label}`);
                });
            });
        }

        // Breaks Dropdown
        const btnBreaksDropdown = document.getElementById('btn-breaks-dropdown');
        const breaksMenu = document.getElementById('breaks-dropdown-menu');
        if (btnBreaksDropdown && breaksMenu) {
            btnBreaksDropdown.addEventListener('click', (e) => {
                e.stopPropagation();
                breaksMenu.classList.toggle('hidden');
            });

            document.addEventListener('click', (e) => {
                if (!breaksMenu.contains(e.target) && !btnBreaksDropdown.contains(e.target)) {
                    breaksMenu.classList.add('hidden');
                }
            });

            breaksMenu.querySelectorAll('[data-break-type]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const type = btn.getAttribute('data-break-type');
                    breaksMenu.classList.add('hidden');
                    if (type === 'page') {
                        this.engine.insertPageBreak();
                        this.showToast('Pemisah halaman (Page Break) disisipkan');
                    } else if (type === 'column') {
                        this.engine.insertColumnBreak();
                        this.showToast('Pemisah kolom (Column Break) disisipkan');
                    } else if (type === 'section-next') {
                        this.engine.insertSectionBreak('newpage');
                        this.showToast('Pemisah bagian (Halaman Berikutnya) disisipkan');
                    } else if (type === 'section-continuous') {
                        this.engine.insertSectionBreak('continuous');
                        this.showToast('Pemisah bagian (Continuous) disisipkan');
                    }
                });
            });
        }

        // Hyphenation Dropdown
        const btnHyphenationDropdown = document.getElementById('btn-hyphenation-dropdown');
        const hyphenationMenu = document.getElementById('hyphenation-dropdown-menu');
        if (btnHyphenationDropdown && hyphenationMenu) {
            btnHyphenationDropdown.addEventListener('click', (e) => {
                e.stopPropagation();
                hyphenationMenu.classList.toggle('hidden');
            });

            document.addEventListener('click', (e) => {
                if (!hyphenationMenu.contains(e.target) && !btnHyphenationDropdown.contains(e.target)) {
                    hyphenationMenu.classList.add('hidden');
                }
            });

            hyphenationMenu.querySelectorAll('[data-hyphen-val]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const val = btn.getAttribute('data-hyphen-val');
                    this.engine.setHyphenation(val);
                    hyphenationMenu.classList.add('hidden');
                    this.showToast(`Tanda hubung diatur ke ${val === 'auto' ? 'Otomatis' : 'Tidak Ada'}`);
                });
            });
        }

        // Tab Stops Modal Launchers
        document.querySelectorAll('[data-action="open-tabs-modal"], #btn-open-tabs-modal, #btn-layout-open-tabs-modal').forEach(btn => {
            btn.addEventListener('click', () => {
                this.openTabsModal();
            });
        });

        // Tab Stops Modal Controls
        const tabsModal = document.getElementById('modal-tabs-format');
        if (tabsModal) {
            const tabStopPosInput = document.getElementById('tab-stop-pos');
            const tabDefaultStopInput = document.getElementById('tab-default-stop');
            const tabStopsList = document.getElementById('tab-stops-list');
            const btnTabSet = document.getElementById('btn-tab-set');
            const btnTabClear = document.getElementById('btn-tab-clear');
            const btnTabClearAll = document.getElementById('btn-tab-clear-all');
            const btnConfirmTabs = document.getElementById('btn-confirm-tabs');

            if (tabStopsList) {
                tabStopsList.addEventListener('change', () => {
                    const selOption = tabStopsList.selectedOptions[0];
                    if (selOption) {
                        const val = parseFloat(selOption.value) || 0;
                        if (tabStopPosInput) tabStopPosInput.value = val;
                        const align = selOption.getAttribute('data-align') || 'left';
                        const leader = selOption.getAttribute('data-leader') || 'none';
                        const alignRadio = tabsModal.querySelector(`input[name="tab-align-radio"][value="${align}"]`);
                        if (alignRadio) alignRadio.checked = true;
                        const leaderRadio = tabsModal.querySelector(`input[name="tab-leader-radio"][value="${leader}"]`);
                        if (leaderRadio) leaderRadio.checked = true;
                    }
                });
            }

            if (btnTabSet && tabStopsList && tabStopPosInput) {
                btnTabSet.addEventListener('click', () => {
                    const pos = parseFloat(tabStopPosInput.value);
                    if (isNaN(pos) || pos <= 0) {
                        this.showToast('Masukkan nilai posisi tab yang valid (lebih besar dari 0)', 'warning');
                        return;
                    }
                    const alignRadio = tabsModal.querySelector('input[name="tab-align-radio"]:checked');
                    const align = alignRadio ? alignRadio.value : 'left';
                    const leaderRadio = tabsModal.querySelector('input[name="tab-leader-radio"]:checked');
                    const leader = leaderRadio ? leaderRadio.value : 'none';

                    // Check if already exists in list, update or add
                    let existing = Array.from(tabStopsList.options).find(opt => parseFloat(opt.value) === pos);
                    if (!existing) {
                        existing = document.createElement('option');
                        tabStopsList.appendChild(existing);
                    }
                    existing.value = pos.toFixed(2);
                    existing.setAttribute('data-align', align);
                    existing.setAttribute('data-leader', leader);
                    const alignLabel = align === 'center' ? 'Tengah' : align === 'right' ? 'Kanan' : 'Kiri';
                    existing.textContent = `${pos.toFixed(2)} cm (${alignLabel})`;

                    // Sort options numerically
                    const sorted = Array.from(tabStopsList.options).sort((a, b) => parseFloat(a.value) - parseFloat(b.value));
                    tabStopsList.innerHTML = '';
                    sorted.forEach(opt => tabStopsList.appendChild(opt));
                    existing.selected = true;
                });
            }

            if (btnTabClear && tabStopsList) {
                btnTabClear.addEventListener('click', () => {
                    const selIndex = tabStopsList.selectedIndex;
                    if (selIndex >= 0) {
                        tabStopsList.remove(selIndex);
                    }
                });
            }

            if (btnTabClearAll && tabStopsList) {
                btnTabClearAll.addEventListener('click', () => {
                    tabStopsList.innerHTML = '';
                });
            }

            if (btnConfirmTabs) {
                btnConfirmTabs.addEventListener('click', () => {
                    const defaultStop = parseFloat(tabDefaultStopInput ? tabDefaultStopInput.value : 1.27) || 1.27;
                    const stops = [];
                    if (tabStopsList) {
                        Array.from(tabStopsList.options).forEach(opt => {
                            stops.push({
                                pos: parseFloat(opt.value),
                                align: opt.getAttribute('data-align') || 'left',
                                leader: opt.getAttribute('data-leader') || 'none',
                            });
                        });
                    }
                    this.engine.setTabStops(stops, defaultStop);
                    this.closeModal(tabsModal);
                    this.showToast('Perhentian tab (Tabs) berhasil diterapkan!', 'success');
                });
            }
        }

        // Line Numbers Dropdown
        const btnLineNumbersDropdown = document.getElementById('btn-line-numbers-dropdown');
        const lineNumbersMenu = document.getElementById('line-numbers-dropdown-menu');
        if (btnLineNumbersDropdown && lineNumbersMenu) {
            btnLineNumbersDropdown.addEventListener('click', (e) => {
                e.stopPropagation();
                lineNumbersMenu.classList.toggle('hidden');
            });
            document.addEventListener('click', (e) => {
                if (!lineNumbersMenu.contains(e.target) && !btnLineNumbersDropdown.contains(e.target)) {
                    lineNumbersMenu.classList.add('hidden');
                }
            });
            lineNumbersMenu.querySelectorAll('[data-line-number-val]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const val = btn.getAttribute('data-line-number-val');
                    lineNumbersMenu.classList.add('hidden');
                    if (val === 'suppress') {
                        const paras = this.engine.getSelectedParagraphElements();
                        paras.forEach(p => p.classList.toggle('suppress-line-numbers'));
                        this.showToast('Penomoran baris untuk paragraf aktif dialihkan');
                    } else {
                        this.engine.setLineNumbers(val);
                        const label = val === 'none' ? 'dinonaktifkan' : (val === 'continuous' ? 'berkelanjutan' : 'mulai ulang tiap halaman');
                        this.showToast(`Penomoran baris ${label}`);
                    }
                });
            });
        }

        // Line Numbers Modal Launchers
        document.querySelectorAll('[data-action="open-line-numbers-modal"]').forEach(btn => {
            btn.addEventListener('click', () => {
                if (lineNumbersMenu) lineNumbersMenu.classList.add('hidden');
                this.openLineNumbersModal();
            });
        });
        const lineNumbersModal = document.getElementById('modal-line-numbers');
        if (lineNumbersModal) {
            const btnConfirmLn = document.getElementById('btn-confirm-line-numbers');
            if (btnConfirmLn) {
                btnConfirmLn.addEventListener('click', () => {
                    const modeRadio = lineNumbersModal.querySelector('input[name="ln-mode-radio"]:checked');
                    const mode = modeRadio ? modeRadio.value : 'none';
                    const startAt = parseInt(document.getElementById('ln-start-at')?.value, 10) || 1;
                    const countBy = parseInt(document.getElementById('ln-count-by')?.value, 10) || 1;
                    this.engine.setLineNumbers(mode, { startAt, countBy });
                    this.closeModal(lineNumbersModal);
                    this.showToast('Pengaturan penomoran baris berhasil diterapkan!');
                });
            }
        }

        // Watermark Dropdown
        const btnWatermarkDropdown = document.getElementById('btn-watermark-dropdown');
        const watermarkMenu = document.getElementById('watermark-dropdown-menu');
        if (btnWatermarkDropdown && watermarkMenu) {
            btnWatermarkDropdown.addEventListener('click', (e) => {
                e.stopPropagation();
                watermarkMenu.classList.toggle('hidden');
            });
            document.addEventListener('click', (e) => {
                if (!watermarkMenu.contains(e.target) && !btnWatermarkDropdown.contains(e.target)) {
                    watermarkMenu.classList.add('hidden');
                }
            });
            watermarkMenu.querySelectorAll('[data-watermark-preset]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const preset = btn.getAttribute('data-watermark-preset');
                    watermarkMenu.classList.add('hidden');
                    const color = preset === 'RAHASIA' ? '#ef4444' : '#9ca3af';
                    this.engine.setWatermark({
                        text: preset,
                        font: "'Times New Roman', serif",
                        size: 72,
                        color,
                        opacity: 0.25,
                        layout: 'diagonal'
                    });
                    this.showToast(`Cap air "${preset}" diterapkan!`);
                });
            });
            watermarkMenu.querySelectorAll('[data-action="remove-watermark"]').forEach(btn => {
                btn.addEventListener('click', () => {
                    watermarkMenu.classList.add('hidden');
                    this.engine.removeWatermark();
                    this.showToast('Cap air dihapus');
                });
            });
        }

        // Watermark Modal Launchers
        document.querySelectorAll('[data-action="open-watermark-modal"]').forEach(btn => {
            btn.addEventListener('click', () => {
                if (watermarkMenu) watermarkMenu.classList.add('hidden');
                this.openWatermarkModal();
            });
        });
        const watermarkModal = document.getElementById('modal-watermark');
        if (watermarkModal) {
            const btnConfirmWm = document.getElementById('btn-confirm-watermark');
            if (btnConfirmWm) {
                btnConfirmWm.addEventListener('click', () => {
                    const text = document.getElementById('wm-text-input')?.value?.trim() || 'DRAF';
                    const font = document.getElementById('wm-font-select')?.value || "'Times New Roman', serif";
                    const sizeRaw = document.getElementById('wm-size-select')?.value || '72';
                    const size = sizeRaw === 'auto' ? 72 : parseInt(sizeRaw, 10);
                    const color = document.getElementById('wm-color-input')?.value || '#9ca3af';
                    const isSemitrans = Boolean(document.getElementById('wm-semitransparent')?.checked);
                    const layoutRadio = watermarkModal.querySelector('input[name="wm-layout-radio"]:checked');
                    const layout = layoutRadio ? layoutRadio.value : 'diagonal';

                    this.engine.setWatermark({
                        text, font, size, color, opacity: isSemitrans ? 0.25 : 0.6, layout
                    });
                    this.closeModal(watermarkModal);
                    this.showToast(`Cap air "${text}" berhasil diterapkan!`);
                });
            }
            const btnRemoveWm = document.getElementById('btn-remove-watermark-dialog');
            if (btnRemoveWm) {
                btnRemoveWm.addEventListener('click', () => {
                    this.engine.removeWatermark();
                    this.closeModal(watermarkModal);
                    this.showToast('Cap air berhasil dihapus');
                });
            }
        }

        // Page Color Dropdown
        const btnPageColorDropdown = document.getElementById('btn-page-color-dropdown');
        const pageColorMenu = document.getElementById('page-color-dropdown-menu');
        if (btnPageColorDropdown && pageColorMenu) {
            btnPageColorDropdown.addEventListener('click', (e) => {
                e.stopPropagation();
                pageColorMenu.classList.toggle('hidden');
            });
            document.addEventListener('click', (e) => {
                if (!pageColorMenu.contains(e.target) && !btnPageColorDropdown.contains(e.target)) {
                    pageColorMenu.classList.add('hidden');
                }
            });
            pageColorMenu.querySelectorAll('[data-page-color]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const color = btn.getAttribute('data-page-color');
                    pageColorMenu.classList.add('hidden');
                    if (color === 'none') {
                        this.engine.setPageColor('#ffffff');
                        this.showToast('Warna halaman dikembalikan ke standar');
                    } else {
                        this.engine.setPageColor(color);
                        this.showToast('Warna halaman berhasil diubah');
                    }
                });
            });
            const inputCustomColor = document.getElementById('input-custom-page-color');
            if (inputCustomColor) {
                inputCustomColor.addEventListener('change', () => {
                    pageColorMenu.classList.add('hidden');
                    this.engine.setPageColor(inputCustomColor.value);
                    this.showToast('Warna halaman kustom diterapkan');
                });
            }
        }

        // Page Borders Modal Launchers
        document.querySelectorAll('[data-action="open-page-borders-modal"], #btn-open-page-borders-modal').forEach(btn => {
            btn.addEventListener('click', () => {
                this.openPageBordersModal();
            });
        });
        const pageBordersModal = document.getElementById('modal-page-borders');
        if (pageBordersModal) {
            const btnConfirmPb = document.getElementById('btn-confirm-page-border');
            if (btnConfirmPb) {
                btnConfirmPb.addEventListener('click', () => {
                    const settingRadio = pageBordersModal.querySelector('input[name="pb-setting-radio"]:checked');
                    const setting = settingRadio ? settingRadio.value : 'box';
                    const style = document.getElementById('pb-style-select')?.value || 'solid';
                    const width = document.getElementById('pb-width-select')?.value || '1.5pt';
                    const color = document.getElementById('pb-color-input')?.value || '#000000';
                    const insetMm = parseFloat(document.getElementById('pb-margin-inset')?.value) || 15;
                    const applyTo = document.getElementById('pb-apply-to')?.value || 'document';

                    if (setting === 'none') {
                        this.engine.removePageBorder();
                        this.showToast('Batas halaman dihapus');
                    } else {
                        this.engine.setPageBorder({
                            setting, style, width, color, insetMm, applyTo
                        });
                        this.showToast('Batas halaman berhasil diterapkan!');
                    }
                    this.closeModal(pageBordersModal);
                });
            }
            const btnRemovePb = document.getElementById('btn-remove-border-dialog');
            if (btnRemovePb) {
                btnRemovePb.addEventListener('click', () => {
                    this.engine.removePageBorder();
                    this.closeModal(pageBordersModal);
                    this.showToast('Batas halaman dihapus');
                });
            }
        }

        // 2. Layout Ribbon Paragraph Quick Inputs (Indent & Spacing)
        const layoutIndentLeft = document.getElementById('layout-indent-left');
        const layoutIndentRight = document.getElementById('layout-indent-right');
        const layoutSpacingBefore = document.getElementById('layout-spacing-before');
        const layoutSpacingAfter = document.getElementById('layout-spacing-after');

        if (layoutIndentLeft) {
            const handleIndent = () => {
                const left = parseFloat(layoutIndentLeft.value) || 0;
                const right = parseFloat(layoutIndentRight ? layoutIndentRight.value : 0) || 0;
                this.engine.setParagraphIndent(left, right);
            };
            layoutIndentLeft.addEventListener('change', handleIndent);
        }
        if (layoutIndentRight) {
            const handleIndent = () => {
                const left = parseFloat(layoutIndentLeft ? layoutIndentLeft.value : 0) || 0;
                const right = parseFloat(layoutIndentRight.value) || 0;
                this.engine.setParagraphIndent(left, right);
            };
            layoutIndentRight.addEventListener('change', handleIndent);
        }
        if (layoutSpacingBefore) {
            const handleSpacing = () => {
                const before = parseFloat(layoutSpacingBefore.value) || 0;
                const after = parseFloat(layoutSpacingAfter ? layoutSpacingAfter.value : 0) || 0;
                this.engine.setParagraphSpacing(before, after);
            };
            layoutSpacingBefore.addEventListener('change', handleSpacing);
        }
        if (layoutSpacingAfter) {
            const handleSpacing = () => {
                const before = parseFloat(layoutSpacingBefore ? layoutSpacingBefore.value : 0) || 0;
                const after = parseFloat(layoutSpacingAfter.value) || 0;
                this.engine.setParagraphSpacing(before, after);
            };
            layoutSpacingAfter.addEventListener('change', handleSpacing);
        }

        // Selection Change -> Sync Layout Ribbon Quick Inputs
        let debounceTimer = null;
        const syncQuickInputsWithSelection = () => {
            if (debounceTimer) clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                const format = this.engine.getActiveParagraphFormat();
                if (layoutIndentLeft && document.activeElement !== layoutIndentLeft) {
                    layoutIndentLeft.value = (format.indentLeft || 0).toFixed(2).replace(/\.?0+$/, '');
                }
                if (layoutIndentRight && document.activeElement !== layoutIndentRight) {
                    layoutIndentRight.value = (format.indentRight || 0).toFixed(2).replace(/\.?0+$/, '');
                }
                if (layoutSpacingBefore && document.activeElement !== layoutSpacingBefore) {
                    layoutSpacingBefore.value = Math.round(format.spacingBefore || 0);
                }
                if (layoutSpacingAfter && document.activeElement !== layoutSpacingAfter) {
                    layoutSpacingAfter.value = Math.round(format.spacingAfter || 6);
                }

                // Synchronize Home ribbon alignment buttons
                document.querySelectorAll('[data-align]').forEach(btn => {
                    const a = btn.getAttribute('data-align');
                    if (a === format.alignment) {
                        btn.classList.add('bg-indigo-100', 'text-indigo-700', 'dark:bg-indigo-900/60', 'dark:text-indigo-300', 'font-bold');
                        btn.classList.remove('text-gray-700', 'dark:text-gray-300');
                    } else {
                        btn.classList.remove('bg-indigo-100', 'text-indigo-700', 'dark:bg-indigo-900/60', 'dark:text-indigo-300', 'font-bold');
                        btn.classList.add('text-gray-700', 'dark:text-gray-300');
                    }
                });

                // Synchronize line spacing select
                const lsSelect = document.getElementById('line-spacing-select');
                if (lsSelect && document.activeElement !== lsSelect && format.lineSpacing) {
                    lsSelect.value = String(format.lineSpacing);
                }
            }, 150);
        };

        document.addEventListener('selectionchange', syncQuickInputsWithSelection);
        if (this.engine.editorEl) {
            this.engine.editorEl.addEventListener('keyup', syncQuickInputsWithSelection);
            this.engine.editorEl.addEventListener('mouseup', syncQuickInputsWithSelection);
        }

        // 3. Page Setup Modal Logic
        const pageSetupModal = document.getElementById('modal-page-setup');
        if (pageSetupModal) {
            // Tab switching
            pageSetupModal.querySelectorAll('[data-page-setup-tab]').forEach(tabBtn => {
                tabBtn.addEventListener('click', () => {
                    const target = tabBtn.getAttribute('data-page-setup-tab');
                    pageSetupModal.querySelectorAll('[data-page-setup-tab]').forEach(b => {
                        b.classList.remove('border-indigo-600', 'text-indigo-600', 'dark:text-indigo-400', 'font-bold');
                        b.classList.add('border-transparent', 'text-gray-500');
                    });
                    tabBtn.classList.add('border-indigo-600', 'text-indigo-600', 'dark:text-indigo-400', 'font-bold');
                    tabBtn.classList.remove('border-transparent', 'text-gray-500');

                    pageSetupModal.querySelectorAll('.page-setup-tab-pane').forEach(pane => pane.classList.add('hidden'));
                    const targetPane = document.getElementById(`tab-ps-${target}`);
                    if (targetPane) targetPane.classList.remove('hidden');
                });
            });

            // Orientation Cards
            pageSetupModal.querySelectorAll('.ps-orient-card').forEach(card => {
                card.addEventListener('click', () => {
                    const orient = card.getAttribute('data-orient');
                    const radio = card.querySelector('input[type="radio"]');
                    if (radio) radio.checked = true;

                    pageSetupModal.querySelectorAll('.ps-orient-card').forEach(c => {
                        c.classList.remove('border-2', 'border-indigo-600', 'bg-indigo-50/50', 'dark:bg-indigo-950/30');
                        c.classList.add('border', 'border-gray-200', 'dark:border-gray-700', 'bg-gray-50/50', 'dark:bg-gray-800/40');
                    });
                    card.classList.remove('border', 'border-gray-200', 'dark:border-gray-700', 'bg-gray-50/50', 'dark:bg-gray-800/40');
                    card.classList.add('border-2', 'border-indigo-600', 'bg-indigo-50/50', 'dark:bg-indigo-950/30');

                    this.updatePageSetupPreview();
                });
            });

            // Margin Presets in Page Setup Modal
            pageSetupModal.querySelectorAll('[data-ps-preset]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const preset = btn.getAttribute('data-ps-preset');
                    pageSetupModal.querySelectorAll('[data-ps-preset]').forEach(b => {
                        b.classList.remove('border-indigo-300', 'dark:border-indigo-800', 'bg-indigo-50', 'dark:bg-indigo-950/60', 'text-indigo-700', 'dark:text-indigo-300', 'font-semibold');
                        b.classList.add('border-gray-200', 'dark:border-gray-700');
                    });
                    btn.classList.add('border-indigo-300', 'dark:border-indigo-800', 'bg-indigo-50', 'dark:bg-indigo-950/60', 'text-indigo-700', 'dark:text-indigo-300', 'font-semibold');

                    const pDef = MARGIN_PRESETS[preset];
                    if (pDef) {
                        const topInput = document.getElementById('ps-margin-top');
                        const btmInput = document.getElementById('ps-margin-bottom');
                        const leftInput = document.getElementById('ps-margin-left');
                        const rightInput = document.getElementById('ps-margin-right');
                        if (topInput) topInput.value = (pDef.top / 10).toFixed(preset === 'skripsi' ? 1 : 2);
                        if (btmInput) btmInput.value = (pDef.bottom / 10).toFixed(preset === 'skripsi' ? 1 : 2);
                        if (leftInput) leftInput.value = (pDef.left / 10).toFixed(preset === 'skripsi' ? 1 : 2);
                        if (rightInput) rightInput.value = (pDef.right / 10).toFixed(preset === 'skripsi' ? 1 : 2);
                        this.updatePageSetupPreview();
                    }
                });
            });

            // Inputs change -> update preview
            ['ps-margin-top', 'ps-margin-bottom', 'ps-margin-left', 'ps-margin-right', 'ps-gutter', 'ps-gutter-pos', 'ps-custom-width', 'ps-custom-height'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.addEventListener('input', () => this.updatePageSetupPreview());
            });

            // Paper Size Select
            const psSizeSelect = document.getElementById('ps-paper-size-select');
            if (psSizeSelect) {
                psSizeSelect.addEventListener('change', () => {
                    const val = psSizeSelect.value;
                    if (val !== 'custom' && PAPER_DIMENSIONS[val]) {
                        const dim = PAPER_DIMENSIONS[val];
                        const wIn = document.getElementById('ps-custom-width');
                        const hIn = document.getElementById('ps-custom-height');
                        if (wIn) wIn.value = (dim.width / 10).toFixed(1);
                        if (hIn) hIn.value = (dim.height / 10).toFixed(1);
                    }
                    this.updatePageSetupPreview();
                });
            }

            // Multiple Pages Select
            const psMultiplePages = document.getElementById('ps-multiple-pages');
            if (psMultiplePages) {
                psMultiplePages.addEventListener('change', () => {
                    const isMirror = psMultiplePages.value === 'mirror';
                    const lblLeft = document.getElementById('lbl-ps-margin-left');
                    const lblRight = document.getElementById('lbl-ps-margin-right');
                    if (lblLeft) lblLeft.textContent = isMirror ? 'Di Dalam (Inside):' : 'Kiri (Left):';
                    if (lblRight) lblRight.textContent = isMirror ? 'Di Luar (Outside):' : 'Kanan (Right):';
                });
            }

            // Confirm Page Setup Button
            const btnConfirmPageSetup = document.getElementById('btn-confirm-page-setup');
            if (btnConfirmPageSetup) {
                btnConfirmPageSetup.addEventListener('click', () => {
                    const top = (parseFloat(document.getElementById('ps-margin-top')?.value) || 2.54) * 10;
                    const bottom = (parseFloat(document.getElementById('ps-margin-bottom')?.value) || 2.54) * 10;
                    const left = (parseFloat(document.getElementById('ps-margin-left')?.value) || 2.54) * 10;
                    const right = (parseFloat(document.getElementById('ps-margin-right')?.value) || 2.54) * 10;
                    const gutter = (parseFloat(document.getElementById('ps-gutter')?.value) || 0) * 10;
                    const gutterPos = document.getElementById('ps-gutter-pos')?.value || 'left';

                    const orientRadio = pageSetupModal.querySelector('input[name="ps-orient-radio"]:checked');
                    const orientation = orientRadio ? orientRadio.value : 'portrait';

                    const paperSize = document.getElementById('ps-paper-size-select')?.value || 'a4';
                    const customWidth = (parseFloat(document.getElementById('ps-custom-width')?.value) || 21) * 10;
                    const customHeight = (parseFloat(document.getElementById('ps-custom-height')?.value) || 29.7) * 10;
                    const multiplePages = document.getElementById('ps-multiple-pages')?.value || 'normal';

                    const sectionStart = document.getElementById('ps-section-start')?.value || 'newpage';
                    const diffOddEven = Boolean(document.getElementById('ps-diff-odd-even')?.checked);
                    const diffFirstPage = Boolean(document.getElementById('ps-diff-first-page')?.checked);
                    const edgeHeader = (parseFloat(document.getElementById('ps-edge-header')?.value) || 1.5) * 10;
                    const edgeFooter = (parseFloat(document.getElementById('ps-edge-footer')?.value) || 1.5) * 10;
                    const verticalAlign = document.getElementById('ps-vert-align')?.value || 'top';

                    const scope = document.getElementById('ps-apply-scope')?.value || 'document';

                    this.engine.setPageSetup({
                        margins: { top, bottom, left, right, gutter, gutterPos, multiplePages },
                        paperSize,
                        customWidth,
                        customHeight,
                        orientation,
                        multiplePages,
                        sectionStart,
                        diffOddEven,
                        diffFirstPage,
                        edgeHeader,
                        edgeFooter,
                        verticalAlign,
                        scope
                    });

                    this.closeModal(pageSetupModal);
                    this.showToast('Penyetelan halaman Microsoft Word berhasil diterapkan!', 'success');
                });
            }

            // Set as Default Page Setup Button
            const btnPsSetDefault = document.getElementById('btn-ps-set-default');
            if (btnPsSetDefault) {
                btnPsSetDefault.addEventListener('click', () => {
                    const top = (parseFloat(document.getElementById('ps-margin-top')?.value) || 2.54) * 10;
                    const bottom = (parseFloat(document.getElementById('ps-margin-bottom')?.value) || 2.54) * 10;
                    const left = (parseFloat(document.getElementById('ps-margin-left')?.value) || 2.54) * 10;
                    const right = (parseFloat(document.getElementById('ps-margin-right')?.value) || 2.54) * 10;
                    const gutter = (parseFloat(document.getElementById('ps-gutter')?.value) || 0) * 10;
                    const gutterPos = document.getElementById('ps-gutter-pos')?.value || 'left';
                    const orientRadio = pageSetupModal.querySelector('input[name="ps-orient-radio"]:checked');
                    const orientation = orientRadio ? orientRadio.value : 'portrait';
                    const paperSize = document.getElementById('ps-paper-size-select')?.value || 'a4';

                    try {
                        localStorage.setItem('velscribe_default_page_setup', JSON.stringify({
                            margins: { top, bottom, left, right, gutter, gutterPos },
                            orientation,
                            paperSize
                        }));
                        this.showToast('Penyetelan halaman disimpan sebagai standar default dokumen baru.', 'success');
                    } catch (e) {
                        this.showToast('Gagal menyimpan ke penyimpanan lokal.', 'error');
                    }
                });
            }
        }

        // 4. Paragraph Format Modal Logic
        const paraModal = document.getElementById('modal-paragraph-format');
        if (paraModal) {
            // Tab switching
            paraModal.querySelectorAll('[data-para-tab]').forEach(tabBtn => {
                tabBtn.addEventListener('click', () => {
                    const target = tabBtn.getAttribute('data-para-tab');
                    paraModal.querySelectorAll('[data-para-tab]').forEach(b => {
                        b.classList.remove('border-indigo-600', 'text-indigo-600', 'dark:text-indigo-400', 'font-bold');
                        b.classList.add('border-transparent', 'text-gray-500');
                    });
                    tabBtn.classList.add('border-indigo-600', 'text-indigo-600', 'dark:text-indigo-400', 'font-bold');
                    tabBtn.classList.remove('border-transparent', 'text-gray-500');

                    paraModal.querySelectorAll('.para-tab-pane').forEach(pane => pane.classList.add('hidden'));
                    const targetPane = document.getElementById(`tab-para-${target}`);
                    if (targetPane) targetPane.classList.remove('hidden');
                });
            });

            // Live preview updates on input changes
            ['para-align-select', 'para-indent-left', 'para-indent-right', 'para-special-indent', 'para-special-val', 'para-spacing-before', 'para-spacing-after', 'para-line-spacing-type', 'para-line-spacing-at'].forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.addEventListener('input', () => this.updateParagraphPreview());
                    el.addEventListener('change', () => this.updateParagraphPreview());
                }
            });

            const lsTypeSelect = document.getElementById('para-line-spacing-type');
            const lsAtInput = document.getElementById('para-line-spacing-at');
            if (lsTypeSelect && lsAtInput) {
                lsTypeSelect.addEventListener('change', () => {
                    const val = lsTypeSelect.value;
                    if (val === '1.0' || val === '1.15' || val === '1.5' || val === '2.0') {
                        lsAtInput.value = val;
                    }
                    this.updateParagraphPreview();
                });
            }

            // Confirm Paragraph Formatting Button
            const btnConfirmPara = document.getElementById('btn-confirm-paragraph-format');
            if (btnConfirmPara) {
                btnConfirmPara.addEventListener('click', () => {
                    const textAlign = document.getElementById('para-align-select')?.value || 'justify';
                    const indentLeft = parseFloat(document.getElementById('para-indent-left')?.value) || 0;
                    const indentRight = parseFloat(document.getElementById('para-indent-right')?.value) || 0;
                    const specialIndent = document.getElementById('para-special-indent')?.value || 'none';
                    const specialVal = parseFloat(document.getElementById('para-special-val')?.value) || 1.25;
                    const spacingBefore = parseFloat(document.getElementById('para-spacing-before')?.value) || 0;
                    const spacingAfter = parseFloat(document.getElementById('para-spacing-after')?.value) || 6;

                    const lsType = document.getElementById('para-line-spacing-type')?.value || '1.5';
                    const lsAt = document.getElementById('para-line-spacing-at')?.value || '1.5';
                    const lineSpacing = (lsType === '1.0' || lsType === '1.15' || lsType === '1.5' || lsType === '2.0') ? lsType : lsAt;

                    const keepWithNext = Boolean(document.getElementById('para-keep-with-next')?.checked);
                    const keepLinesTogether = Boolean(document.getElementById('para-keep-lines-together')?.checked);
                    const pageBreakBefore = Boolean(document.getElementById('para-page-break-before')?.checked);
                    const widowOrphan = Boolean(document.getElementById('para-widow-orphan')?.checked);
                    const outlineLevel = document.getElementById('para-outline-level')?.value || 'body';
                    const mirrorIndents = Boolean(document.getElementById('para-mirror-indents')?.checked);
                    const dontAddSpaceSame = Boolean(document.getElementById('para-dont-add-space-same')?.checked);
                    const suppressLineNumbers = Boolean(document.getElementById('para-suppress-line-numbers')?.checked);
                    const dontHyphenate = Boolean(document.getElementById('para-dont-hyphenate')?.checked);

                    const scope = document.getElementById('para-apply-scope')?.value || 'selection';

                    this.engine.setParagraphFormatting({
                        textAlign,
                        indentLeft,
                        indentRight,
                        specialIndent,
                        specialVal,
                        spacingBefore,
                        spacingAfter,
                        lineSpacing,
                        keepWithNext,
                        keepLinesTogether,
                        pageBreakBefore,
                        widowOrphan,
                        outlineLevel,
                        mirrorIndents,
                        dontAddSpaceSame,
                        suppressLineNumbers,
                        dontHyphenate
                    }, scope);

                    this.closeModal(paraModal);
                    this.showToast('Format paragraf berhasil diterapkan!', 'success');
                });
            }

            // Set as Default Paragraph Button
            const btnParaSetDefault = document.getElementById('btn-para-set-default');
            if (btnParaSetDefault) {
                btnParaSetDefault.addEventListener('click', () => {
                    const textAlign = document.getElementById('para-align-select')?.value || 'justify';
                    const indentLeft = parseFloat(document.getElementById('para-indent-left')?.value) || 0;
                    const indentRight = parseFloat(document.getElementById('para-indent-right')?.value) || 0;
                    const specialIndent = document.getElementById('para-special-indent')?.value || 'none';
                    const specialVal = parseFloat(document.getElementById('para-special-val')?.value) || 1.25;
                    const spacingBefore = parseFloat(document.getElementById('para-spacing-before')?.value) || 0;
                    const spacingAfter = parseFloat(document.getElementById('para-spacing-after')?.value) || 6;
                    const lineSpacing = document.getElementById('para-line-spacing-type')?.value || '1.5';

                    try {
                        localStorage.setItem('velscribe_default_paragraph_format', JSON.stringify({
                            textAlign, indentLeft, indentRight, specialIndent, specialVal, spacingBefore, spacingAfter, lineSpacing
                        }));
                        this.showToast('Format paragraf disimpan sebagai standar default.', 'success');
                    } catch (e) {
                        this.showToast('Gagal menyimpan ke penyimpanan lokal.', 'error');
                    }
                });
            }
        }

        // 7. Paragraph Shading Dropdown (Microsoft Word Paint Bucket)
        const btnParaShadingDropdown = document.getElementById('btn-paragraph-shading-dropdown');
        const paraShadingMenu = document.getElementById('paragraph-shading-dropdown-menu');
        const customParaShadingInput = document.getElementById('input-custom-para-shading');

        if (btnParaShadingDropdown && paraShadingMenu) {
            btnParaShadingDropdown.addEventListener('click', (e) => {
                e.stopPropagation();
                paraShadingMenu.classList.toggle('hidden');
            });
            document.addEventListener('click', (e) => {
                if (!paraShadingMenu.contains(e.target) && !btnParaShadingDropdown.contains(e.target)) {
                    paraShadingMenu.classList.add('hidden');
                }
            });
            paraShadingMenu.querySelectorAll('[data-para-shading]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const color = btn.getAttribute('data-para-shading');
                    this.engine.setParagraphShading(color);
                    paraShadingMenu.classList.add('hidden');
                    this.showToast(color === 'none' ? 'Menghapus warna latar paragraf' : 'Warna latar paragraf diterapkan');
                });
            });
            if (customParaShadingInput) {
                customParaShadingInput.addEventListener('input', (e) => {
                    this.engine.setParagraphShading(e.target.value);
                });
            }
        }

        // 8. Paragraph Borders Dropdown (Microsoft Word Paragraph Borders)
        const btnParaBordersDropdown = document.getElementById('btn-paragraph-borders-dropdown');
        const paraBordersMenu = document.getElementById('paragraph-borders-dropdown-menu');

        if (btnParaBordersDropdown && paraBordersMenu) {
            btnParaBordersDropdown.addEventListener('click', (e) => {
                e.stopPropagation();
                paraBordersMenu.classList.toggle('hidden');
            });
            document.addEventListener('click', (e) => {
                if (!paraBordersMenu.contains(e.target) && !btnParaBordersDropdown.contains(e.target)) {
                    paraBordersMenu.classList.add('hidden');
                }
            });
            paraBordersMenu.querySelectorAll('[data-para-border]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const borderType = btn.getAttribute('data-para-border');
                    this.engine.setParagraphBorder(borderType);
                    paraBordersMenu.classList.add('hidden');
                    this.showToast(`Batas garis paragraf: ${borderType}`);
                });
            });
        }

        // 9. Drop Cap Dropdown & Modal (Microsoft Word Drop Cap Suite)
        const btnDropCapDropdown = document.getElementById('btn-drop-cap-dropdown');
        const dropCapMenu = document.getElementById('drop-cap-dropdown-menu');
        const modalDropCap = document.getElementById('modal-drop-cap');

        if (btnDropCapDropdown && dropCapMenu) {
            btnDropCapDropdown.addEventListener('click', (e) => {
                e.stopPropagation();
                dropCapMenu.classList.toggle('hidden');
            });
            document.addEventListener('click', (e) => {
                if (!dropCapMenu.contains(e.target) && !btnDropCapDropdown.contains(e.target)) {
                    dropCapMenu.classList.add('hidden');
                }
            });
            dropCapMenu.querySelectorAll('[data-drop-cap-preset]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const preset = btn.getAttribute('data-drop-cap-preset');
                    if (preset === 'none') {
                        this.engine.removeDropCap();
                        this.showToast('Menghapus Drop Cap');
                    } else {
                        this.engine.applyDropCap({ position: preset, lines: 3 });
                        this.showToast(`Drop Cap (${preset === 'dropped' ? 'Turun' : 'Margin'}) diterapkan`);
                    }
                    dropCapMenu.classList.add('hidden');
                });
            });
            const openDropCapBtn = dropCapMenu.querySelector('[data-action="open-drop-cap-modal"]');
            if (openDropCapBtn) {
                openDropCapBtn.addEventListener('click', () => {
                    dropCapMenu.classList.add('hidden');
                    this.openModal(modalDropCap);
                });
            }
        }

        if (modalDropCap) {
            const btnConfirmDropCap = document.getElementById('btn-confirm-drop-cap');
            const btnRemoveDropCap = document.getElementById('btn-remove-drop-cap');

            if (btnConfirmDropCap) {
                btnConfirmDropCap.addEventListener('click', () => {
                    const posRadio = modalDropCap.querySelector('input[name="dc-position-radio"]:checked');
                    const position = posRadio ? posRadio.value : 'dropped';
                    const font = document.getElementById('dc-font-select')?.value || 'inherit';
                    const lines = parseInt(document.getElementById('dc-lines-to-drop')?.value) || 3;
                    const distance = parseFloat(document.getElementById('dc-distance-text')?.value) || 0.2;

                    this.engine.applyDropCap({ position, font, lines, distance });
                    this.closeModal(modalDropCap);
                    this.showToast('Pengaturan Drop Cap berhasil diterapkan');
                });
            }

            if (btnRemoveDropCap) {
                btnRemoveDropCap.addEventListener('click', () => {
                    this.engine.removeDropCap();
                    this.closeModal(modalDropCap);
                    this.showToast('Drop Cap dihapus');
                });
            }
        }

        // 10. Interactive Word Ruler Double-Clicks
        const rulerBar = document.getElementById('ruler-bar');
        const rulerLeftMargin = document.getElementById('ruler-left-margin');
        const rulerRightMargin = document.getElementById('ruler-right-margin');
        const rulerFirstLine = document.getElementById('ruler-marker-first-line');
        const rulerLeftIndent = document.getElementById('ruler-marker-left-indent');
        const rulerRightIndent = document.getElementById('ruler-marker-right-indent');

        if (rulerBar) {
            rulerBar.addEventListener('dblclick', (e) => {
                if (e.target === rulerFirstLine || e.target === rulerLeftIndent || e.target === rulerRightIndent) {
                    this.openParagraphModal('indents-spacing');
                } else {
                    this.openPageSetupModal('margins');
                }
            });
        }

        const verticalRuler = document.getElementById('vertical-ruler');
        if (verticalRuler) {
            verticalRuler.addEventListener('dblclick', () => {
                this.openPageSetupModal('margins');
            });
        }

        // 11. Formatting Marks (Microsoft Word Pilcrow ¶ Mode)
        const btnFormattingMarks = document.getElementById('btn-toggle-formatting-marks');
        if (btnFormattingMarks) {
            btnFormattingMarks.addEventListener('click', () => {
                const active = this.engine.toggleFormattingMarks();
                this.showToast(active ? 'Tanda pemformatan (¶) ditampilkan' : 'Tanda pemformatan disembunyikan');
            });
        }

        // 12. Bi-Directional Text Direction (LTR / RTL)
        const btnLtr = document.getElementById('btn-text-dir-ltr');
        if (btnLtr) {
            btnLtr.addEventListener('click', () => {
                this.engine.setTextDirection('ltr');
                this.showToast('Arah teks paragraf: Kiri ke Kanan (LTR)');
            });
        }

        const btnRtl = document.getElementById('btn-text-dir-rtl');
        if (btnRtl) {
            btnRtl.addEventListener('click', () => {
                this.engine.setTextDirection('rtl');
                this.showToast('Arah teks paragraf: Kanan ke Kiri (RTL)');
            });
        }

        // 13. Sort Paragraphs (Microsoft Word Sort A-Z)
        const btnSortModal = document.getElementById('btn-sort-paragraphs-modal');
        const modalSort = document.getElementById('modal-sort-paragraphs');
        if (btnSortModal && modalSort) {
            btnSortModal.addEventListener('click', () => {
                this.openModal(modalSort);
            });

            const btnConfirmSort = document.getElementById('btn-confirm-sort-paragraphs');
            if (btnConfirmSort) {
                btnConfirmSort.addEventListener('click', () => {
                    const orderRadio = modalSort.querySelector('input[name="sort-order-radio"]:checked');
                    const order = orderRadio ? orderRadio.value : 'asc';
                    const scope = document.getElementById('sort-scope-select')?.value || 'selection';
                    this.engine.sortParagraphs(order, scope);
                    this.closeModal(modalSort);
                    this.showToast(`Paragraf diurutkan secara alfabetis (${order === 'asc' ? 'A - Z' : 'Z - A'})`);
                });
            }
        }
    }

    openLineNumbersModal() {
        const modal = document.getElementById('modal-line-numbers');
        if (!modal) return;
        const currentMode = this.engine.lineNumbers || 'none';
        const radio = modal.querySelector(`input[name="ln-mode-radio"][value="${currentMode}"]`);
        if (radio) radio.checked = true;
        const startInput = document.getElementById('ln-start-at');
        if (startInput) startInput.value = this.engine.lineNumbersOptions?.startAt || 1;
        const countInput = document.getElementById('ln-count-by');
        if (countInput) countInput.value = this.engine.lineNumbersOptions?.countBy || 1;
        this.openModal(modal);
    }

    openWatermarkModal() {
        const modal = document.getElementById('modal-watermark');
        if (!modal) return;
        const currentWm = this.engine.watermark || { text: 'DRAF', font: "'Times New Roman', serif", size: 72, color: '#9ca3af', opacity: 0.25, layout: 'diagonal' };
        const textIn = document.getElementById('wm-text-input');
        if (textIn) textIn.value = currentWm.text || 'DRAF';
        const fontSel = document.getElementById('wm-font-select');
        if (fontSel && currentWm.font) fontSel.value = currentWm.font;
        const sizeSel = document.getElementById('wm-size-select');
        if (sizeSel && currentWm.size) sizeSel.value = String(currentWm.size);
        const colorIn = document.getElementById('wm-color-input');
        if (colorIn && currentWm.color) colorIn.value = currentWm.color;
        const semiCheck = document.getElementById('wm-semitransparent');
        if (semiCheck) semiCheck.checked = currentWm.opacity <= 0.4;
        const layout = currentWm.layout || 'diagonal';
        const layRadio = modal.querySelector(`input[name="wm-layout-radio"][value="${layout}"]`);
        if (layRadio) layRadio.checked = true;
        this.openModal(modal);
    }

    openPageBordersModal() {
        const modal = document.getElementById('modal-page-borders');
        if (!modal) return;
        const currentPb = this.engine.pageBorder || { setting: 'box', style: 'solid', width: '1.5pt', color: '#000000', insetMm: 15, applyTo: 'document' };
        const settingRadio = modal.querySelector(`input[name="pb-setting-radio"][value="${currentPb.setting || 'box'}"]`);
        if (settingRadio) settingRadio.checked = true;
        const styleSel = document.getElementById('pb-style-select');
        if (styleSel && currentPb.style) styleSel.value = currentPb.style;
        const widthSel = document.getElementById('pb-width-select');
        if (widthSel && currentPb.width) widthSel.value = currentPb.width;
        const colorIn = document.getElementById('pb-color-input');
        if (colorIn && currentPb.color) colorIn.value = currentPb.color;
        const insetIn = document.getElementById('pb-margin-inset');
        if (insetIn && currentPb.insetMm) insetIn.value = currentPb.insetMm;
        const applySel = document.getElementById('pb-apply-to');
        if (applySel && currentPb.applyTo) applySel.value = currentPb.applyTo;
        this.openModal(modal);
    }

    openTabsModal() {
        const modal = document.getElementById('modal-tabs-format');
        if (!modal) return;

        const defaultStop = this.engine.defaultTabStop || 1.27;
        const defaultInput = document.getElementById('tab-default-stop');
        if (defaultInput) defaultInput.value = defaultStop;

        const tabStopsList = document.getElementById('tab-stops-list');
        if (tabStopsList) {
            tabStopsList.innerHTML = '';
            const stops = Array.isArray(this.engine.tabStops) && this.engine.tabStops.length > 0
                ? this.engine.tabStops
                : [{ pos: 1.25, align: 'left', leader: 'none' }];

            stops.forEach(st => {
                const opt = document.createElement('option');
                const pos = typeof st === 'object' ? parseFloat(st.pos) : parseFloat(st);
                const align = typeof st === 'object' ? (st.align || 'left') : 'left';
                const leader = typeof st === 'object' ? (st.leader || 'none') : 'none';
                opt.value = pos.toFixed(2);
                opt.setAttribute('data-align', align);
                opt.setAttribute('data-leader', leader);
                const alignLabel = align === 'center' ? 'Tengah' : align === 'right' ? 'Kanan' : 'Kiri';
                opt.textContent = `${pos.toFixed(2)} cm (${alignLabel})`;
                tabStopsList.appendChild(opt);
            });

            if (tabStopsList.options.length > 0) {
                tabStopsList.options[0].selected = true;
                const posIn = document.getElementById('tab-stop-pos');
                if (posIn) posIn.value = tabStopsList.options[0].value;
            }
        }

        this.openModal(modal);
    }

    openPageSetupModal(initialTab = 'margins') {
        const modal = document.getElementById('modal-page-setup');
        if (!modal) return;

        // Switch to target tab
        const tabBtn = modal.querySelector(`[data-page-setup-tab="${initialTab}"]`);
        if (tabBtn) tabBtn.click();

        // Populate current margins
        const margins = this.engine.currentMargins || { top: 40, bottom: 30, left: 40, right: 30, gutter: 0, gutterPos: 'left' };
        const topIn = document.getElementById('ps-margin-top');
        const btmIn = document.getElementById('ps-margin-bottom');
        const lftIn = document.getElementById('ps-margin-left');
        const rgtIn = document.getElementById('ps-margin-right');
        const gutIn = document.getElementById('ps-gutter');
        const gutPos = document.getElementById('ps-gutter-pos');

        if (topIn) topIn.value = (margins.top / 10).toFixed(1);
        if (btmIn) btmIn.value = (margins.bottom / 10).toFixed(1);
        if (lftIn) lftIn.value = (margins.left / 10).toFixed(1);
        if (rgtIn) rgtIn.value = (margins.right / 10).toFixed(1);
        if (gutIn) gutIn.value = ((margins.gutter || 0) / 10).toFixed(1);
        if (gutPos) gutPos.value = margins.gutterPos || 'left';

        // Highlight preset button if matching
        if (this.engine.currentMarginPreset) {
            const presetBtn = modal.querySelector(`[data-ps-preset="${this.engine.currentMarginPreset}"]`);
            if (presetBtn) {
                modal.querySelectorAll('[data-ps-preset]').forEach(b => {
                    b.classList.remove('border-indigo-300', 'dark:border-indigo-800', 'bg-indigo-50', 'dark:bg-indigo-950/60', 'text-indigo-700', 'dark:text-indigo-300', 'font-semibold');
                    b.classList.add('border-gray-200', 'dark:border-gray-700');
                });
                presetBtn.classList.add('border-indigo-300', 'dark:border-indigo-800', 'bg-indigo-50', 'dark:bg-indigo-950/60', 'text-indigo-700', 'dark:text-indigo-300', 'font-semibold');
            }
        }

        // Orientation
        const orient = this.engine.currentOrientation || 'portrait';
        const card = modal.querySelector(`.ps-orient-card[data-orient="${orient}"]`);
        if (card) card.click();

        // Paper size
        const sizeSelect = document.getElementById('ps-paper-size-select');
        if (sizeSelect) {
            sizeSelect.value = this.engine.currentPaperSize || 'a4';
        }
        const currentSize = this.engine.currentPaperSize || 'a4';
        const defaultDim = PAPER_DIMENSIONS[currentSize] || { width: 210, height: 297 };
        const customW = document.getElementById('ps-custom-width');
        const customH = document.getElementById('ps-custom-height');
        if (customW) customW.value = ((this.engine.customWidthMm || defaultDim.width) / 10).toFixed(1);
        if (customH) customH.value = ((this.engine.customHeightMm || defaultDim.height) / 10).toFixed(1);

        // Multiple Pages, Section & Layout tab settings
        const multPages = document.getElementById('ps-multiple-pages');
        if (multPages && this.engine.multiplePagesMode) multPages.value = this.engine.multiplePagesMode;

        const secStart = document.getElementById('ps-section-start');
        if (secStart && this.engine.sectionStart) secStart.value = this.engine.sectionStart;

        const diffOdd = document.getElementById('ps-diff-odd-even');
        if (diffOdd) diffOdd.checked = Boolean(this.engine.differentOddEven);

        const diffFirst = document.getElementById('ps-diff-first-page');
        if (diffFirst) diffFirst.checked = Boolean(this.engine.differentFirstPage);

        const edgeH = document.getElementById('ps-edge-header');
        if (edgeH && this.engine.edgeHeader !== undefined) edgeH.value = (this.engine.edgeHeader / 10).toFixed(1);

        const edgeF = document.getElementById('ps-edge-footer');
        if (edgeF && this.engine.edgeFooter !== undefined) edgeF.value = (this.engine.edgeFooter / 10).toFixed(1);

        const vAlign = document.getElementById('ps-vert-align');
        if (vAlign && this.engine.verticalAlign) vAlign.value = this.engine.verticalAlign;

        this.updatePageSetupPreview();
        this.openModal(modal);
    }

    updatePageSetupPreview() {
        const modal = document.getElementById('modal-page-setup');
        if (!modal) return;

        const orientRadio = modal.querySelector('input[name="ps-orient-radio"]:checked');
        const orient = orientRadio ? orientRadio.value : 'portrait';

        const sizeSelect = document.getElementById('ps-paper-size-select');
        const paperSize = sizeSelect ? sizeSelect.value : 'a4';

        let widthMm = 210;
        let heightMm = 297;
        if (paperSize === 'custom') {
            widthMm = (parseFloat(document.getElementById('ps-custom-width')?.value) || 21) * 10;
            heightMm = (parseFloat(document.getElementById('ps-custom-height')?.value) || 29.7) * 10;
        } else if (PAPER_DIMENSIONS[paperSize]) {
            widthMm = PAPER_DIMENSIONS[paperSize].width;
            heightMm = PAPER_DIMENSIONS[paperSize].height;
        }

        if (orient === 'landscape') {
            const tmp = widthMm;
            widthMm = heightMm;
            heightMm = tmp;
        }

        const topMm = (parseFloat(document.getElementById('ps-margin-top')?.value) || 2.54) * 10;
        const bottomMm = (parseFloat(document.getElementById('ps-margin-bottom')?.value) || 2.54) * 10;
        const leftMm = (parseFloat(document.getElementById('ps-margin-left')?.value) || 2.54) * 10;
        const rightMm = (parseFloat(document.getElementById('ps-margin-right')?.value) || 2.54) * 10;

        const previewCanvas = document.getElementById('ps-preview-canvas');
        const contentBox = document.getElementById('ps-preview-content-box');
        const caption = document.getElementById('ps-preview-caption');
        const ratioInfo = document.getElementById('ps-paper-ratio-info');
        const printAreaInfo = document.getElementById('ps-printable-area-info');

        if (previewCanvas && contentBox) {
            const maxW = 140;
            const maxH = 150;
            let canvasW = maxW;
            let canvasH = maxH;
            const aspect = widthMm / heightMm;

            if (aspect <= 1) {
                // portrait
                canvasH = maxH;
                canvasW = Math.round(maxH * aspect);
            } else {
                // landscape
                canvasW = maxW;
                canvasH = Math.round(maxW / aspect);
            }

            previewCanvas.style.width = `${canvasW}px`;
            previewCanvas.style.height = `${canvasH}px`;

            const topPct = Math.min(40, Math.max(5, (topMm / heightMm) * 100));
            const bottomPct = Math.min(40, Math.max(5, (bottomMm / heightMm) * 100));
            const leftPct = Math.min(40, Math.max(5, (leftMm / widthMm) * 100));
            const rightPct = Math.min(40, Math.max(5, (rightMm / widthMm) * 100));

            contentBox.style.top = `${topPct}%`;
            contentBox.style.bottom = `${bottomPct}%`;
            contentBox.style.left = `${leftPct}%`;
            contentBox.style.right = `${rightPct}%`;
        }

        if (caption) {
            caption.textContent = `${paperSize.toUpperCase()} ${orient === 'portrait' ? 'Potret' : 'Lanskap'} (${Math.round(widthMm)} × ${Math.round(heightMm)} mm)`;
        }
        if (ratioInfo) {
            ratioInfo.textContent = `1 : ${(Math.max(widthMm, heightMm) / Math.min(widthMm, heightMm)).toFixed(3)}`;
        }
        if (printAreaInfo) {
            const printW = Math.max(0, Math.round(widthMm - leftMm - rightMm));
            const printH = Math.max(0, Math.round(heightMm - topMm - bottomMm));
            printAreaInfo.textContent = `${printW} × ${printH} mm`;
        }
    }

    openParagraphModal(initialTab = 'indents-spacing') {
        const modal = document.getElementById('modal-paragraph-format');
        if (!modal) return;

        const tabBtn = modal.querySelector(`[data-para-tab="${initialTab}"]`);
        if (tabBtn) tabBtn.click();

        const format = this.engine.getActiveParagraphFormat();

        const alignSelect = document.getElementById('para-align-select');
        if (alignSelect) alignSelect.value = format.textAlign || 'justify';

        const indentLeft = document.getElementById('para-indent-left');
        if (indentLeft) indentLeft.value = (format.indentLeft || 0).toFixed(2).replace(/\.?0+$/, '');

        const indentRight = document.getElementById('para-indent-right');
        if (indentRight) indentRight.value = (format.indentRight || 0).toFixed(2).replace(/\.?0+$/, '');

        const specialIndent = document.getElementById('para-special-indent');
        if (specialIndent) specialIndent.value = format.specialIndent || 'none';

        const specialVal = document.getElementById('para-special-val');
        if (specialVal) specialVal.value = (format.specialVal || 1.25).toFixed(2).replace(/\.?0+$/, '');

        const spacingBefore = document.getElementById('para-spacing-before');
        if (spacingBefore) spacingBefore.value = Math.round(format.spacingBefore || 0);

        const spacingAfter = document.getElementById('para-spacing-after');
        if (spacingAfter) spacingAfter.value = Math.round(format.spacingAfter || 6);

        const lsType = document.getElementById('para-line-spacing-type');
        const lsAt = document.getElementById('para-line-spacing-at');
        const rawLs = String(format.lineSpacing || '1.5');
        if (lsType && lsAt) {
            if (['1.0', '1.15', '1.5', '2.0'].includes(rawLs)) {
                lsType.value = rawLs;
                lsAt.value = rawLs;
            } else {
                lsType.value = 'multiple';
                lsAt.value = rawLs;
            }
        }

        const keepNext = document.getElementById('para-keep-with-next');
        if (keepNext) keepNext.checked = Boolean(format.keepWithNext);

        const pageBreak = document.getElementById('para-page-break-before');
        if (pageBreak) pageBreak.checked = Boolean(format.pageBreakBefore);

        const widowOrphan = document.getElementById('para-widow-orphan');
        if (widowOrphan) widowOrphan.checked = format.widowOrphan !== false;

        const outlineSelect = document.getElementById('para-outline-level');
        if (outlineSelect) outlineSelect.value = format.outlineLevel || 'body';

        const mirrorCheck = document.getElementById('para-mirror-indents');
        if (mirrorCheck) mirrorCheck.checked = Boolean(format.mirrorIndents);

        const sameSpaceCheck = document.getElementById('para-dont-add-space-same');
        if (sameSpaceCheck) sameSpaceCheck.checked = Boolean(format.dontAddSpaceSame);

        const suppressLn = document.getElementById('para-suppress-line-numbers');
        if (suppressLn) suppressLn.checked = Boolean(format.suppressLineNumbers);

        const dontHyphen = document.getElementById('para-dont-hyphenate');
        if (dontHyphen) dontHyphen.checked = Boolean(format.dontHyphenate);

        this.updateParagraphPreview();
        this.openModal(modal);
    }

    updateParagraphPreview() {
        const preview = document.getElementById('para-preview-sample');
        if (!preview) return;

        const align = document.getElementById('para-align-select')?.value || 'justify';
        const indentLeft = parseFloat(document.getElementById('para-indent-left')?.value) || 0;
        const indentRight = parseFloat(document.getElementById('para-indent-right')?.value) || 0;
        const special = document.getElementById('para-special-indent')?.value || 'none';
        const specialVal = parseFloat(document.getElementById('para-special-val')?.value) || 1.25;
        const spacingBefore = parseFloat(document.getElementById('para-spacing-before')?.value) || 0;
        const spacingAfter = parseFloat(document.getElementById('para-spacing-after')?.value) || 6;

        const lsType = document.getElementById('para-line-spacing-type')?.value || '1.5';
        const lsAt = document.getElementById('para-line-spacing-at')?.value || '1.5';
        const lineSpacing = (lsType === '1.0' || lsType === '1.15' || lsType === '1.5' || lsType === '2.0') ? lsType : (lsAt || '1.5');

        preview.style.textAlign = align;
        preview.style.marginLeft = `${indentLeft}cm`;
        preview.style.marginRight = `${indentRight}cm`;
        preview.style.marginTop = `${spacingBefore}pt`;
        preview.style.marginBottom = `${spacingAfter}pt`;
        preview.style.lineHeight = lineSpacing;

        if (special === 'first-line') {
            preview.style.textIndent = `${specialVal}cm`;
            preview.style.paddingLeft = '0px';
        } else if (special === 'hanging') {
            preview.style.textIndent = `-${specialVal}cm`;
            preview.style.paddingLeft = `${specialVal}cm`;
        } else {
            preview.style.textIndent = '0px';
            preview.style.paddingLeft = '0px';
        }
    }

    /* -------------------------------------------------------------
     * Rulers & Measurements
     * ------------------------------------------------------------- */

    renderRuler(widthCm = null) {
        const ruler = document.getElementById('ruler-ticks');
        const rulerContainer = document.getElementById('ruler-container');
        if (!ruler) return;

        if (!widthCm && this.engine && typeof this.engine.getDocumentWidthMm === 'function') {
            widthCm = this.engine.getDocumentWidthMm() / 10;
        }
        if (!widthCm) widthCm = 21;

        if (rulerContainer) {
            rulerContainer.style.width = `${widthCm * 10}mm`;
        }
        ruler.style.width = '100%';
        ruler.innerHTML = '';
        const totalCm = Math.max(1, Math.round(widthCm));
        for (let cm = 0; cm <= totalCm; cm++) {
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
