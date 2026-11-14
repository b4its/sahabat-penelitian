import test from 'node:test';
import assert from 'node:assert/strict';
import { JSDOM } from 'jsdom';

function setupPersonalizeDom() {
    const dom = new JSDOM(`
        <!DOCTYPE html>
        <html>
        <body>
            <select id="personalize-provider-select" class="hidden">
                <option value="uuid-openai" selected data-name="OpenAI GPT-4o Studio" data-label="OpenAI">OpenAI</option>
                <option value="uuid-gemini" data-name="Google Gemini 2.0 Flash" data-label="Google Gemini">Gemini</option>
                <option value="uuid-anthropic" data-name="Anthropic Claude 3.5 Sonnet" data-label="Anthropic Claude">Claude</option>
            </select>

            <button type="button" id="personalize-provider-search-trigger" aria-expanded="false">
                <span id="personalize-provider-selected-text">OpenAI GPT-4o Studio</span>
                <i id="personalize-provider-chevron" class="fa-solid fa-chevron-down"></i>
            </button>

            <div id="personalize-provider-dropdown" class="hidden">
                <input type="text" id="personalize-provider-search-input" />
                <ul id="personalize-provider-options-list">
                    <li class="personalize-provider-option-item" data-uuid="uuid-openai" data-name="OpenAI GPT-4o Studio" data-label="OpenAI">OpenAI</li>
                    <li class="personalize-provider-option-item" data-uuid="uuid-gemini" data-name="Google Gemini 2.0 Flash" data-label="Google Gemini">Gemini</li>
                    <li class="personalize-provider-option-item" data-uuid="uuid-anthropic" data-name="Anthropic Claude 3.5 Sonnet" data-label="Anthropic Claude">Claude</li>
                </ul>
                <div id="personalize-provider-empty-result" class="hidden">Provider tidak ditemukan</div>
            </div>

            <!-- Frontier Model Searchable Elements -->
            <select id="frontier-model-select" class="hidden">
                <option value="gpt-4o" selected>gpt-4o</option>
                <option value="o1-preview">o1-preview</option>
                <option value="claude-3-5-sonnet">claude-3-5-sonnet</option>
            </select>
            <button type="button" id="frontier-model-search-trigger" aria-expanded="false">
                <span id="frontier-model-selected-text">gpt-4o</span>
                <i id="frontier-model-chevron" class="fa-solid fa-chevron-down"></i>
            </button>
            <div id="frontier-model-dropdown" class="hidden">
                <input type="text" id="frontier-model-search-input" />
                <ul id="frontier-model-options-list">
                    <li class="frontier-model-option-item" data-model="gpt-4o">gpt-4o</li>
                    <li class="frontier-model-option-item" data-model="o1-preview">o1-preview</li>
                    <li class="frontier-model-option-item" data-model="claude-3-5-sonnet">claude-3-5-sonnet</li>
                </ul>
                <div id="frontier-model-empty-result" class="hidden">Model tidak ditemukan</div>
            </div>

            <!-- Worker Model Searchable Elements -->
            <select id="worker-model-select" class="hidden">
                <option value="gpt-4o-mini" selected>gpt-4o-mini</option>
                <option value="claude-3-haiku">claude-3-haiku</option>
                <option value="gemini-2-flash">gemini-2-flash</option>
            </select>
            <button type="button" id="worker-model-search-trigger" aria-expanded="false">
                <span id="worker-model-selected-text">gpt-4o-mini</span>
                <i id="worker-model-chevron" class="fa-solid fa-chevron-down"></i>
            </button>
            <div id="worker-model-dropdown" class="hidden">
                <input type="text" id="worker-model-search-input" />
                <ul id="worker-model-options-list">
                    <li class="worker-model-option-item" data-model="gpt-4o-mini">gpt-4o-mini</li>
                    <li class="worker-model-option-item" data-model="claude-3-haiku">claude-3-haiku</li>
                    <li class="worker-model-option-item" data-model="gemini-2-flash">gemini-2-flash</li>
                </ul>
                <div id="worker-model-empty-result" class="hidden">Model tidak ditemukan</div>
            </div>

            <input type="text" id="simulator-input" />
            <div id="simulator-result" class="hidden"></div>
        </body>
        </html>
    `, { url: 'http://localhost' });

    return dom;
}

test('personalize provider dropdown filters options based on search query', () => {
    const dom = setupPersonalizeDom();
    const { document } = dom.window;

    const searchInput = document.getElementById('personalize-provider-search-input');
    const items = document.querySelectorAll('#personalize-provider-options-list .personalize-provider-option-item');
    const emptyResult = document.getElementById('personalize-provider-empty-result');

    function filterPersonalizeProviders() {
        const query = (searchInput?.value || '').toLowerCase().trim();
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
        if (emptyResult) {
            emptyResult.classList.toggle('hidden', visibleCount > 0);
        }
    }

    // Search for 'gemini'
    searchInput.value = 'gemini';
    filterPersonalizeProviders();
    assert.equal(items[0].classList.contains('hidden'), true);
    assert.equal(items[1].classList.contains('hidden'), false);
    assert.equal(items[2].classList.contains('hidden'), true);
    assert.equal(emptyResult.classList.contains('hidden'), true);

    // Search for non-existent keyword
    searchInput.value = 'unknown-cloud';
    filterPersonalizeProviders();
    assert.equal(items[0].classList.contains('hidden'), true);
    assert.equal(items[1].classList.contains('hidden'), true);
    assert.equal(items[2].classList.contains('hidden'), true);
    assert.equal(emptyResult.classList.contains('hidden'), false);

    // Reset search
    searchInput.value = '';
    filterPersonalizeProviders();
    assert.equal(items[0].classList.contains('hidden'), false);
    assert.equal(items[1].classList.contains('hidden'), false);
    assert.equal(items[2].classList.contains('hidden'), false);
    assert.equal(emptyResult.classList.contains('hidden'), true);
});

test('personalize interactive routing simulator routes reasoning to frontier and micro-tasks to worker', () => {
    const dom = setupPersonalizeDom();
    const { document } = dom.window;

    function simulateRouting(query) {
        const q = query.toLowerCase();
        const frontierKeywords = ['hipotesis', 'teori', 'sintesis', 'brainstorm', 'kritis', 'analisis', 'bab', 'metode', 'argumen'];
        const isFrontier = frontierKeywords.some(kw => q.includes(kw));
        const frontierModel = document.getElementById('frontier-model-select').value;
        const workerModel = document.getElementById('worker-model-select').value;

        return {
            isFrontier,
            modelAssigned: isFrontier ? frontierModel : workerModel,
            roleAssigned: isFrontier ? 'Frontier Model' : 'Worker Model',
        };
    }

    const testFrontier = simulateRouting('Sintesis teori disrupsi dengan temuan empiris pada bab 2');
    assert.equal(testFrontier.isFrontier, true);
    assert.equal(testFrontier.roleAssigned, 'Frontier Model');
    assert.equal(testFrontier.modelAssigned, 'gpt-4o');

    const testWorker = simulateRouting('Koreksi kata aktifitas dan format sitasi APA');
    assert.equal(testWorker.isFrontier, false);
    assert.equal(testWorker.roleAssigned, 'Worker Model');
    assert.equal(testWorker.modelAssigned, 'gpt-4o-mini');
});

test('frontier model dropdown filters options and selects model correctly', () => {
    const dom = setupPersonalizeDom();
    const { document } = dom.window;

    const searchInput = document.getElementById('frontier-model-search-input');
    const items = document.querySelectorAll('#frontier-model-options-list .frontier-model-option-item');
    const emptyResult = document.getElementById('frontier-model-empty-result');
    const select = document.getElementById('frontier-model-select');
    const selectedText = document.getElementById('frontier-model-selected-text');

    function filterFrontierModels() {
        const query = (searchInput?.value || '').toLowerCase().trim();
        let visibleCount = 0;
        items.forEach(item => {
            const modelName = (item.getAttribute('data-model') || '').toLowerCase();
            if (!query || modelName.includes(query)) {
                item.classList.remove('hidden');
                visibleCount++;
            } else {
                item.classList.add('hidden');
            }
        });
        if (emptyResult) {
            emptyResult.classList.toggle('hidden', visibleCount > 0);
        }
    }

    function selectFrontierModel(modelName) {
        if (select) {
            select.value = modelName;
        }
        if (selectedText) {
            selectedText.textContent = modelName;
        }
    }

    // Filter for 'claude'
    searchInput.value = 'claude';
    filterFrontierModels();
    assert.equal(items[0].classList.contains('hidden'), true); // gpt-4o
    assert.equal(items[1].classList.contains('hidden'), true); // o1-preview
    assert.equal(items[2].classList.contains('hidden'), false); // claude-3-5-sonnet
    assert.equal(emptyResult.classList.contains('hidden'), true);

    // Filter for non-existent
    searchInput.value = 'llama';
    filterFrontierModels();
    assert.equal(items[0].classList.contains('hidden'), true);
    assert.equal(items[1].classList.contains('hidden'), true);
    assert.equal(items[2].classList.contains('hidden'), true);
    assert.equal(emptyResult.classList.contains('hidden'), false);

    // Select 'o1-preview'
    selectFrontierModel('o1-preview');
    assert.equal(select.value, 'o1-preview');
    assert.equal(selectedText.textContent, 'o1-preview');
});

test('worker model dropdown filters options and selects model correctly', () => {
    const dom = setupPersonalizeDom();
    const { document } = dom.window;

    const searchInput = document.getElementById('worker-model-search-input');
    const items = document.querySelectorAll('#worker-model-options-list .worker-model-option-item');
    const emptyResult = document.getElementById('worker-model-empty-result');
    const select = document.getElementById('worker-model-select');
    const selectedText = document.getElementById('worker-model-selected-text');

    function filterWorkerModels() {
        const query = (searchInput?.value || '').toLowerCase().trim();
        let visibleCount = 0;
        items.forEach(item => {
            const modelName = (item.getAttribute('data-model') || '').toLowerCase();
            if (!query || modelName.includes(query)) {
                item.classList.remove('hidden');
                visibleCount++;
            } else {
                item.classList.add('hidden');
            }
        });
        if (emptyResult) {
            emptyResult.classList.toggle('hidden', visibleCount > 0);
        }
    }

    function selectWorkerModel(modelName) {
        if (select) {
            select.value = modelName;
        }
        if (selectedText) {
            selectedText.textContent = modelName;
        }
    }

    // Filter for 'gemini'
    searchInput.value = 'gemini';
    filterWorkerModels();
    assert.equal(items[0].classList.contains('hidden'), true); // gpt-4o-mini
    assert.equal(items[1].classList.contains('hidden'), true); // claude-3-haiku
    assert.equal(items[2].classList.contains('hidden'), false); // gemini-2-flash
    assert.equal(emptyResult.classList.contains('hidden'), true);

    // Select 'claude-3-haiku'
    selectWorkerModel('claude-3-haiku');
    assert.equal(select.value, 'claude-3-haiku');
    assert.equal(selectedText.textContent, 'claude-3-haiku');
});
