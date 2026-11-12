import test from 'node:test';
import assert from 'node:assert/strict';
import { JSDOM } from 'jsdom';
import { WordToolsSuite } from '../../resources/js/editor/word-tools-suite.js';

function setupEnvironment() {
    const dom = new JSDOM(`
        <!DOCTYPE html>
        <html>
        <head>
            <meta name="csrf-token" content="test-csrf-token">
        </head>
        <body>
            <span id="document-format-badge">DOCX</span>
            <span id="document-protection-badge" class="hidden">🔒 Terproteksi</span>

            <!-- Track changes -->
            <button id="btn-toggle-track-changes"><span id="track-changes-indicator" class="bg-gray-400"></span></button>
            <button id="btn-review-accept-all">Terima</button>
            <button id="btn-review-reject-all">Tolak</button>

            <!-- Compare modal -->
            <div id="modal-compare-documents" class="editor-modal hidden">
                <textarea id="compare-base-content"></textarea>
                <button id="btn-run-compare">Bandingkan</button>
                <div id="compare-diff-result"></div>
                <button id="btn-apply-comparison" class="hidden">Terapkan</button>
            </div>

            <!-- Accessibility modal -->
            <div id="modal-accessibility-audit" class="editor-modal hidden">
                <span id="a11y-status-badge"></span>
                <span id="a11y-issue-count"></span>
                <div id="a11y-issues-list"></div>
                <button id="btn-refresh-accessibility">Refresh</button>
            </div>

            <!-- Mail Merge modal -->
            <div id="modal-mail-merge" class="editor-modal hidden">
                <select id="mail-merge-preset-select"></select>
                <button id="btn-mail-merge-sample-csv">Contoh CSV</button>
                <textarea id="mail-merge-csv-input"></textarea>
                <button id="btn-mail-merge-preview">Preview</button>
                <div id="mail-merge-preview-box"></div>
                <button id="btn-mail-merge-batch">Batch</button>
            </div>

            <!-- Version history modal -->
            <div id="modal-version-history" class="editor-modal hidden">
                <input id="version-snapshot-note" />
                <button id="btn-create-version-snapshot">Simpan</button>
                <div id="version-history-list"></div>
            </div>

            <!-- Protection modal -->
            <div id="modal-document-protection" class="editor-modal hidden">
                <select id="protection-mode-select">
                    <option value="read_only">read_only</option>
                </select>
                <input type="password" id="protection-password-input" />
                <button id="btn-apply-protection">Terapkan</button>
                <input type="password" id="protection-unlock-input" />
                <button id="btn-unlock-protection">Buka</button>
            </div>

            <div class="page-sheet-body" contenteditable="true">
                <p>Naskah Pengujian</p>
            </div>
        </body>
        </html>
    `);

    globalThis.window = dom.window;
    globalThis.document = dom.window.document;
    globalThis.fetch = dom.window.fetch;

    let content = '<p>Naskah Pengujian</p>';
    const editorBody = document.querySelector('.page-sheet-body');
    const toasts = [];

    const mockEngine = {
        getContent: () => content,
        setContent: (html) => { content = html; editorBody.innerHTML = html; },
        getTitle: () => 'Naskah Uji.docx',
        handleInput: () => {},
        getAllEditorBodies: () => [editorBody],
    };

    const mockUi = {
        showToast: (msg) => toasts.push(msg),
        openModal: (m) => m.classList.remove('hidden'),
        closeModal: (m) => m.classList.add('hidden'),
    };

    const suite = new WordToolsSuite(mockEngine, mockUi);
    suite.bind();

    return { suite, mockEngine, mockUi, editorBody, toasts, dom };
}

test('WordToolsSuite toggles track changes state and updates indicator', () => {
    const { suite, toasts } = setupEnvironment();
    const btn = document.getElementById('btn-toggle-track-changes');
    const indicator = document.getElementById('track-changes-indicator');

    assert.equal(suite.isTrackChangesActive, false);
    btn.click();
    assert.equal(suite.isTrackChangesActive, true);
    assert.equal(indicator.classList.contains('bg-emerald-500'), true);
    assert.match(toasts[toasts.length - 1], /Track Changes.*Aktif/);

    btn.click();
    assert.equal(suite.isTrackChangesActive, false);
    assert.equal(indicator.classList.contains('bg-emerald-500'), false);
    assert.match(toasts[toasts.length - 1], /Track Changes.*Nonaktif/);
});

test('WordToolsSuite mail merge sample button fills CSV input', () => {
    setupEnvironment();
    const btn = document.getElementById('btn-mail-merge-sample-csv');
    const input = document.getElementById('mail-merge-csv-input');

    assert.equal(input.value, '');
    btn.click();
    assert.match(input.value, /Dr\. Hendra Wijaya/);
    assert.match(input.value, /nama,institusi,kota/);
});

test('WordToolsSuite sets lock state correctly on document protection', () => {
    const { suite, editorBody } = setupEnvironment();
    const badge = document.getElementById('document-protection-badge');

    assert.equal(editorBody.getAttribute('contenteditable'), 'true');
    assert.equal(badge.classList.contains('hidden'), true);

    suite.setLockState(true, 'read_only');
    assert.equal(suite.isLocked, true);
    assert.equal(editorBody.getAttribute('contenteditable'), 'false');
    assert.equal(badge.classList.contains('hidden'), false);
    assert.match(badge.textContent, /read_only/);

    suite.setLockState(false, 'none');
    assert.equal(suite.isLocked, false);
    assert.equal(editorBody.getAttribute('contenteditable'), 'true');
    assert.equal(badge.classList.contains('hidden'), true);
});

test('WordToolsSuite runs accessibility audit and updates DOM status badge', async () => {
    const { suite } = setupEnvironment();

    // Mock apiPost
    suite.apiPost = async (url) => {
        if (url === '/api/documents/accessibility-audit') {
            return {
                status: 'success',
                audit: {
                    passed: false,
                    issues: [
                        { type: 'heading_skip', message: 'Heading level jumped from H1 to H3' },
                        { type: 'missing_alt', message: 'Image missing ALT text' },
                    ],
                },
            };
        }
        return { status: 'error' };
    };

    await suite.runAccessibilityAudit();

    const badge = document.getElementById('a11y-status-badge');
    const count = document.getElementById('a11y-issue-count');
    const list = document.getElementById('a11y-issues-list');

    assert.match(badge.textContent, /Perlu Perbaikan/);
    assert.match(count.textContent, /2 Isu Terdeteksi/);
    assert.equal(list.children.length, 2);
    assert.match(list.children[0].textContent, /heading_skip/);
});

test('WordToolsSuite comparison applies diff to document engine', () => {
    const { mockEngine, mockUi } = setupEnvironment();
    const applyBtn = document.getElementById('btn-apply-comparison');
    const diffBox = document.getElementById('compare-diff-result');
    const modal = document.getElementById('modal-compare-documents');

    diffBox.dataset.diffHtml = '<p>Teks <ins class="text-emerald-600 underline">baru</ins></p>';
    applyBtn.click();

    assert.equal(mockEngine.getContent(), '<p>Teks <ins class="text-emerald-600 underline">baru</ins></p>');
    assert.equal(modal.classList.contains('hidden'), true);
});
