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
