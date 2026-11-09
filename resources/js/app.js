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
        editorEl,
        titleInput,
        formatBadge,
        onStatsChange: (stats) => {
            if (statWords) statWords.textContent = `${stats.wordCount} kata`;
            if (statChars) statChars.textContent = `${stats.charCount} karakter`;
            if (statReadingTime) statReadingTime.textContent = stats.readingTime;
            if (statPages) statPages.textContent = `Halaman 1 dari ${stats.pageCount}`;
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
                const indentClass = h.level === 1 ? 'font-semibold text-gray-800 dark:text-gray-200' :
                                    h.level === 2 ? 'pl-3 text-gray-600 dark:text-gray-400 text-xs' :
                                    'pl-6 text-gray-500 dark:text-gray-500 text-xs';

                li.className = `py-1 cursor-pointer hover:text-indigo-600 dark:hover:text-indigo-400 truncate transition ${indentClass}`;
                li.textContent = h.text || 'Judul Kosong';
                li.title = h.text;

                li.addEventListener('click', () => {
                    const targetEl = document.getElementById(h.id);
                    if (targetEl) {
                        targetEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        targetEl.classList.add('bg-indigo-50', 'dark:bg-indigo-950/40');
                        setTimeout(() => targetEl.classList.remove('bg-indigo-50', 'dark:bg-indigo-950/40'), 1500);
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

    // Optional: Check if local storage draft is newer
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

    console.log('VelScribe Wordcraft Studio successfully initialized.');
});
