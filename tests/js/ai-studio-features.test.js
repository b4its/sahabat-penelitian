import test from 'node:test';
import assert from 'node:assert/strict';
import { JSDOM } from 'jsdom';
import { marked } from 'marked';

test('formatMarkdown converts markdown headings, bold, italic, code blocks, lists, and quotes', () => {
    const rawMarkdown = `### Metodologi Penelitian
Berikut tahapan riset:
- **Analisis Kuantitatif**: Menggunakan regresi linier.
- *Observasi*: Melakukan triangulasi data.

> "Data empiris adalah pondasi teori."

Gunakan perintah \`python analyze.py\` untuk eksekusi.
\`\`\`bash
npm run build
\`\`\``;

    // Test with marked
    const htmlWithMarked = marked.parse(rawMarkdown);
    assert.match(htmlWithMarked, /<h3[^>]*>Metodologi Penelitian<\/h3>/i);
    assert.match(htmlWithMarked, /<strong>Analisis Kuantitatif<\/strong>/i);
    assert.match(htmlWithMarked, /<em>Observasi<\/em>/i);
    assert.match(htmlWithMarked, /<blockquote/i);
    assert.match(htmlWithMarked, /<code>python analyze\.py<\/code>/i);
    assert.match(htmlWithMarked, /<pre><code/i);
});

test('searchable provider dropdown filters options based on query', () => {
    const dom = new JSDOM(`
        <div id="chat-provider-searchable-wrapper">
            <input type="text" id="chat-provider-search-input" value="gemini" />
            <ul id="chat-provider-options-list">
                <li class="provider-option-item" data-name="OpenAI GPT-4o Studio" data-label="OpenAI">OpenAI</li>
                <li class="provider-option-item" data-name="Google Gemini 2.0 Flash" data-label="Google Gemini">Gemini</li>
                <li class="provider-option-item" data-name="Anthropic Claude 3.5 Sonnet" data-label="Anthropic">Claude</li>
            </ul>
            <div id="chat-provider-empty-result" class="hidden">Provider tidak ditemukan</div>
        </div>
    `);

    const { document } = dom.window;
    const query = (document.getElementById('chat-provider-search-input')?.value || '').toLowerCase().trim();
    const items = document.querySelectorAll('#chat-provider-options-list .provider-option-item');
    let visibleCount = 0;

    items.forEach(item => {
        const name = (item.getAttribute('data-name') || '').toLowerCase();
        const label = (item.getAttribute('data-label') || '').toLowerCase();
        if (name.includes(query) || label.includes(query)) {
            item.classList.remove('hidden');
            visibleCount++;
        } else {
            item.classList.add('hidden');
        }
    });

    assert.equal(visibleCount, 1);
    assert.equal(items[0].classList.contains('hidden'), true);
    assert.equal(items[1].classList.contains('hidden'), false);
    assert.equal(items[2].classList.contains('hidden'), true);
});

test('provider multiple choice selection toggles count and bulk actions bar', () => {
    const dom = new JSDOM(`
        <div id="bulk-actions-bar" class="hidden">
            <span id="bulk-selected-count">0</span>
            <span id="bulk-selected-badge">0</span>
        </div>
        <table>
            <tr id="provider-row-uuid-1">
                <td><input type="checkbox" class="provider-checkbox" value="uuid-1" checked /></td>
            </tr>
            <tr id="provider-row-uuid-2">
                <td><input type="checkbox" class="provider-checkbox" value="uuid-2" checked /></td>
            </tr>
            <tr id="provider-row-uuid-3">
                <td><input type="checkbox" class="provider-checkbox" value="uuid-3" /></td>
            </tr>
        </table>
    `);

    const { document } = dom.window;
    const rowCheckboxes = Array.from(document.querySelectorAll('.provider-checkbox'));
    const selected = rowCheckboxes.filter(cb => cb.checked);
    const bulkBar = document.getElementById('bulk-actions-bar');
    const countLabel = document.getElementById('bulk-selected-count');
    const badgeLabel = document.getElementById('bulk-selected-badge');

    if (selected.length > 0) {
        bulkBar.classList.remove('hidden');
        countLabel.textContent = selected.length.toString();
        badgeLabel.textContent = selected.length.toString();
    }

    assert.equal(selected.length, 2);
    assert.equal(bulkBar.classList.contains('hidden'), false);
    assert.equal(countLabel.textContent, '2');
    assert.equal(badgeLabel.textContent, '2');
});

test('model multiple choice initialization reads available models from presets and marks initial selected', () => {
    const PRESETS = {
        openai: {
            default_models: ['gpt-4o', 'gpt-4o-mini', 'gpt-4-turbo', 'gpt-3.5-turbo'],
            default_model: 'gpt-4o',
        },
    };

    const initialSelected = ['gpt-4o', 'custom-finetune'];
    const presetModels = PRESETS.openai.default_models;
    const allModels = Array.from(new Set([...presetModels, ...initialSelected]));
    const selectedSet = new Set(initialSelected);

    assert.equal(allModels.length, 5);
    assert.equal(allModels.includes('custom-finetune'), true);
    assert.equal(selectedSet.has('gpt-4o'), true);
    assert.equal(selectedSet.has('custom-finetune'), true);
    assert.equal(selectedSet.has('gpt-4o-mini'), false);
});

test('model multiple choice search filtering shows only matching models without losing checked state', () => {
    const dom = new JSDOM(`
        <div id="container-provider-models">
            <input type="text" id="model-search-input" value="mini" />
            <div id="model-multiselect-container">
                <div class="model-multiselect-item" data-model="gpt-4o">
                    <input type="checkbox" id="chk-0" value="gpt-4o" checked />
                    <span>gpt-4o</span>
                </div>
                <div class="model-multiselect-item" data-model="gpt-4o-mini">
                    <input type="checkbox" id="chk-1" value="gpt-4o-mini" checked />
                    <span>gpt-4o-mini</span>
                </div>
                <div class="model-multiselect-item" data-model="o3-mini">
                    <input type="checkbox" id="chk-2" value="o3-mini" />
                    <span>o3-mini</span>
                </div>
            </div>
            <div id="model-empty-search-state" class="hidden"></div>
        </div>
    `);

    const { document } = dom.window;
    const query = document.getElementById('model-search-input').value.toLowerCase().trim();
    const rows = document.querySelectorAll('.model-multiselect-item');
    let visible = 0;

    rows.forEach(row => {
        const model = row.getAttribute('data-model') || '';
        if (model.includes(query)) {
            row.classList.remove('hidden');
            visible++;
        } else {
            row.classList.add('hidden');
        }
    });

    assert.equal(visible, 2);
    assert.equal(rows[0].classList.contains('hidden'), true);
    assert.equal(rows[1].classList.contains('hidden'), false);
    assert.equal(rows[2].classList.contains('hidden'), false);

    // Assert checkbox states remain checked
    assert.equal(document.getElementById('chk-0').checked, true);
    assert.equal(document.getElementById('chk-1').checked, true);
    assert.equal(document.getElementById('chk-2').checked, false);
});

test('model multiple choice select all and deselect all updates state and hidden input', () => {
    const available = ['gemini-2.0-flash', 'gemini-1.5-pro', 'gemini-1.5-flash'];
    let selectedSet = new Set(['gemini-2.0-flash']);

    // Select all
    available.forEach(m => selectedSet.add(m));
    assert.equal(selectedSet.size, 3);
    const hiddenValueAll = Array.from(selectedSet).join(', ');
    assert.equal(hiddenValueAll, 'gemini-2.0-flash, gemini-1.5-pro, gemini-1.5-flash');

    // Deselect all
    selectedSet.clear();
    assert.equal(selectedSet.size, 0);
    const hiddenValueEmpty = Array.from(selectedSet).join(', ');
    assert.equal(hiddenValueEmpty, '');
});

test('model multiple choice custom model addition integrates into list and marks it selected', () => {
    const available = ['llama-3.3-70b-versatile', 'llama-3.1-8b-instant'];
    const selectedSet = new Set(['llama-3.3-70b-versatile']);

    const customModel = 'deepseek-r1-distill-llama-70b';
    if (!available.includes(customModel)) {
        available.push(customModel);
    }
    selectedSet.add(customModel);

    assert.equal(available.includes(customModel), true);
    assert.equal(selectedSet.has(customModel), true);
    assert.equal(selectedSet.size, 2);
    assert.equal(Array.from(selectedSet).includes(customModel), true);
});

test('provider multiple choice persists selections across simulated pagination navigation using storage', () => {
    let mockStorage = {};
    const storageApi = {
        getItem: (k) => mockStorage[k] || null,
        setItem: (k, v) => { mockStorage[k] = v; },
        removeItem: (k) => { delete mockStorage[k]; },
    };

    const STORAGE_KEY = 'sp_selected_providers';

    // Page 1: 3 providers, user selects prov-1 and prov-2
    const page1Dom = new JSDOM(`
        <input type="checkbox" id="select-all-providers" />
        <div id="bulk-actions-bar" class="hidden">
            <span id="bulk-selected-count">0</span>
        </div>
        <input type="checkbox" class="provider-checkbox" value="prov-1" data-name="Provider 1" />
        <input type="checkbox" class="provider-checkbox" value="prov-2" data-name="Provider 2" />
        <input type="checkbox" class="provider-checkbox" value="prov-3" data-name="Provider 3" />
    `);

    // Simulate selecting prov-1 and prov-2 on page 1 and saving to storage
    let stored = {};
    stored['prov-1'] = { uuid: 'prov-1', name: 'Provider 1' };
    stored['prov-2'] = { uuid: 'prov-2', name: 'Provider 2' };
    storageApi.setItem(STORAGE_KEY, JSON.stringify(stored));

    // Page 2: user navigates to page 2 (new DOM)
    const page2Dom = new JSDOM(`
        <input type="checkbox" id="select-all-providers" />
        <div id="bulk-actions-bar" class="hidden">
            <span id="bulk-selected-count">0</span>
            <span id="bulk-selected-badge">0</span>
        </div>
        <input type="checkbox" class="provider-checkbox" value="prov-4" data-name="Provider 4" />
        <input type="checkbox" class="provider-checkbox" value="prov-5" data-name="Provider 5" />
    `);

    // On Page 2 load: read storage and update checkboxes
    const loadedStored = JSON.parse(storageApi.getItem(STORAGE_KEY));
    assert.equal(Object.keys(loadedStored).length, 2);

    const page2Cbs = Array.from(page2Dom.window.document.querySelectorAll('.provider-checkbox'));
    page2Cbs.forEach(cb => {
        if (loadedStored[cb.value]) cb.checked = true;
    });

    // On Page 2, prov-4 and prov-5 are unchecked, but total stored count is 2
    assert.equal(page2Cbs[0].checked, false);
    assert.equal(page2Cbs[1].checked, false);
    assert.equal(Object.keys(loadedStored).length, 2);

    // User checks prov-4 on Page 2
    page2Cbs[0].checked = true;
    loadedStored[page2Cbs[0].value] = { uuid: page2Cbs[0].value, name: 'Provider 4' };
    storageApi.setItem(STORAGE_KEY, JSON.stringify(loadedStored));

    // Total stored count becomes 3
    const updatedStored = JSON.parse(storageApi.getItem(STORAGE_KEY));
    assert.equal(Object.keys(updatedStored).length, 3);
    assert.ok(updatedStored['prov-1']);
    assert.ok(updatedStored['prov-2']);
    assert.ok(updatedStored['prov-4']);

    // User navigates back to Page 1: prov-1 and prov-2 must still be checked!
    const page1Cbs = Array.from(page1Dom.window.document.querySelectorAll('.provider-checkbox'));
    page1Cbs.forEach(cb => {
        cb.checked = !!updatedStored[cb.value];
    });

    assert.equal(page1Cbs[0].checked, true);
    assert.equal(page1Cbs[1].checked, true);
    assert.equal(page1Cbs[2].checked, false);

    // After bulk deletion, storage is cleared
    storageApi.removeItem(STORAGE_KEY);
    assert.equal(storageApi.getItem(STORAGE_KEY), null);
});

test('document multiple choice persists selections across simulated pagination navigation using storage', () => {
    let mockStorage = {};
    const storageApi = {
        getItem: (k) => mockStorage[k] || null,
        setItem: (k, v) => { mockStorage[k] = v; },
        removeItem: (k) => { delete mockStorage[k]; },
    };

    const STORAGE_KEY = 'sp_selected_documents';

    // Page 1: select doc-1
    let stored = {};
    stored['doc-1'] = { uuid: 'doc-1', title: 'Naskah Bab 1' };
    storageApi.setItem(STORAGE_KEY, JSON.stringify(stored));

    // Page 2: select doc-6
    let storedPage2 = JSON.parse(storageApi.getItem(STORAGE_KEY));
    assert.equal(Object.keys(storedPage2).length, 1);
    storedPage2['doc-6'] = { uuid: 'doc-6', title: 'Naskah Bab 6' };
    storageApi.setItem(STORAGE_KEY, JSON.stringify(storedPage2));

    // Total selections across both pages is 2
    const totalStored = JSON.parse(storageApi.getItem(STORAGE_KEY));
    assert.equal(Object.keys(totalStored).length, 2);
    assert.deepEqual(Object.keys(totalStored), ['doc-1', 'doc-6']);

    // Form inputs array contains both uuids
    const bulkUuids = Object.values(totalStored).map(item => item.uuid);
    assert.deepEqual(bulkUuids, ['doc-1', 'doc-6']);
});

test('chat model dropdown conditionally hides search input when models count is 1 or 2, and shows when > 2', () => {
    const dom = new JSDOM(`
        <div id="chat-model-searchable-wrapper">
            <button id="chat-model-search-trigger">
                <span id="chat-model-selected-text">Pilih Model</span>
            </button>
            <div id="chat-model-dropdown">
                <div id="chat-model-search-container">
                    <input type="text" id="chat-model-search-input" />
                </div>
                <ul id="chat-model-options-list"></ul>
            </div>
        </div>
    `);

    const { document } = dom.window;
    const searchContainer = document.getElementById('chat-model-search-container');

    // Case 1: 1 model -> search container must be hidden
    const models1 = ['gpt-4o'];
    if (models1.length <= 2) {
        searchContainer.classList.add('hidden');
    } else {
        searchContainer.classList.remove('hidden');
    }
    assert.equal(searchContainer.classList.contains('hidden'), true);

    // Case 2: 2 models -> search container must be hidden
    const models2 = ['gpt-4o', 'gpt-4o-mini'];
    if (models2.length <= 2) {
        searchContainer.classList.add('hidden');
    } else {
        searchContainer.classList.remove('hidden');
    }
    assert.equal(searchContainer.classList.contains('hidden'), true);

    // Case 3: 3 models -> search container must be shown (NOT hidden)
    const models3 = ['gpt-4o', 'gpt-4o-mini', 'o3-mini'];
    if (models3.length <= 2) {
        searchContainer.classList.add('hidden');
    } else {
        searchContainer.classList.remove('hidden');
    }
    assert.equal(searchContainer.classList.contains('hidden'), false);

    // Case 4: 0 models -> search container must be hidden
    const models0 = [];
    if (models0.length <= 2) {
        searchContainer.classList.add('hidden');
    } else {
        searchContainer.classList.remove('hidden');
    }
    assert.equal(searchContainer.classList.contains('hidden'), true);
});

test('chat model dropdown filters options based on search query', () => {
    const dom = new JSDOM(`
        <div id="chat-model-searchable-wrapper">
            <input type="text" id="chat-model-search-input" value="mini" />
            <ul id="chat-model-options-list">
                <li class="chat-model-option-item" data-model="gpt-4o">gpt-4o</li>
                <li class="chat-model-option-item" data-model="gpt-4o-mini">gpt-4o-mini</li>
                <li class="chat-model-option-item" data-model="o3-mini">o3-mini</li>
                <li class="chat-model-option-item" data-model="claude-3-5-sonnet">claude-3-5-sonnet</li>
            </ul>
            <div id="chat-model-empty-result" class="hidden">Model tidak ditemukan</div>
        </div>
    `);

    const { document } = dom.window;
    const query = (document.getElementById('chat-model-search-input')?.value || '').toLowerCase().trim();
    const items = document.querySelectorAll('#chat-model-options-list .chat-model-option-item');
    let visibleCount = 0;

    items.forEach(item => {
        const model = (item.getAttribute('data-model') || '').toLowerCase();
        if (model.includes(query)) {
            item.classList.remove('hidden');
            visibleCount++;
        } else {
            item.classList.add('hidden');
        }
    });

    assert.equal(visibleCount, 2);
    assert.equal(items[0].classList.contains('hidden'), true);
    assert.equal(items[1].classList.contains('hidden'), false); // gpt-4o-mini
    assert.equal(items[2].classList.contains('hidden'), false); // o3-mini
    assert.equal(items[3].classList.contains('hidden'), true);

    const emptyResult = document.getElementById('chat-model-empty-result');
    if (visibleCount === 0 && items.length > 0) {
        emptyResult.classList.remove('hidden');
    } else {
        emptyResult.classList.add('hidden');
    }
    assert.equal(emptyResult.classList.contains('hidden'), true);
});

test('UiController responsive drawer and backdrop logic for narrow and wide viewports', () => {
    const dom = new JSDOM(`
        <div id="outline-backdrop" class="hidden"></div>
        <aside id="outline-sidebar" class="hidden"></aside>
        <div id="wordcraft-backdrop" class="hidden"></div>
        <aside id="wordcraft-sidebar" class="hidden"></aside>
        <div id="btn-toggle-outline"><input type="checkbox" /></div>
    `);

    const { window } = dom;
    const { document } = window;

    // Simulate narrow screen (< 1280px, e.g. mobile 375px or tablet 768px)
    let isNarrow = true;

    // Helper functions mirroring UiController
    function toggleOutline(forceOpen = null, isOutlineVisible) {
        const sidebar = document.getElementById('outline-sidebar');
        const backdrop = document.getElementById('outline-backdrop');
        const newVisible = forceOpen !== null ? forceOpen : !isOutlineVisible;

        sidebar.classList.toggle('hidden', !newVisible);
        if (backdrop) {
            backdrop.classList.toggle('hidden', !newVisible || !isNarrow);
        }
        return newVisible;
    }

    function toggleWordcraft(forceOpen = null, isWordcraftVisible) {
        const sidebar = document.getElementById('wordcraft-sidebar');
        const backdrop = document.getElementById('wordcraft-backdrop');
        const newVisible = forceOpen !== null ? forceOpen : !isWordcraftVisible;

        sidebar.classList.toggle('hidden', !newVisible);
        if (backdrop) {
            backdrop.classList.toggle('hidden', !newVisible || !isNarrow);
        }
        return newVisible;
    }

    // 1. Open outline on mobile: sidebar and backdrop must be visible
    let outlineVis = toggleOutline(true, false);
    assert.equal(outlineVis, true);
    assert.equal(document.getElementById('outline-sidebar').classList.contains('hidden'), false);
    assert.equal(document.getElementById('outline-backdrop').classList.contains('hidden'), false);

    // 2. Close outline on mobile: sidebar and backdrop must be hidden
    outlineVis = toggleOutline(false, outlineVis);
    assert.equal(outlineVis, false);
    assert.equal(document.getElementById('outline-sidebar').classList.contains('hidden'), true);
    assert.equal(document.getElementById('outline-backdrop').classList.contains('hidden'), true);

    // 3. Open Wordcraft AI on mobile: sidebar and backdrop visible
    let wcVis = toggleWordcraft(true, false);
    assert.equal(wcVis, true);
    assert.equal(document.getElementById('wordcraft-sidebar').classList.contains('hidden'), false);
    assert.equal(document.getElementById('wordcraft-backdrop').classList.contains('hidden'), false);

    // 4. Switch to wide screen (>= 1280px desktop): backdrop should never be shown even when open
    isNarrow = false;
    wcVis = toggleWordcraft(true, false);
    assert.equal(wcVis, true);
    assert.equal(document.getElementById('wordcraft-sidebar').classList.contains('hidden'), false);
    assert.equal(document.getElementById('wordcraft-backdrop').classList.contains('hidden'), true);
});
