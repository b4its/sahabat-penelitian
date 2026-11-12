import test from 'node:test';
import assert from 'node:assert/strict';
import { JSDOM } from 'jsdom';
import { DocumentEngine } from '../../resources/js/editor/document-engine.js';

function outline(html) {
    const dom = new JSDOM(`<div id="document-pages-wrapper">${html}</div>`);
    globalThis.document = dom.window.document;
    const engine = Object.create(DocumentEngine.prototype);
    engine.getAllEditorBodies = () => Array.from(document.querySelectorAll('.page-sheet-body'));
    let entries = [];
    engine.onOutlineChange = headings => { entries = headings; };
    engine.updateOutline();
    return { engine, entries: () => entries };
}

test('outline gives repeated imported heading ids distinct destinations across pages', () => {
    const { engine, entries } = outline(`
        <div class="page-sheet-body"><h1 id="heading-0">BAB I PENDAHULUAN</h1><h2 id="heading-1">1.1 Pendahuluan</h2></div>
        <div class="page-sheet-body"><h1 id="heading-0">BAB II METODE</h1><h2 id="heading-1">2.1 Metodologi</h2></div>
    `);
    const first = entries();
    assert.equal(first.length, 4);
    assert.equal(new Set(first.map(h => h.id)).size, 4);
    for (const heading of first) {
        assert.equal(document.getElementById(heading.id).textContent, heading.text);
    }
    assert.deepEqual(first.map(h => h.pageNumber), [1, 1, 2, 2]);
    engine.updateOutline();
    assert.deepEqual(entries().map(h => h.id), first.map(h => h.id));
});

test('later streamed pages and new headings never reuse an existing anchor', () => {
    const { engine, entries } = outline('<div class="page-sheet-body"><h1 id="heading-0">BAB I AWAL</h1></div>');
    const wrapper = document.getElementById('document-pages-wrapper');
    const later = document.createElement('div');
    later.className = 'page-sheet-body';
    later.innerHTML = '<h1 id="heading-0">BAB II LANJUTAN</h1><h2>2.1 Subbab Baru</h2>';
    wrapper.appendChild(later);
    engine.updateOutline();
    assert.equal(entries().length, 3);
    assert.equal(new Set(entries().map(h => h.id)).size, 3);
    assert.equal(document.getElementById(entries()[2].id).textContent, '2.1 Subbab Baru');
});

test('outline does not replace a valid unique imported heading anchor', () => {
    const { entries } = outline('<div class="page-sheet-body"><h1 id="bab-penelitian">BAB III HASIL</h1><p>Isi</p></div>');
    assert.equal(entries()[0].id, 'bab-penelitian');
    assert.equal(entries()[0].text, 'BAB III HASIL');
});
