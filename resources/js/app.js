import mammoth from 'mammoth';
import { marked } from 'marked';
import { DocumentEngine } from './editor/document-engine.js';
import { WordcraftAi } from './editor/wordcraft-ai.js';
import { UiController } from './editor/ui-controller.js';

window.mammoth = mammoth;
window.marked = marked;

document.addEventListener('DOMContentLoaded', () => {
    const editorEl = document.getElementById('document-editor');
    if (!editorEl) return;

    const titleInput = document.getElementById('document-title-input');
    const formatBadge = document.getElementById('document-format-badge');
    const saveStatusEl = document.getElementById('document-save-status');

    // Bottom status bar elements
    const statWords = document.getElementById('stat-words');
    const statChars = document.getElementById('stat-chars');
    const statReadingTime = document.getElementById('stat-reading-time');
    const statPages = document.getElementById('stat-pages');

    // Outline container
    const outlineList = document.getElementById('outline-headings-list');

    // Initialize Document Engine
    const engine = new DocumentEngine({
        pagesWrapper: document.getElementById('document-pages-wrapper'),
        editorEl,
        canvasEl: document.getElementById('document-page-canvas'),
        titleInput,
        formatBadge,
        onStatsChange: (stats) => {
            if (statWords) statWords.textContent = `${stats.wordCount} kata`;
            if (statChars) statChars.textContent = `${stats.charCount} karakter`;
            if (statReadingTime) statReadingTime.textContent = stats.readingTime;
            if (statPages) statPages.textContent = `Halaman ${stats.activePage || 1} dari ${stats.pageCount}`;
        },
        onOutlineChange: (headings) => {
            if (!outlineList) return;
            outlineList.innerHTML = '';

            if (headings.length === 0) {
                outlineList.innerHTML = '<li class="text-xs text-gray-400 italic py-2">Belum ada judul/heading. Gunakan H1, H2, atau H3.</li>';
                return;
            }

            headings.forEach(h => {
                const li = document.createElement('li');
                const indentClass = h.level === 1 ? 'font-bold text-gray-900 dark:text-gray-100 text-xs mt-1.5' :
                                    h.level === 2 ? 'pl-3 font-medium text-gray-700 dark:text-gray-300 text-[11px]' :
                                    'pl-6 text-gray-500 dark:text-gray-400 text-[11px]';

                li.className = `py-1 cursor-pointer hover:text-indigo-600 dark:hover:text-indigo-400 flex items-center justify-between gap-1 transition ${indentClass}`;
                li.innerHTML = `
                    <span class="truncate" title="${engine.escapeHtml(h.text || 'Judul Kosong')}">${engine.escapeHtml(h.text || 'Judul Kosong')}</span>
                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 shrink-0 font-mono font-normal">Hal ${h.pageNumber || 1}</span>
                `;

                li.addEventListener('click', () => {
                    const targetEl = document.getElementById(h.id);
                    if (targetEl) {
                        targetEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        targetEl.classList.add('bg-indigo-100', 'dark:bg-indigo-950/60');
                        setTimeout(() => targetEl.classList.remove('bg-indigo-100', 'dark:bg-indigo-950/60'), 1800);
                    }
                });

                outlineList.appendChild(li);
            });
        },
        onSaveStateChange: (msg) => {
            if (saveStatusEl) {
                saveStatusEl.textContent = msg;
            }
        },
        onAdaptationNotice: (notice) => {
            if (window.uiController) {
                window.uiController.showToast(notice.message, notice.type === 'academic' ? 'success' : 'info');
            }
        },
    });

    // Initialize Wordcraft AI Studio
    const wordcraftAi = new WordcraftAi(engine, {
        containerEl: document.getElementById('wordcraft-sidebar'),
        resultsEl: document.getElementById('wordcraft-results-container'),
        promptInput: document.getElementById('wordcraft-custom-prompt'),
        styleSelect: document.getElementById('wordcraft-style-select'),
        statusEl: document.getElementById('wordcraft-loading-spinner'),
        onNotify: (msg) => {
            if (window.uiController) {
                window.uiController.showToast(msg, 'success');
            }
        },
    });

    // Initialize UI Controller
    const uiController = new UiController(engine, wordcraftAi);
    window.uiController = uiController;
    window.documentEngine = engine;
    window.wordcraftAi = wordcraftAi;

    // Optional: Check if local storage draft exists
    try {
        const raw = localStorage.getItem('velscribe_document_state');
        if (raw) {
            const data = JSON.parse(raw);
            if (data && data.content && data.content.length > 50) {
                const restoreBtn = document.getElementById('btn-restore-draft');
                if (restoreBtn) {
                    restoreBtn.classList.remove('hidden');
                    restoreBtn.addEventListener('click', () => {
                        engine.loadFromLocalStorage();
                        restoreBtn.classList.add('hidden');
                        uiController.showToast('Draf terakhir dari penyimpanan lokal dipulihkan!');
                    });
                }
            }
        }
    } catch (e) {
        // silent
    }

    console.log('VelScribe Wordcraft Studio core initialized.');
});
