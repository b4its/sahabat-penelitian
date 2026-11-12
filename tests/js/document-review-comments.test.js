import test from 'node:test';
import assert from 'node:assert/strict';
import { JSDOM } from 'jsdom';
import { ReviewComments } from '../../resources/js/editor/review-comments.js';

function setup(html = '<p id="one">Awal akhir</p>') {
    const dom = new JSDOM(`<div class="page-sheet-body" contenteditable="true">${html}</div><div id="review-comments-list"></div>`);
    globalThis.window = dom.window;
    globalThis.document = dom.window.document;
    const body = document.querySelector('.page-sheet-body');
    let changes = 0;
    const review = new ReviewComments({ getAllEditorBodies: () => [body], handleInput: () => { changes++; } });
    const range = document.createRange();
    range.setStart(body.querySelector('#one').firstChild, 0);
    range.setEnd(body.querySelector('#one').firstChild, 4);
    return { body, review, range, changes: () => changes };
}

test('comment anchors selected text without changing the rest of the paragraph and survives HTML reload', () => {
    const { body, review, range, changes } = setup();
    assert.equal(review.addComment(range, 'Perjelas bagian ini'), true);
    assert.equal(body.textContent, 'Awal akhir');
    assert.equal(changes(), 1);
    const html = body.innerHTML;
    body.innerHTML = html;
    review.render();
    assert.equal(review.anchors[0].textContent, 'Awal');
    assert.equal(review.anchors[0].dataset.commentText, 'Perjelas bagian ini');
    assert.match(document.getElementById('review-comments-list').textContent, /Perjelas bagian ini/);
});

test('resolved comments can reopen and deletion preserves selected text', () => {
    const { body, review, range } = setup();
    review.addComment(range, 'Ulas');
    const id = review.anchors[0].dataset.commentId;
    assert.equal(review.setResolved(id, true), true);
    assert.equal(review.anchors[0].dataset.commentResolved, 'true');
    assert.equal(review.setResolved(id, false), true);
    assert.equal(review.anchors[0].dataset.commentResolved, 'false');
    assert.equal(review.deleteComment(id), true);
    assert.equal(body.textContent, 'Awal akhir');
    assert.equal(review.anchors.length, 0);
});

test('comment text is rendered as text, never executable markup', () => {
    const { review, range } = setup();
    const malicious = '<img src=x onerror=alert(1)>';
    review.addComment(range, malicious);
    assert.equal(document.querySelector('#review-comments-list img'), null);
    assert.equal(review.anchors[0].dataset.commentText, malicious);
    assert.match(document.querySelector('#review-comments-list').textContent, /onerror/);
});

test('empty, oversized and cross-paragraph selections cannot create a comment', () => {
    const { body, review, range, changes } = setup('<p id="one">Awal</p><p id="two">Akhir</p>');
    assert.equal(review.addComment(range, ' '), false);
    assert.equal(review.addComment(range, 'x'.repeat(2001)), false);
    range.setEnd(body.querySelector('#two').firstChild, 3);
    assert.equal(review.addComment(range, 'Antar paragraf'), false);
    assert.equal(review.anchors.length, 0);
    assert.equal(changes(), 0);
});

test('stale selections from a repaginated page cannot create orphaned comments', () => {
    const { body, review, range, changes } = setup();
    body.remove();
    assert.equal(review.addComment(range, 'Komentar usang'), false);
    assert.equal(changes(), 0);
});
