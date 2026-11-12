import test from 'node:test';
import assert from 'node:assert/strict';
import { JSDOM } from 'jsdom';
import { DocumentEngine } from '../../resources/js/editor/document-engine.js';

function setup(html) {
    const dom = new JSDOM(`<div id="document-pages-wrapper"><article class="document-page-sheet"><div class="page-sheet-body">${html}</div></article></div>`);
    globalThis.document = dom.window.document;
    globalThis.window = dom.window;
    const engine = Object.create(DocumentEngine.prototype);
    engine.pagesWrapper = document.getElementById('document-pages-wrapper');
    const body = document.querySelector('.page-sheet-body');
    body.getBoundingClientRect = () => ({ left: 0, right: 500, top: 0, bottom: 750, width: 500 });
    return { engine, body };
}

test('a normal image click does not wrap or move the image', () => {
    const { engine, body } = setup('<p><img src="image.png">teks di sebelahnya</p>');
    const img = body.querySelector('img');
    engine.startDocumentObjectDrag(img, { button: 0, clientX: 10, clientY: 10, preventDefault() {} });
    window.dispatchEvent(new window.Event('pointerup'));
    assert.equal(img.parentElement.tagName, 'P');
    assert.equal(body.querySelector('p').textContent, 'teks di sebelahnya');
});

test('dragging a figure reorders the picture and caption together, without changing text', () => {
    const { engine, body } = setup('<p id="before">Sebelum</p><figure><img src="image.png"><figcaption>Caption</figcaption></figure><p id="after">Sesudah</p>');
    const figure = body.querySelector('figure');
    const source = engine.getDraggableBlock(figure.querySelector('img'));
    assert.equal(source, figure);
    let saved = '';
    engine.getContent = () => body.innerHTML;
    engine.setContent = html => { saved = html; };
    engine.handleInput = () => {};
    engine.hideImageToolbar = () => {};
    engine.hideTableToolbar = () => {};
    assert.equal(engine.moveDocumentObject(source, { body, reference: body.querySelector('#before'), before: true }), true);
    assert.deepEqual(Array.from(body.children, el => el.tagName), ['FIGURE', 'P', 'P']);
    assert.equal(body.querySelector('figcaption').textContent, 'Caption');
    assert.equal(body.querySelectorAll('img').length, 1);
    assert.ok(saved.indexOf('<figure>') < saved.indexOf('Sebelum'));
    assert.equal((saved.match(/Sesudah/g) || []).length, 1);
});

test('table drops insert the whole grid between paragraphs, never inside a cell', () => {
    const { engine, body } = setup('<p id="first">Awal</p><table><tbody><tr><td>Baris A</td><td>Baris B</td></tr></tbody></table><p id="last">Akhir</p>');
    const table = body.querySelector('table');
    const last = body.querySelector('#last');
    document.elementFromPoint = () => last;
    last.getBoundingClientRect = () => ({ top: 100, height: 40 });
    const destination = engine.findDocumentDropTarget(20, 135, table);
    assert.equal(destination.reference, last);
    assert.equal(destination.before, false);
    engine.getContent = () => body.innerHTML;
    engine.setContent = () => {};
    engine.handleInput = () => {};
    engine.hideImageToolbar = () => {};
    engine.hideTableToolbar = () => {};
    assert.equal(engine.moveDocumentObject(table, destination), true);
    assert.equal(body.lastElementChild, table);
    assert.equal(table.rows[0].cells.length, 2);
    assert.equal(table.textContent, 'Baris ABaris B');
    document.elementFromPoint = () => table.rows[0].cells[0];
    assert.equal(engine.findDocumentDropTarget(20, 135, table), null);
});

test('dropping outside the printable body or onto itself is ignored', () => {
    const { engine, body } = setup('<p>Isi</p><table><tr><td>Data</td></tr></table>');
    const table = body.querySelector('table');
    document.elementFromPoint = () => table.rows[0].cells[0];
    assert.equal(engine.findDocumentDropTarget(520, 120, table), null);
    assert.equal(engine.findDocumentDropTarget(20, 120, table), null);
    document.elementFromPoint = () => document.body;
    assert.equal(engine.findDocumentDropTarget(20, 120, table), null);
});

test('moving a paginated table carries every fragment in row order with only one header', () => {
    const { engine, body } = setup('<p>Awal</p><table data-table-group="wordcraft-table-1"><thead><tr><th>Judul</th></tr></thead><tbody><tr><td>Baris 1</td></tr></tbody></table><p id="destination">Tujuan</p><table data-table-group="wordcraft-table-1"><thead><tr><th>Judul</th></tr></thead><tbody><tr><td>Baris 2</td></tr></tbody></table>');
    const fragments = body.querySelectorAll('table');
    engine.getContent = () => body.innerHTML;
    engine.setContent = () => {};
    engine.handleInput = () => {};
    engine.hideImageToolbar = () => {};
    engine.hideTableToolbar = () => {};
    assert.equal(engine.moveDocumentObject(fragments[1], { body, reference: body.querySelector('#destination'), before: false }), true);
    const table = body.querySelector('table');
    assert.equal(body.querySelectorAll('table').length, 1);
    assert.deepEqual(Array.from(table.tBodies[0].rows, row => row.textContent), ['Baris 1', 'Baris 2']);
    assert.equal(table.tHead.rows.length, 1);
    assert.equal(table.previousElementSibling.id, 'destination');
});
