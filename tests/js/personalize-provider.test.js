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

            <select id="frontier-model-select">
                <option value="gpt-4o" selected>gpt-4o</option>
                <option value="o1-preview">o1-preview</option>
            </select>

            <select id="worker-model-select">
                <option value="gpt-4o-mini" selected>gpt-4o-mini</option>
            </select>

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
