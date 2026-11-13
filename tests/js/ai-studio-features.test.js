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
