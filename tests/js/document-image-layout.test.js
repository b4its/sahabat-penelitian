import test from 'node:test';
import assert from 'node:assert/strict';
import { JSDOM } from 'jsdom';
import { DocumentEngine } from '../../resources/js/editor/document-engine.js';

function editor(html) {
    const dom = new JSDOM(`<div class="page-sheet-body" id="document-editor">${html}</div>`);
    globalThis.document = dom.window.document;
    const engine = Object.create(DocumentEngine.prototype);
    let saved = 0;
    engine.handleInput = () => { saved++; };
    engine.activeImage = document.querySelector('img');
    const body = document.querySelector('.page-sheet-body');
    Object.defineProperties(body, { clientWidth: { value: 500 }, clientHeight: { value: 750 } });
    return { engine, body, saved: () => saved };
}

test('inserting a picture gives it independent controls and leaves its caption editable', () => {
    const { engine, body } = editor('<p>Awal</p>');
    globalThis.window = { getSelection: () => ({ rangeCount: 0 }) };
    engine.getActiveEditor = () => body;
    engine.insertImage('data:image/png;base64,abc', 'Gambar 1');
    const figure = body.querySelector('figure');
    assert.equal(figure.querySelector('.wordcraft-image-frame').dataset.imageAlign, 'center');
    assert.equal(figure.querySelector('figcaption').textContent, 'Gambar 1');
    engine.activeImage = figure.querySelector('img');
    engine.setImageAlignment('right');
    assert.equal(figure.querySelector('.wordcraft-image-frame').dataset.imageAlign, 'right');
    assert.equal(figure.querySelector('figcaption').textContent, 'Gambar 1');
});

test('aligning an imported image changes only its frame, not the caption or paragraph', () => {
    const { engine, body, saved } = editor('<figure class="text-center"><img src="image.png"><figcaption class="text-center">Keterangan tetap</figcaption></figure><p>Paragraf tetap</p>');
    for (const align of ['left', 'center', 'right']) {
        engine.setImageAlignment(align);
        assert.equal(body.querySelector('.wordcraft-image-frame').dataset.imageAlign, align);
        assert.equal(body.querySelector('figure').className, 'text-center');
        assert.equal(body.querySelector('figcaption').className, 'text-center');
        assert.equal(body.querySelector('p').textContent, 'Paragraf tetap');
    }
    assert.equal(saved(), 3);
});

test('wrap modes can be switched back to normal without losing image content', () => {
    const { engine, body } = editor('<p><img src="image.png" alt="Foto"></p><p>Isi</p>');
    engine.setImageLayout('wrap-left');
    assert.equal(body.querySelector('.wordcraft-image-frame').dataset.imageMode, 'wrap-left');
    engine.setImageAlignment('right');
    assert.equal(body.querySelector('.wordcraft-image-frame').dataset.imageMode, 'wrap-right');
    engine.setImageLayout('top-bottom');
    assert.equal(body.querySelector('.wordcraft-image-frame').dataset.imageMode, 'top-bottom');
    engine.setImageLayout('inline');
    assert.equal(body.querySelector('img').getAttribute('src'), 'image.png');
    assert.equal(body.querySelectorAll('img').length, 1);
    assert.equal(body.querySelector('p:last-child').textContent, 'Isi');
});

test('floating image positions are clamped to the page and survive serialization', () => {
    const { engine, body } = editor('<figure><img src="image.png"><figcaption>Caption</figcaption></figure>');
    const frame = engine.getImageFrame();
    Object.defineProperties(frame, { offsetWidth: { value: 120 }, offsetHeight: { value: 90 } });
    engine.setImageLayout('floating');
    engine.positionImage(frame, 999, -20);
    assert.equal(frame.style.left, '380px');
    assert.equal(frame.style.top, '0px');
    const saved = body.innerHTML;
    assert.ok(saved.includes('data-image-mode="floating"'));
    assert.ok(saved.includes('left: 380px'));
    assert.equal(body.querySelector('figcaption').textContent, 'Caption');
    engine.setImageLayout('inline');
    assert.equal(frame.style.left, '');
    assert.equal(frame.style.top, '');
    engine.setImageLayout('invalid');
    assert.equal(frame.dataset.imageMode, 'inline');
});
