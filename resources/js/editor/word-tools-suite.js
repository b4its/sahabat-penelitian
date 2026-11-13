/**
 * Sahabat Penelitian Word Tools Suite
 * Integrates Microsoft Word parity features into the web workspace:
 * 1. Track Changes & Revision Management (Accept / Reject All / Indicator)
 * 2. Document Comparison (Legal Blackline Semantic Diff)
 * 3. Accessibility Checker (Heading Hierarchy, Image Alt Text, Table Headers)
 * 4. Mail Merge Studio (CSV Mapping, Presets, Preview & Batch Merge)
 * 5. Server Version Snapshots (Immutable snapshots & Non-destructive Restore)
 * 6. Document Protection (Password hashing & Restrict Editing)
 */

export class WordToolsSuite {
    constructor(engine, uiController) {
        this.engine = engine;
        this.uiController = uiController;
        this.isTrackChangesActive = false;
        this.serverDocUuid = (typeof localStorage !== 'undefined')
            ? (localStorage.getItem('velscribe_server_doc_uuid') || null)
            : null;
        this.isLocked = false;
    }

    bind() {
        this.bindTrackChanges();
        this.bindCompareModal();
        this.bindAccessibilityModal();
        this.bindMailMergeModal();
        this.bindVersionHistoryModal();
        this.bindProtectionModal();
    }

    getCsrfToken() {
        if (typeof document === 'undefined') return '';
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    async apiPost(url, data) {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': this.getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(data),
        });
        return res.json();
    }

    async apiGet(url) {
        const res = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        return res.json();
    }

    /* -------------------------------------------------------------
     * 1. TRACK CHANGES & REVISIONS (ACCEPT / REJECT)
     * ------------------------------------------------------------- */
    bindTrackChanges() {
        const toggleBtn = document.getElementById('btn-toggle-track-changes');
        const indicator = document.getElementById('track-changes-indicator');
        const acceptAllBtn = document.getElementById('btn-review-accept-all');
        const rejectAllBtn = document.getElementById('btn-review-reject-all');

        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                this.isTrackChangesActive = !this.isTrackChangesActive;
                if (indicator) {
                    indicator.classList.toggle('bg-emerald-500', this.isTrackChangesActive);
                    indicator.classList.toggle('bg-gray-400', !this.isTrackChangesActive);
                }
                toggleBtn.classList.toggle('border-emerald-500', this.isTrackChangesActive);
                toggleBtn.classList.toggle('text-emerald-700', this.isTrackChangesActive);

                const statusText = this.isTrackChangesActive ? 'Aktif' : 'Nonaktif';
                this.uiController?.showToast?.(`Lacak Perubahan (Track Changes) ${statusText}`);
            });
        }

        if (acceptAllBtn) {
            acceptAllBtn.addEventListener('click', async () => {
                try {
                    const content = this.engine.getContent();
                    const data = await this.apiPost('/api/documents/review/accept-all', { html: content });
                    if ((data.success || data.status === 'success') && data.html !== undefined) {
                        this.engine.setContent(data.html);
                        this.engine.handleInput();
                        this.uiController?.showToast?.('Semua revisi lacak perubahan berhasil diterima.');
                    }
                } catch (e) {
                    console.error('Error accepting all revisions:', e);
                    this.uiController?.showToast?.('Gagal menerima revisi.');
                }
            });
        }

        if (rejectAllBtn) {
            rejectAllBtn.addEventListener('click', async () => {
                try {
                    const content = this.engine.getContent();
                    const data = await this.apiPost('/api/documents/review/reject-all', { html: content });
                    if ((data.success || data.status === 'success') && data.html !== undefined) {
                        this.engine.setContent(data.html);
                        this.engine.handleInput();
                        this.uiController?.showToast?.('Semua revisi lacak perubahan berhasil ditolak.');
                    }
                } catch (e) {
                    console.error('Error rejecting all revisions:', e);
                    this.uiController?.showToast?.('Gagal menolak revisi.');
                }
            });
        }
    }

    /* -------------------------------------------------------------
     * 2. DOCUMENT COMPARISON (LEGAL BLACKLINE DIFF)
     * ------------------------------------------------------------- */
    bindCompareModal() {
        const modal = document.getElementById('modal-compare-documents');
        if (!modal) return;

        const openBtns = document.querySelectorAll('[data-action="open-compare-modal"]');
        openBtns.forEach(b => b.addEventListener('click', () => {
            this.uiController?.openModal?.(modal);
        }));

        const runBtn = document.getElementById('btn-run-compare');
        const applyBtn = document.getElementById('btn-apply-comparison');
        const baseInput = document.getElementById('compare-base-content');
        const diffBox = document.getElementById('compare-diff-result');

        if (runBtn) {
            runBtn.addEventListener('click', async () => {
                const originalHtml = baseInput ? baseInput.value.trim() : '';
                if (!originalHtml) {
                    this.uiController?.showToast?.('Masukkan naskah versi asli pembanding.');
                    return;
                }
                runBtn.disabled = true;
                runBtn.textContent = 'Membandingkan...';
                try {
                    const modifiedHtml = this.engine.getContent();
                    const data = await this.apiPost('/api/documents/review/compare', {
                        original_html: originalHtml,
                        modified_html: modifiedHtml,
                    });
                    if ((data.success || data.status === 'success') && diffBox) {
                        diffBox.innerHTML = data.diff_html || '<p class="text-gray-400">Tidak ada perbedaan naskah.</p>';
                        diffBox.dataset.diffHtml = data.diff_html;
                        if (applyBtn) applyBtn.classList.remove('hidden');
                    }
                } catch (e) {
                    console.error('Error comparing documents:', e);
                    this.uiController?.showToast?.('Gagal membandingkan dokumen.');
                } finally {
                    runBtn.disabled = false;
                    runBtn.textContent = 'Bandingkan Naskah';
                }
            });
        }

        if (applyBtn) {
            applyBtn.addEventListener('click', () => {
                const diffHtml = diffBox?.dataset.diffHtml;
                if (diffHtml) {
                    this.engine.setContent(diffHtml);
                    this.engine.handleInput();
                    this.uiController?.closeModal?.(modal);
                    this.uiController?.showToast?.('Hasil perbandingan (blackline diff) berhasil diterapkan ke dokumen!');
                }
            });
        }
    }

    /* -------------------------------------------------------------
     * 3. ACCESSIBILITY AUDIT (WCAG 2.2 AA & WORD CHECKER)
     * ------------------------------------------------------------- */
    bindAccessibilityModal() {
        const modal = document.getElementById('modal-accessibility-audit');
        if (!modal) return;

        const openBtns = document.querySelectorAll('[data-action="open-accessibility-modal"]');
        openBtns.forEach(b => b.addEventListener('click', () => {
            this.uiController?.openModal?.(modal);
            this.runAccessibilityAudit();
        }));

        const refreshBtn = document.getElementById('btn-refresh-accessibility');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => this.runAccessibilityAudit());
        }
    }

    async runAccessibilityAudit() {
        const badge = document.getElementById('a11y-status-badge');
        const list = document.getElementById('a11y-issues-list');
        const countSpan = document.getElementById('a11y-issue-count');

        if (badge) badge.textContent = 'Memeriksa...';
        try {
            const content = this.engine.getContent();
            const data = await this.apiPost('/api/documents/accessibility-audit', { html: content, content });
            if ((data.success || data.status === 'success') && (data.audit || data.passed !== undefined)) {
                const audit = data.audit || data;
                const issues = audit.issues || [];

                if (countSpan) countSpan.textContent = `${issues.length} Isu Terdeteksi`;

                if (badge) {
                    if (audit.passed) {
                        badge.className = 'px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300';
                        badge.innerHTML = '<i class="fa-solid fa-circle-check mr-1 text-emerald-500"></i> Lolos Penuh (Aksesibel)';
                    } else {
                        badge.className = 'px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300';
                        badge.innerHTML = '<i class="fa-solid fa-triangle-exclamation mr-1 text-amber-500"></i> Perlu Perbaikan';
                    }
                }

                if (list) {
                    list.innerHTML = '';
                    if (issues.length === 0) {
                        list.innerHTML = `
                            <div class="p-4 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs text-emerald-800 dark:text-emerald-200">
                                <strong>Dokumen Memenuhi Standar Aksesibilitas!</strong><br>
                                Tidak ditemukan lompatan judul heading, gambar tanpa deskripsi teks alternatif, maupun tabel tanpa sel tajuk.
                            </div>
                        `;
                    } else {
                        issues.forEach(issue => {
                            const item = document.createElement('div');
                            item.className = 'p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60 text-xs space-y-1';
                            const title = document.createElement('div');
                            title.className = 'font-bold text-gray-800 dark:text-gray-200 flex items-center gap-1.5';
                            title.innerHTML = `<span class="text-amber-500"><i class="fa-solid fa-triangle-exclamation"></i></span> <span>${this.escapeHtml(issue.type)}</span>`;
                            const desc = document.createElement('p');
                            desc.className = 'text-gray-600 dark:text-gray-400';
                            desc.textContent = issue.message;
                            item.appendChild(title);
                            item.appendChild(desc);
                            list.appendChild(item);
                        });
                    }
                }
            }
        } catch (e) {
            console.error('Error running accessibility audit:', e);
            if (badge) badge.textContent = 'Galat Pemeriksaan';
        }
    }

    /* -------------------------------------------------------------
     * 4. MAIL MERGE STUDIO (CSV MAPPING & PREVIEW)
     * ------------------------------------------------------------- */
    bindMailMergeModal() {
        const modal = document.getElementById('modal-mail-merge');
        if (!modal) return;

        const openBtns = document.querySelectorAll('[data-action="open-mail-merge-modal"]');
        openBtns.forEach(b => b.addEventListener('click', () => {
            this.uiController?.openModal?.(modal);
            this.loadMailMergePresets();
        }));

        const sampleCsvBtn = document.getElementById('btn-mail-merge-sample-csv');
        const csvInput = document.getElementById('mail-merge-csv-input');
        if (sampleCsvBtn && csvInput) {
            sampleCsvBtn.addEventListener('click', () => {
                csvInput.value = `nama,institusi,kota,posisi\nDr. Hendra Wijaya,Universitas Indonesia,Jakarta,Ketua Tim Peneliti\nProf. Ratna Sari,Institut Teknologi Bandung,Bandung,Reviewer Utama\nIr. Budi Santoso,Badan Riset Nasional,Surabaya,Anggota Peneliti`;
            });
        }

        const previewBtn = document.getElementById('btn-mail-merge-preview');
        const previewBox = document.getElementById('mail-merge-preview-box');
        if (previewBtn) {
            previewBtn.addEventListener('click', async () => {
                const csv = csvInput ? csvInput.value.trim() : '';
                if (!csv) {
                    this.uiController?.showToast?.('Masukkan data penerima dalam format CSV.');
                    return;
                }
                previewBtn.disabled = true;
                previewBtn.textContent = 'Menyiapkan Preview...';
                try {
                    const templateHtml = this.engine.getContent();
                    const data = await this.apiPost('/api/documents/mail-merge/preview', {
                        template_html: templateHtml,
                        csv,
                        record_index: 0,
                    });
                    if ((data.success || data.status === 'success') && previewBox) {
                        previewBox.innerHTML = data.merged_html || '';
                        this.uiController?.showToast?.(`Preview untuk penerima #${data.record_index + 1} (${data.total_records} data terdeteksi).`);
                    }
                } catch (e) {
                    console.error('Error previewing mail merge:', e);
                    this.uiController?.showToast?.('Gagal memproses pratinjau surat masal.');
                } finally {
                    previewBtn.disabled = false;
                    previewBtn.textContent = 'Pratinjau Penerima #1';
                }
            });
        }

        const batchBtn = document.getElementById('btn-mail-merge-batch');
        if (batchBtn) {
            batchBtn.addEventListener('click', async () => {
                const csv = csvInput ? csvInput.value.trim() : '';
                if (!csv) {
                    this.uiController?.showToast?.('Masukkan data penerima CSV.');
                    return;
                }
                batchBtn.disabled = true;
                batchBtn.textContent = 'Menggabungkan...';
                try {
                    const templateHtml = this.engine.getContent();
                    const data = await this.apiPost('/api/documents/mail-merge/batch', {
                        template_html: templateHtml,
                        csv,
                    });
                    if ((data.success || data.status === 'success') && data.documents) {
                        const count = data.documents.length;
                        const mergedCombined = data.documents.join('\n<div class="page-break my-8 border-b-2 border-dashed border-gray-400 text-center py-2 select-none" data-page-break="true"><span class="bg-gray-100 px-3 py-1 rounded-full text-xs text-gray-500 font-mono">Pemisah Surat Masal</span></div>\n');
                        this.engine.setContent(mergedCombined);
                        this.engine.handleInput();
                        this.uiController?.closeModal?.(modal);
                        this.uiController?.showToast?.(`Sukses! ${count} dokumen surat masal berhasil dibuat.`);
                    }
                } catch (e) {
                    console.error('Error batch mail merge:', e);
                    this.uiController?.showToast?.('Gagal menggabungkan surat masal.');
                } finally {
                    batchBtn.disabled = false;
                    batchBtn.textContent = 'Gabungkan ke Naskah Aktif';
                }
            });
        }
    }

    async loadMailMergePresets() {
        const select = document.getElementById('mail-merge-preset-select');
        if (!select || select.dataset.loaded === 'true') return;

        try {
            const data = await this.apiGet('/api/documents/mail-merge/presets');
            if ((data.success || data.status === 'success') && data.presets) {
                select.innerHTML = '';
                Object.entries(data.presets).forEach(([key, preset]) => {
                    const opt = document.createElement('option');
                    opt.value = key;
                    opt.textContent = `${preset.name} (${preset.width_mm} x ${preset.height_mm} mm)`;
                    select.appendChild(opt);
                });
                select.dataset.loaded = 'true';
            }
        } catch (e) {
            console.error('Error loading mail merge presets:', e);
        }
    }

    /* -------------------------------------------------------------
     * 5. SERVER VERSION HISTORY & SNAPSHOT RESTORE
     * ------------------------------------------------------------- */
    bindVersionHistoryModal() {
        const modal = document.getElementById('modal-version-history');
        if (!modal) return;

        const openBtns = document.querySelectorAll('[data-action="open-version-history-modal"]');
        openBtns.forEach(b => b.addEventListener('click', async () => {
            this.uiController?.openModal?.(modal);
            await this.ensureServerDocument();
            this.loadVersionList();
        }));

        const createSnapshotBtn = document.getElementById('btn-create-version-snapshot');
        const noteInput = document.getElementById('version-snapshot-note');
        if (createSnapshotBtn) {
            createSnapshotBtn.addEventListener('click', async () => {
                await this.ensureServerDocument();
                if (!this.serverDocUuid) return;

                const note = noteInput ? noteInput.value.trim() : '';
                createSnapshotBtn.disabled = true;
                createSnapshotBtn.textContent = 'Menyimpan...';
                try {
                    const content = this.engine.getContent();
                    const data = await this.apiPost(`/api/documents/server/${this.serverDocUuid}/versions`, {
                        content,
                        change_summary: note || 'Snapshot berkala pengguna',
                        user_name: 'Penulis Naskah',
                    });
                    if (data.success || data.status === 'success') {
                        if (noteInput) noteInput.value = '';
                        this.uiController?.showToast?.(`Snapshot Versi ${data.version.version_number} berhasil disimpan.`);
                        this.loadVersionList();
                    }
                } catch (e) {
                    console.error('Error creating snapshot:', e);
                    this.uiController?.showToast?.('Gagal menyimpan snapshot versi.');
                } finally {
                    createSnapshotBtn.disabled = false;
                    createSnapshotBtn.textContent = 'Simpan Snapshot Versi Baru';
                }
            });
        }
    }

    async ensureServerDocument() {
        if (this.serverDocUuid) return;

        try {
            const title = this.engine.getTitle() || 'Dokumen VelScribe.docx';
            const content = this.engine.getContent();
            const data = await this.apiPost('/api/documents/server/create', {
                title,
                content,
                owner_email: 'penulis@velscribe.local',
            });
            if ((data.success || data.status === 'success') && data.document) {
                this.serverDocUuid = data.document.uuid;
                if (typeof localStorage !== 'undefined') {
                    localStorage.setItem('velscribe_server_doc_uuid', this.serverDocUuid);
                }
            }
        } catch (e) {
            console.error('Error creating server document:', e);
        }
    }

    async loadVersionList() {
        const list = document.getElementById('version-history-list');
        if (!list || !this.serverDocUuid) return;

        list.innerHTML = '<div class="text-xs text-gray-500 py-2">Memuat riwayat versi...</div>';
        try {
            const data = await this.apiGet(`/api/documents/server/${this.serverDocUuid}/versions`);
            if ((data.success || data.status === 'success') && Array.isArray(data.versions)) {
                list.innerHTML = '';
                if (data.versions.length === 0) {
                    list.innerHTML = '<div class="text-xs text-gray-400">Belum ada snapshot versi tersimpan.</div>';
                    return;
                }
                data.versions.forEach(v => {
                    const item = document.createElement('div');
                    item.className = 'p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex items-center justify-between text-xs';
                    const info = document.createElement('div');
                    info.innerHTML = `
                        <div class="font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                            <span>Versi #${v.version_number}</span>
                            <span class="text-[10px] text-gray-400 font-normal">${new Date(v.created_at).toLocaleString('id-ID')}</span>
                        </div>
                        <div class="text-gray-500 dark:text-gray-400 text-[11px] mt-0.5">${this.escapeHtml(v.change_summary || 'Tidak ada catatan.')}</div>
                    `;
                    const restoreBtn = document.createElement('button');
                    restoreBtn.type = 'button';
                    restoreBtn.className = 'px-3 py-1 rounded bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 font-semibold hover:bg-indigo-100 transition';
                    restoreBtn.textContent = 'Pulihkan Versi Ini';
                    restoreBtn.addEventListener('click', () => this.restoreVersion(v.version_number));

                    item.appendChild(info);
                    item.appendChild(restoreBtn);
                    list.appendChild(item);
                });
            }
        } catch (e) {
            console.error('Error loading versions:', e);
            list.innerHTML = '<div class="text-xs text-rose-500">Gagal memuat riwayat versi.</div>';
        }
    }

    async restoreVersion(versionNum) {
        if (!this.serverDocUuid) return;
        try {
            const data = await this.apiPost(`/api/documents/server/${this.serverDocUuid}/versions/${versionNum}/restore`, {
                user_email: 'penulis@velscribe.local',
            });
            if ((data.success || data.status === 'success') && data.version) {
                this.engine.setContent(data.version.content);
                this.engine.handleInput();
                this.uiController?.closeModal?.(document.getElementById('modal-version-history'));
                this.uiController?.showToast?.(`Versi #${versionNum} dipulihkan (dibuat sebagai Versi #${data.version.version_number}).`);
            }
        } catch (e) {
            console.error('Error restoring version:', e);
            this.uiController?.showToast?.('Gagal memulihkan versi.');
        }
    }

    /* -------------------------------------------------------------
     * 6. DOCUMENT PROTECTION & RESTRICTED EDITING
     * ------------------------------------------------------------- */
    bindProtectionModal() {
        const modal = document.getElementById('modal-document-protection');
        if (!modal) return;

        const openBtns = document.querySelectorAll('[data-action="open-protection-modal"]');
        openBtns.forEach(b => b.addEventListener('click', async () => {
            this.uiController?.openModal?.(modal);
            await this.ensureServerDocument();
        }));

        const applyBtn = document.getElementById('btn-apply-protection');
        const passInput = document.getElementById('protection-password-input');
        const modeSelect = document.getElementById('protection-mode-select');

        if (applyBtn) {
            applyBtn.addEventListener('click', async () => {
                await this.ensureServerDocument();
                if (!this.serverDocUuid) return;

                const password = passInput ? passInput.value : '';
                const mode = modeSelect ? modeSelect.value : 'read_only';

                if (!password) {
                    this.uiController?.showToast?.('Masukkan kata sandi perlindungan dokumen.');
                    return;
                }

                applyBtn.disabled = true;
                applyBtn.textContent = 'Menerapkan...';
                try {
                    const data = await this.apiPost(`/api/documents/server/${this.serverDocUuid}/protection`, {
                        password,
                        mode,
                    });
                    if (data.success || data.status === 'success') {
                        this.setLockState(mode !== 'none', mode);
                        this.uiController?.closeModal?.(modal);
                        this.uiController?.showToast?.(`Dokumen dilindungi dengan sandi (Mode: ${mode}).`);
                    }
                } catch (e) {
                    console.error('Error setting protection:', e);
                    this.uiController?.showToast?.('Gagal menerapkan proteksi dokumen.');
                } finally {
                    applyBtn.disabled = false;
                    applyBtn.textContent = 'Terapkan Proteksi Sandi';
                }
            });
        }

        const unlockBtn = document.getElementById('btn-unlock-protection');
        const unlockInput = document.getElementById('protection-unlock-input');
        if (unlockBtn) {
            unlockBtn.addEventListener('click', async () => {
                if (!this.serverDocUuid) return;
                const password = unlockInput ? unlockInput.value : '';
                if (!password) {
                    this.uiController?.showToast?.('Masukkan kata sandi untuk membuka kunci.');
                    return;
                }
                unlockBtn.disabled = true;
                unlockBtn.textContent = 'Memverifikasi...';
                try {
                    const data = await this.apiPost(`/api/documents/server/${this.serverDocUuid}/protection/verify`, {
                        password,
                    });
                    if ((data.success || data.status === 'success') && data.verified) {
                        this.setLockState(false, 'none');
                        this.uiController?.closeModal?.(modal);
                        this.uiController?.showToast?.('Proteksi berhasil dibuka! Mode penyuntingan dipulihkan.');
                    } else {
                        this.uiController?.showToast?.('Kata sandi salah. Akses ditolak.');
                    }
                } catch (e) {
                    console.error('Error verifying protection:', e);
                    this.uiController?.showToast?.('Kata sandi salah atau galat verifikasi.');
                } finally {
                    unlockBtn.disabled = false;
                    unlockBtn.textContent = 'Buka Kunci Dokumen';
                }
            });
        }
    }

    setLockState(locked, mode) {
        this.isLocked = locked;
        const bodies = this.engine.getAllEditorBodies?.() || [];
        bodies.forEach(body => {
            if (locked && mode === 'read_only') {
                body.setAttribute('contenteditable', 'false');
            } else {
                body.setAttribute('contenteditable', 'true');
            }
        });

        const badge = document.getElementById('document-protection-badge');
        if (badge) {
            badge.classList.toggle('hidden', !locked);
            badge.innerHTML = locked ? `<i class="fa-solid fa-lock mr-1"></i> Terproteksi (${mode})` : '';
        }
    }

    escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
}
