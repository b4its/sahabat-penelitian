import test from 'node:test';
import assert from 'node:assert/strict';
import { JSDOM } from 'jsdom';
import { DocumentEngine } from '../../resources/js/editor/document-engine.js';

function editor() {
    const engine = Object.create(DocumentEngine.prototype);
    let content = '<p>Awal</p>';
    let saved = '';
    engine.history = [content];
    engine.historyIndex = 0;
    engine.maxHistory = 50;
    engine.isTyping = false;
    engine.getContent = () => content;
    engine.setContent = (html, record, options) => {
        content = html;
        options.onComplete?.();
    };
    engine.getActiveEditor = () => ({ focus() {} });
    engine.saveToLocalStorage = () => { saved = content; };
    engine.onSaveStateChange = () => {};
    return { engine, type: html => { content = html; engine.isTyping = true; }, content: () => content, saved: () => saved };
}

test('undo before debounce expires restores only the most recent edit and cancels its timer', () => {
    const { engine, type, content, saved } = editor();
    type('<p>Awal baru</p>');
    engine.autoSaveTimer = setTimeout(() => { throw new Error('stale autosave'); }, 1000);
    engine.undo();
    assert.equal(content(), '<p>Awal</p>');
    assert.equal(saved(), '<p>Awal</p>');
    assert.deepEqual(engine.history, ['<p>Awal</p>', '<p>Awal baru</p>']);
    assert.equal(engine.historyIndex, 0);
    assert.equal(engine.autoSaveTimer, null);
    engine.redo();
    assert.equal(content(), '<p>Awal baru</p>');
    assert.equal(saved(), '<p>Awal baru</p>');
});

test('typing after undo invalidates the redo branch, not the new text', () => {
    const { engine, type, content } = editor();
    type('<p>Versi lama</p>');
    engine.recordHistory();
    engine.undo();
    type('<p>Versi baru</p>');
    engine.redo();
    assert.equal(content(), '<p>Versi baru</p>');
    assert.deepEqual(engine.history, ['<p>Awal</p>', '<p>Versi baru</p>']);
});

test('redo survives harmless pagination markup changes after undo', () => {
    const { engine, type, content } = editor();
    type('<p>Awal</p><div class="page-break"></div><p>Halaman dua</p>');
    engine.recordHistory();
    engine.undo();
    const originalHistory = [...engine.history];
    engine.getContent = () => content() + '<!-- rendered page marker -->';

    engine.redo();

    assert.equal(engine.historyIndex, 1);
    assert.deepEqual(engine.history, originalHistory);
    assert.match(content(), /Halaman dua/);
});

test('document keyboard shortcuts share undo/redo history with toolbar actions', () => {
    const { engine, type, content } = editor();
    type('<p>Baru</p>');
    let prevented = 0;
    const press = (key, shiftKey = false, metaKey = false) => engine.handleKeydown({
        key, ctrlKey: !metaKey, metaKey, shiftKey,
        preventDefault() { prevented++; },
    });
    press('z');
    assert.equal(content(), '<p>Awal</p>');
    press('z', true);
    assert.equal(content(), '<p>Baru</p>');
    press('z', false, true);
    press('y');
    assert.equal(content(), '<p>Baru</p>');
    assert.equal(prevented, 4);
});

test('undo leaves the caret near the edited text so typing can continue', () => {
    const dom = new JSDOM('<div class="page-sheet-body" contenteditable="true"><p>Awal baru</p></div>');
    globalThis.window = dom.window;
    globalThis.document = dom.window.document;
    const body = document.querySelector('.page-sheet-body');
    body.focus = () => {};
    const engine = Object.create(DocumentEngine.prototype);
    engine.history = ['<p>Awal</p>', '<p>Awal baru</p>'];
    engine.historyIndex = 1;
    engine.maxHistory = 50;
    engine.isTyping = true;
    engine.getAllEditorBodies = () => [body];
    engine.getActiveEditor = () => body;
    engine.getContent = () => body.innerHTML;
    engine.setContent = (html, record, options) => { body.innerHTML = html; options.onComplete?.(); };
    engine.saveToLocalStorage = () => {};
    engine.onSaveStateChange = () => {};
    const range = document.createRange();
    range.setStart(body.querySelector('p').firstChild, 9);
    range.collapse(true);
    window.getSelection().addRange(range);

    engine.undo();

    assert.equal(body.textContent, 'Awal');
    assert.equal(window.getSelection().anchorNode.textContent, 'Awal');
    assert.equal(window.getSelection().anchorOffset, 4);
    delete globalThis.window;
    delete globalThis.document;
});
