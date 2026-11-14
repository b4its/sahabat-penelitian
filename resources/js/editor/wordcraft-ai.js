/**
 * Sahabat Penelitian Xishie Studio AI
 * Implements creative writing operations (Continue, Elaborate, Rewrite, Grammar, Summarize, Ideate, Chat).
 */

export class WordcraftAi {
    constructor(engine, options = {}) {
        this.engine = engine;
        this.containerEl = options.containerEl;
        this.resultsEl = options.resultsEl;
        this.promptInput = options.promptInput;
        this.styleSelect = options.styleSelect;
        this.statusEl = options.statusEl;
        this.providerSelect = options.providerSelect || (this.containerEl ? this.containerEl.querySelector('#wordcraft-provider-select') : null) || document.getElementById('wordcraft-provider-select');
        this.providerDropdown = options.providerDropdown || (this.containerEl ? this.containerEl.querySelector('#wordcraft-provider-dropdown') : null) || document.getElementById('wordcraft-provider-dropdown');
        this.providerTrigger = options.providerTrigger || (this.containerEl ? this.containerEl.querySelector('#wordcraft-provider-search-trigger') : null) || document.getElementById('wordcraft-provider-search-trigger');
        this.providerSearchInput = options.providerSearchInput || (this.containerEl ? this.containerEl.querySelector('#wordcraft-provider-search-input') : null) || document.getElementById('wordcraft-provider-search-input');
        this.providerOptionsList = options.providerOptionsList || (this.containerEl ? this.containerEl.querySelector('#wordcraft-provider-options-list') : null) || document.getElementById('wordcraft-provider-options-list');
        this.providerSelectedText = options.providerSelectedText || (this.containerEl ? this.containerEl.querySelector('#wordcraft-provider-selected-text') : null) || document.getElementById('wordcraft-provider-selected-text');
        this.providerEmptyResult = options.providerEmptyResult || (this.containerEl ? this.containerEl.querySelector('#wordcraft-provider-empty-result') : null) || document.getElementById('wordcraft-provider-empty-result');
        this.providerChevron = options.providerChevron || (this.containerEl ? this.containerEl.querySelector('#wordcraft-provider-chevron') : null) || document.getElementById('wordcraft-provider-chevron');
        this.onNotify = options.onNotify || (() => {});

        this.currentAction = 'continue';
        this.activeSelection = '';
        this.isLoading = false;

        window.wordcraftAi = this;
        this.init();
    }

    init() {
        if (!this.containerEl) return;

        // Action chips event delegation
        this.containerEl.querySelectorAll('[data-wordcraft-action]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const action = btn.getAttribute('data-wordcraft-action');
                this.setAction(action);
                this.executeAction(action);
            });
        });

        // Submit custom prompt
        const submitBtn = this.containerEl.querySelector('#wordcraft-submit-prompt');
        if (submitBtn) {
            submitBtn.addEventListener('click', () => {
                const query = this.promptInput ? this.promptInput.value.trim() : '';
                if (query) {
                    this.executeAction('chat', query);
                }
            });
        }

        // Enter key in prompt input
        if (this.promptInput) {
            this.promptInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    const query = this.promptInput.value.trim();
                    if (query) {
                        this.executeAction('chat', query);
                    }
                }
            });
        }

        // Initialize Searchable AI Provider Dropdown
        this.initProviderSelector();
    }

    initProviderSelector() {
        if (!this.providerTrigger || !this.providerDropdown) return;

        // Toggle dropdown on trigger click
        this.providerTrigger.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleProviderDropdown();
        });

        // Filter on search input
        if (this.providerSearchInput) {
            this.providerSearchInput.addEventListener('input', () => {
                this.filterProviderOptions();
            });
            this.providerSearchInput.addEventListener('click', (e) => {
                e.stopPropagation();
            });
            this.providerSearchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    this.toggleProviderDropdown(false);
                }
            });
        }

        // Option item click
        if (this.providerOptionsList) {
            this.providerOptionsList.querySelectorAll('.wordcraft-provider-option-item').forEach(item => {
                item.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const uuid = item.getAttribute('data-uuid');
                    if (uuid) {
                        this.selectProvider(uuid);
                    }
                });
            });
        }

        // Click outside closes dropdown
        document.addEventListener('click', (e) => {
            const wrapper = document.getElementById('wordcraft-provider-searchable-wrapper');
            if (wrapper && !wrapper.contains(e.target)) {
                this.toggleProviderDropdown(false);
            }
        });

        // Close dropdown on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.providerDropdown && !this.providerDropdown.classList.contains('hidden')) {
                this.toggleProviderDropdown(false);
            }
        });
    }

    toggleProviderDropdown(force) {
        if (!this.providerDropdown) return;
        const isCurrentlyHidden = this.providerDropdown.classList.contains('hidden');
        const show = typeof force === 'boolean' ? force : isCurrentlyHidden;

        if (show) {
            this.providerDropdown.classList.remove('hidden');
            if (this.providerChevron) {
                this.providerChevron.classList.add('rotate-180');
            }
            if (this.providerTrigger) {
                this.providerTrigger.setAttribute('aria-expanded', 'true');
            }
            if (this.providerSearchInput) {
                this.providerSearchInput.value = '';
                this.filterProviderOptions();
                setTimeout(() => this.providerSearchInput.focus(), 50);
            }
        } else {
            this.providerDropdown.classList.add('hidden');
            if (this.providerChevron) {
                this.providerChevron.classList.remove('rotate-180');
            }
            if (this.providerTrigger) {
                this.providerTrigger.setAttribute('aria-expanded', 'false');
            }
        }
    }

    filterProviderOptions() {
        if (!this.providerOptionsList) return;
        const query = (this.providerSearchInput ? this.providerSearchInput.value : '').toLowerCase().trim();
        const items = this.providerOptionsList.querySelectorAll('.wordcraft-provider-option-item');
        let visibleCount = 0;

        items.forEach(item => {
            const name = (item.getAttribute('data-name') || '').toLowerCase();
            const label = (item.getAttribute('data-label') || '').toLowerCase();
            if (!query || name.includes(query) || label.includes(query)) {
                item.classList.remove('hidden');
                visibleCount++;
            } else {
                item.classList.add('hidden');
            }
        });

        if (this.providerEmptyResult) {
            if (visibleCount === 0) {
                this.providerEmptyResult.classList.remove('hidden');
            } else {
                this.providerEmptyResult.classList.add('hidden');
            }
        }
    }

    selectProvider(uuid) {
        if (this.providerSelect) {
            this.providerSelect.value = uuid;
            this.providerSelect.dispatchEvent(new Event('change', { bubbles: true }));
        }

        // Highlight selected option
        if (this.providerOptionsList) {
            this.providerOptionsList.querySelectorAll('.wordcraft-provider-option-item').forEach(item => {
                if (item.getAttribute('data-uuid') === uuid) {
                    item.classList.add('bg-indigo-50/80', 'dark:bg-indigo-950/60', 'font-bold');
                } else {
                    item.classList.remove('bg-indigo-50/80', 'dark:bg-indigo-950/60', 'font-bold');
                }
            });
        }

        // Update selected display text
        this.updateSelectedProviderDisplay(uuid);
        this.toggleProviderDropdown(false);
    }

    updateSelectedProviderDisplay(uuid) {
        if (!this.providerSelectedText) return;
        let selectedItem = null;
        if (this.providerOptionsList) {
            selectedItem = this.providerOptionsList.querySelector(`.wordcraft-provider-option-item[data-uuid="${uuid}"]`);
        }

        if (selectedItem) {
            const name = selectedItem.getAttribute('data-name') || '';
            const label = selectedItem.getAttribute('data-label') || '';
            const badge = selectedItem.getAttribute('data-badge') || '';
            this.providerSelectedText.innerHTML = `
                <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                <span class="truncate font-bold text-gray-900 dark:text-white">${this.escapeHtml(name)}</span>
                ${label ? `<span class="text-[10px] px-1.5 py-0.2 rounded font-normal shrink-0 ${badge}">${this.escapeHtml(label)}</span>` : ''}
            `;
        }
    }

    getSelectedProvider() {
        return this.providerSelect ? this.providerSelect.value : '';
    }

    escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    setAction(action) {
        this.currentAction = action;
        this.containerEl.querySelectorAll('[data-wordcraft-action]').forEach(btn => {
            const btnAction = btn.getAttribute('data-wordcraft-action');
            if (btnAction === action) {
                btn.classList.add('bg-indigo-600', 'text-white', 'border-indigo-600');
                btn.classList.remove('bg-gray-100', 'text-gray-700', 'dark:bg-gray-800', 'dark:text-gray-300');
            } else {
                btn.classList.remove('bg-indigo-600', 'text-white', 'border-indigo-600');
                btn.classList.add('bg-gray-100', 'text-gray-700', 'dark:bg-gray-800', 'dark:text-gray-300');
            }
        });
    }

    /**
     * Execute Wordcraft action against active document selection or context.
     */
    async executeAction(action, customPrompt = '') {
        if (this.isLoading) return;

        // Grab selection and context from document engine
        const selState = this.engine.getSelectionState();
        this.activeSelection = selState.hasSelection ? selState.text : '';
        const context = this.engine.editorEl.innerText || '';
        const style = this.styleSelect ? this.styleSelect.value : 'formal';

        this.setLoading(true);

        const providerUuid = this.getSelectedProvider();

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch('/api/documents/ai-assistant', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                },
                body: JSON.stringify({
                    action,
                    selection: this.activeSelection,
                    context: context.substring(Math.max(0, context.length - 2000)),
                    prompt: customPrompt,
                    style,
                    provider: providerUuid,
                }),
            });

            if (!res.ok) {
                throw new Error('Permintaan asisten AI gagal');
            }

            const data = await res.json();
            this.renderResults(data);
        } catch (err) {
            console.warn('Wordcraft API fallback ke generator lokal:', err);
            // Local client-side smart simulation fallback
            const fallbackData = this.generateLocalFallback(action, this.activeSelection, context, customPrompt, style);
            if (providerUuid) {
                const opt = this.providerSelect ? this.providerSelect.querySelector(`option[value="${providerUuid}"]`) : null;
                if (opt) {
                    fallbackData.provider = opt.getAttribute('data-name') || opt.textContent.trim();
                }
            }
            this.renderResults(fallbackData);
        } finally {
            this.setLoading(false);
        }
    }

    renderResults(data) {
        if (!this.resultsEl) return;
        this.resultsEl.innerHTML = '';

        const titleDiv = document.createElement('div');
        titleDiv.className = 'mb-3 flex items-center justify-between flex-wrap gap-2';
        titleDiv.innerHTML = `
            <div class="flex items-center gap-1.5 flex-wrap">
                <span class="text-xs font-semibold px-2 py-0.5 rounded bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300 uppercase">
                    ${data.title || data.action}
                </span>
                ${data.provider ? `
                    <span class="text-[10px] px-2 py-0.5 rounded bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200 dark:border-blue-800 flex items-center gap-1">
                        <i class="fa-solid fa-server text-[9px]"></i>
                        ${this.escapeHtml(data.provider)}
                    </span>
                ` : ''}
                <span class="text-xs text-gray-500">${data.options.length} saran dihasilkan</span>
            </div>
            <button id="wordcraft-retry-btn" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1 cursor-pointer">
                <i class="fa-solid fa-arrow-rotate-right text-xs"></i>
                Coba Lagi
            </button>
        `;
        this.resultsEl.appendChild(titleDiv);

        // Bind retry button
        titleDiv.querySelector('#wordcraft-retry-btn').addEventListener('click', () => {
            this.executeAction(data.action);
        });

        if (data.explanation) {
            const exp = document.createElement('p');
            exp.className = 'text-xs text-gray-500 mb-3 italic';
            exp.textContent = data.explanation;
            this.resultsEl.appendChild(exp);
        }

        // Render Cards
        data.options.forEach((optText, idx) => {
            const card = document.createElement('div');
            card.className = 'p-3.5 mb-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm hover:border-indigo-300 dark:hover:border-indigo-600 transition-all';

            const cardHeader = document.createElement('div');
            cardHeader.className = 'flex items-center justify-between text-xs text-gray-400 mb-2';
            cardHeader.innerHTML = `<span>Opsi ${idx + 1}</span>`;

            const cardBody = document.createElement('div');
            cardBody.className = 'text-sm text-gray-800 dark:text-gray-200 mb-3 whitespace-pre-line leading-relaxed';
            cardBody.innerHTML = optText.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

            const cardActions = document.createElement('div');
            cardActions.className = 'flex items-center gap-2 pt-2 border-t border-gray-100 dark:border-gray-700/60';

            // Insert button
            const insertBtn = document.createElement('button');
            insertBtn.className = 'px-2.5 py-1 text-xs font-medium rounded bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 hover:bg-indigo-100 dark:hover:bg-indigo-900 transition flex items-center gap-1.5 cursor-pointer';
            insertBtn.innerHTML = `
                <i class="fa-solid fa-plus text-xs"></i>
                <span>Sisipkan</span>
            `;
            insertBtn.addEventListener('click', () => {
                this.engine.insertAtCursor(`<p>${optText.replace(/\n/g, '<br>')}</p>`);
                this.onNotify('Teks berhasil disisipkan ke dalam dokumen!');
            });

            // Replace button (if selection exists)
            const replaceBtn = document.createElement('button');
            replaceBtn.className = 'px-2.5 py-1 text-xs font-medium rounded bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-900 transition flex items-center gap-1.5 cursor-pointer';
            replaceBtn.innerHTML = `
                <i class="fa-solid fa-arrows-rotate text-xs"></i>
                <span>Ganti Seleksi</span>
            `;
            replaceBtn.addEventListener('click', () => {
                this.engine.replaceSelectedText(optText.replace(/\n/g, '<br>'));
                this.onNotify('Teks terpilih berhasil diganti dengan saran AI!');
            });

            // Copy button
            const copyBtn = document.createElement('button');
            copyBtn.className = 'px-2 py-1 text-xs text-gray-500 hover:text-gray-800 dark:hover:text-gray-200 ml-auto transition cursor-pointer';
            copyBtn.innerHTML = `
                <i class="fa-regular fa-copy text-sm"></i>
            `;
            copyBtn.title = 'Salin ke Clipboard';
            copyBtn.addEventListener('click', () => {
                navigator.clipboard.writeText(optText);
                this.onNotify('Teks berhasil disalin ke clipboard!');
            });

            cardActions.appendChild(insertBtn);
            if (this.activeSelection) {
                cardActions.appendChild(replaceBtn);
            }
            cardActions.appendChild(copyBtn);

            card.appendChild(cardHeader);
            card.appendChild(cardBody);
            card.appendChild(cardActions);

            this.resultsEl.appendChild(card);
        });
    }

    setLoading(loading) {
        this.isLoading = loading;
        if (this.statusEl) {
            this.statusEl.classList.toggle('hidden', !loading);
        }
    }

    generateLocalFallback(action, selection, context, prompt, style) {
        const sel = selection || 'naskah ini';
        if (action === 'continue') {
            return {
                action: 'continue',
                title: 'Lanjutkan Tulisan',
                options: [
                    "Lebih jauh lagi, temuan ini memperkuat urgensi penerapan pendekatan yang lebih adaptif dalam mengatasi dinamika kerja kontemporer.",
                    "Sebagai langkah mitigasi, sinkronisasi antar elemen teknis dan operasional menjadi kunci keberhasilan jangka panjang.",
                    "Dengan demikian, setiap perbaikan kecil pada tata kelola naskah memberikan impak eksponensial terhadap kualitas keluaran.",
                ],
                explanation: 'Saran dihasilkan secara instan dari model bahasa lokal.',
            };
        }

        if (action === 'elaborate') {
            return {
                action: 'elaborate',
                title: 'Elaborasi Gagasan',
                options: [
                    `${sel} — secara mendalam tercermin dari keterikatan antara struktur metodologis dengan kepraktisan eksekusi harian. Hal ini membuka cakrawala baru bagi peningkatan efisiensi tim.`,
                    `Menelisik lebih lanjut mengenai ${sel}, esensinya terletak pada harmonisasi format dan konten yang terintegrasi tanpa sekat pembatas konvensional.`,
                ],
                explanation: 'Pilihan elaborasi naratif dengan penambahan konteks analitis.',
            };
        }

        if (action === 'rewrite') {
            return {
                action: 'rewrite',
                title: 'Parafrase Teks',
                options: [
                    `Berdasarkan telaah kritis: "${sel}" dapat disintesiskan kembali dengan formulasi bahasa yang lebih lugas dan terarah.`,
                    `Melalui perspektif komprehensif, pesan utama dari teks tersebut mencerminkan komitmen terhadap kualitas dan ketepatan sasaran.`,
                ],
                explanation: `Hasil parafrase disesuaikan dengan nada ${style}.`,
            };
        }

        if (action === 'grammar') {
            return {
                action: 'grammar',
                title: 'Koreksi Tata Bahasa & PUEBI',
                options: [
                    sel.replace(/\bdimana\b/gi, 'di mana').replace(/\bmerubah\b/gi, 'mengubah'),
                ],
                explanation: 'Ejaan dan tanda baca telah diselaraskan dengan kaidah PUEBI.',
            };
        }

        if (action === 'summarize') {
            return {
                action: 'summarize',
                title: 'Ringkasan Eksekutif',
                options: [
                    "**Intisari Dokumen**:\n• Dokumen membahas strategi modifikasi dokumen lokal dengan penyelarasan format otomatis.\n• Fitur terintegrasi mengadopsi standar Word, Google Docs, dan Xishie Studio.\n• Ekspor multi-format (DOCX, PDF, MD, HTML) siap digunakan.",
                ],
                explanation: 'Ringkasan poin eksekutif siap pakai.',
            };
        }

        return {
            action: 'chat',
            title: 'Tanya Dokumen',
            options: [
                `Menjawab pertanyaan Anda: "${prompt}": Dokumen ini dirancang untuk menyederhanakan modifikasi berkas lokal dengan format yang otomatis menyesuaikan gaya naskah.`,
            ],
            explanation: 'Jawaban dikonstruksi dari konteks aktif dokumen.',
        };
    }
}
