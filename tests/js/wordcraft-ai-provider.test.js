import test from 'node:test';
import assert from 'node:assert/strict';
import { JSDOM } from 'jsdom';
import { WordcraftAi } from '../../resources/js/editor/wordcraft-ai.js';

function setupWordcraftAiDom() {
    const dom = new JSDOM(`
        <!DOCTYPE html>
        <html>
        <body>
            <div id="editor-mock">Isi dokumen uji untuk Xishie Studio</div>
            <div id="wordcraft-sidebar">
                <select id="wordcraft-provider-select" class="hidden">
                    <option value="uuid-openai" selected data-name="OpenAI GPT-4o" data-label="OpenAI" data-badge="bg-emerald-50 text-emerald-700">OpenAI GPT-4o</option>
                    <option value="uuid-gemini" data-name="Google Gemini 2.0 Flash" data-label="Google Gemini" data-badge="bg-blue-50 text-blue-700">Google Gemini 2.0 Flash</option>
                    <option value="uuid-anthropic" data-name="Anthropic Claude 3.5 Sonnet" data-label="Anthropic Claude" data-badge="bg-amber-50 text-amber-700">Anthropic Claude 3.5 Sonnet</option>
                </select>

                <div id="wordcraft-provider-searchable-wrapper">
                    <button type="button" id="wordcraft-provider-search-trigger" aria-expanded="false">
                        <span id="wordcraft-provider-selected-text">OpenAI GPT-4o</span>
                        <i id="wordcraft-provider-chevron" class="fa-solid fa-chevron-down"></i>
                    </button>

                    <div id="wordcraft-provider-dropdown" class="hidden">
                        <input type="text" id="wordcraft-provider-search-input" />
                        <ul id="wordcraft-provider-options-list">
                            <li class="wordcraft-provider-option-item" data-uuid="uuid-openai" data-name="OpenAI GPT-4o" data-label="OpenAI" data-badge="bg-emerald-50 text-emerald-700">OpenAI GPT-4o</li>
                            <li class="wordcraft-provider-option-item" data-uuid="uuid-gemini" data-name="Google Gemini 2.0 Flash" data-label="Google Gemini" data-badge="bg-blue-50 text-blue-700">Google Gemini 2.0 Flash</li>
                            <li class="wordcraft-provider-option-item" data-uuid="uuid-anthropic" data-name="Anthropic Claude 3.5 Sonnet" data-label="Anthropic Claude" data-badge="bg-amber-50 text-amber-700">Anthropic Claude 3.5 Sonnet</li>
                        </ul>
                        <div id="wordcraft-provider-empty-result" class="hidden">Provider tidak ditemukan</div>
                    </div>
                </div>

                <div id="wordcraft-results-container"></div>
                <input type="text" id="wordcraft-custom-prompt" />
                <select id="wordcraft-style-select">
                    <option value="formal">Formal</option>
                </select>
                <div id="wordcraft-loading-spinner" class="hidden"></div>
            </div>
        </body>
        </html>
    `, { url: 'http://localhost' });

    global.window = dom.window;
    global.document = dom.window.document;
    global.Event = dom.window.Event;

    const mockEngine = {
        editorEl: dom.window.document.getElementById('editor-mock'),
        getSelectionState: () => ({ hasSelection: false, text: '' }),
        insertAtCursor: () => {},
        replaceSelectedText: () => {},
    };

    const ai = new WordcraftAi(mockEngine, {
        containerEl: dom.window.document.getElementById('wordcraft-sidebar'),
        resultsEl: dom.window.document.getElementById('wordcraft-results-container'),
        promptInput: dom.window.document.getElementById('wordcraft-custom-prompt'),
        styleSelect: dom.window.document.getElementById('wordcraft-style-select'),
        statusEl: dom.window.document.getElementById('wordcraft-loading-spinner'),
        providerSelect: dom.window.document.getElementById('wordcraft-provider-select'),
        providerDropdown: dom.window.document.getElementById('wordcraft-provider-dropdown'),
        providerTrigger: dom.window.document.getElementById('wordcraft-provider-search-trigger'),
        providerSearchInput: dom.window.document.getElementById('wordcraft-provider-search-input'),
        providerOptionsList: dom.window.document.getElementById('wordcraft-provider-options-list'),
        providerSelectedText: dom.window.document.getElementById('wordcraft-provider-selected-text'),
        providerEmptyResult: dom.window.document.getElementById('wordcraft-provider-empty-result'),
        providerChevron: dom.window.document.getElementById('wordcraft-provider-chevron'),
    });

    return { dom, ai, document: dom.window.document };
}

test('wordcraft ai initializes provider select and returns active default provider', () => {
    const { ai } = setupWordcraftAiDom();
    assert.equal(ai.getSelectedProvider(), 'uuid-openai');
});

test('wordcraft ai toggleProviderDropdown opens and closes dropdown with chevron rotation', () => {
    const { ai, document } = setupWordcraftAiDom();
    const dropdown = document.getElementById('wordcraft-provider-dropdown');
    const chevron = document.getElementById('wordcraft-provider-chevron');
    const trigger = document.getElementById('wordcraft-provider-search-trigger');

    assert.equal(dropdown.classList.contains('hidden'), true);
    assert.equal(trigger.getAttribute('aria-expanded'), 'false');

    ai.toggleProviderDropdown(true);
    assert.equal(dropdown.classList.contains('hidden'), false);
    assert.equal(chevron.classList.contains('rotate-180'), true);
    assert.equal(trigger.getAttribute('aria-expanded'), 'true');

    ai.toggleProviderDropdown(false);
    assert.equal(dropdown.classList.contains('hidden'), true);
    assert.equal(chevron.classList.contains('rotate-180'), false);
    assert.equal(trigger.getAttribute('aria-expanded'), 'false');
});

test('wordcraft ai filters provider options based on search query', () => {
    const { ai, document } = setupWordcraftAiDom();
    const searchInput = document.getElementById('wordcraft-provider-search-input');
    const items = document.querySelectorAll('.wordcraft-provider-option-item');
    const emptyResult = document.getElementById('wordcraft-provider-empty-result');

    // Search for 'gemini'
    searchInput.value = 'gemini';
    ai.filterProviderOptions();

    assert.equal(items[0].classList.contains('hidden'), true);
    assert.equal(items[1].classList.contains('hidden'), false); // Google Gemini matches
    assert.equal(items[2].classList.contains('hidden'), true);
    assert.equal(emptyResult.classList.contains('hidden'), true);

    // Search for non-existent provider
    searchInput.value = 'deepseek-unknown';
    ai.filterProviderOptions();

    assert.equal(items[0].classList.contains('hidden'), true);
    assert.equal(items[1].classList.contains('hidden'), true);
    assert.equal(items[2].classList.contains('hidden'), true);
    assert.equal(emptyResult.classList.contains('hidden'), false);

    // Clear search
    searchInput.value = '';
    ai.filterProviderOptions();
    assert.equal(items[0].classList.contains('hidden'), false);
    assert.equal(items[1].classList.contains('hidden'), false);
    assert.equal(items[2].classList.contains('hidden'), false);
    assert.equal(emptyResult.classList.contains('hidden'), true);
});

test('wordcraft ai selectProvider updates select value, text display, and closes dropdown', () => {
    const { ai, document } = setupWordcraftAiDom();
    const select = document.getElementById('wordcraft-provider-select');
    const selectedText = document.getElementById('wordcraft-provider-selected-text');
    const dropdown = document.getElementById('wordcraft-provider-dropdown');

    ai.toggleProviderDropdown(true);
    assert.equal(dropdown.classList.contains('hidden'), false);

    ai.selectProvider('uuid-gemini');

    assert.equal(select.value, 'uuid-gemini');
    assert.equal(ai.getSelectedProvider(), 'uuid-gemini');
    assert.match(selectedText.textContent, /Google Gemini 2\.0 Flash/);
    assert.equal(dropdown.classList.contains('hidden'), true);
});
