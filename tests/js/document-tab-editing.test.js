import test from 'node:test';
import assert from 'node:assert/strict';
import { JSDOM } from 'jsdom';
import { DocumentEngine } from '../../resources/js/editor/document-engine.js';

function editor(html, selector = 'p', offset = 0) {
    const dom = new JSDOM(`<div class="page-sheet-body" contenteditable="true">${html}</div>`);
    globalThis.window = dom.window;
    globalThis.document = dom.window.document;
    globalThis.Node = dom.window.Node;
    const engine = Object.create(DocumentEngine.prototype);
    engine.tabStops = [{ pos: 2, align: 'right', leader: 'dots' }, { pos: 4, align: 'left', leader: 'none' }];
    engine.defaultTabStop = 1.27;
    let saves = 0;
    engine.handleInput = () => { saves++; };
    const body = document.querySelector('.page-sheet-body');
    const node = body.querySelector(selector).firstChild;
    const range = document.createRange();
    range.setStart(node, offset);
    range.collapse(true);
    const selection = window.getSelection();
    selection.removeAllRanges();
    selection.addRange(range);
    return { engine, body, selection, saves: () => saves };
}

function key(engine, key, shiftKey = false) {
    let prevented = false;
    engine.handleKeydown({ key, shiftKey, preventDefault() { prevented = true; } });
    return prevented;
}

test('Tab inserts an inline stop at the caret without indenting preceding text', () => {
    const { engine, body, saves } = editor('<p>AwalAkhir</p>', 'p', 4);
    assert.equal(key(engine, 'Tab'), true);
    const paragraph = body.querySelector('p');
    const tab = paragraph.querySelector('.document-tab');
    assert.equal(paragraph.firstChild.textContent, 'Awal');
    assert.equal(paragraph.lastChild.textContent, 'Akhir');
    assert.equal(tab.dataset.tabPosition, '2');
    assert.equal(tab.dataset.tabAlign, 'right');
    assert.equal(tab.dataset.tabLeader, 'dots');
    assert.equal(paragraph.style.marginLeft, '');
    assert.equal(saves(), 1);
});

test('consecutive tabs use the next configured stop and then default spacing', () => {
    const { engine, body } = editor('<p>Isi</p>', 'p', 3);
    key(engine, 'Tab');
    // jsdom does not calculate caret geometry; simulate the cursor after the first stop.
    const range = window.getSelection().getRangeAt(0);
    range.getBoundingClientRect = () => ({ left: 110, height: 16 });
    body.querySelector('p').getBoundingClientRect = () => ({ left: 0 });
    key(engine, 'Tab');
    assert.deepEqual(Array.from(body.querySelectorAll('.document-tab'), tab => tab.dataset.tabPosition), ['2', '4']);
    assert.equal(engine.getTabStopAt(260).align, 'left');
});

test('Enter after a tab starts a normal paragraph and preserves text after the cursor', () => {
    const { engine, body, selection } = editor('<p class="indent-8" style="margin-left: 2cm; text-indent: 1cm">AwalLanjutan</p>', 'p', 4);
    key(engine, 'Tab');
    assert.equal(key(engine, 'Enter'), true);
    const paragraphs = body.querySelectorAll('p');
    assert.equal(paragraphs.length, 2);
    assert.equal(paragraphs[0].firstChild.textContent, 'Awal');
    assert.equal(paragraphs[0].querySelectorAll('.document-tab').length, 1);
    assert.equal(paragraphs[1].textContent, 'Lanjutan');
    assert.equal(paragraphs[1].style.marginLeft, '');
    assert.equal(paragraphs[1].style.textIndent, '');
    assert.equal(paragraphs[1].classList.contains('indent-8'), false);
    assert.equal(selection.anchorNode.parentElement?.closest('p') || selection.anchorNode, paragraphs[1]);
});

test('Enter at the end of tabbed text leaves an editable empty paragraph', () => {
    const { engine, body, selection } = editor('<p>Judul</p>', 'p', 5);
    key(engine, 'Tab');
    assert.equal(key(engine, 'Enter'), true);
    const next = body.querySelectorAll('p')[1];
    assert.equal(next.innerHTML, '<br>');
    assert.equal(next.querySelector('.document-tab'), null);
    assert.equal(selection.anchorNode, next);
    assert.equal(selection.anchorOffset, 0);
});

test('Shift+Tab removes only the previous tab, not the paragraph formatting', () => {
    const { engine, body } = editor('<p>Awal</p>', 'p', 4);
    key(engine, 'Tab');
    key(engine, 'Tab', true);
    assert.equal(body.querySelector('.document-tab'), null);
    assert.equal(body.querySelector('p').textContent, 'Awal');
});

test('Tab inside a table navigates cells, Shift+Tab goes backwards, last cell creates a row', () => {
    const { engine, body, selection } = editor('<table><tr><td>One</td><td>Two</td></tr></table>', 'td', 0);
    key(engine, 'Tab');
    assert.equal(selection.anchorNode.parentElement?.closest('td') || selection.anchorNode, body.querySelectorAll('td')[1]);
    key(engine, 'Tab', true);
    assert.equal(selection.anchorNode.parentElement?.closest('td') || selection.anchorNode, body.querySelectorAll('td')[0]);
    const range = document.createRange();
    range.selectNodeContents(body.querySelectorAll('td')[1]);
    range.collapse(true);
    selection.removeAllRanges();
    selection.addRange(range);
    key(engine, 'Tab');
    assert.equal(body.querySelectorAll('tr').length, 2);
    assert.equal(body.querySelectorAll('td').length, 4);
    assert.equal(body.querySelectorAll('.document-tab').length, 0);
});
