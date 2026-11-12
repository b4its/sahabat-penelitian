import test from 'node:test';
import assert from 'node:assert/strict';
import { JSDOM } from 'jsdom';
import { DocumentEngine } from '../../resources/js/editor/document-engine.js';

function paginate(html) {
    const dom = new JSDOM('<!doctype html><html><body></body></html>');
    globalThis.DOMParser = dom.window.DOMParser;
    globalThis.document = dom.window.document;
    const engine = Object.create(DocumentEngine.prototype);
    engine.formatLatexEquationsInHtml = value => value;
    return engine.paginateHtml(html);
}

test('wide local table retains every row, repeated headings and column proportions across sheets', () => {
    const columns = Array.from({ length: 12 }, (_, index) => `<col style="width: ${index ? '8' : '12'}%">`).join('');
    const headings = Array.from({ length: 12 }, (_, index) => `<th>Bulan ${index}</th>`).join('');
    const rows = Array.from({ length: 25 }, (_, index) =>
        `<tr><td>Baris ${index + 1}</td>${'<td>kegiatan penelitian</td>'.repeat(11)}</tr>`).join('');
    const pages = paginate(`<p>Jadwal penelitian</p><table><colgroup>${columns}</colgroup><thead><tr>${headings}</tr></thead><tbody>${rows}</tbody></table><p>Setelah tabel</p>`);

    assert.ok(pages.length > 1);
    const tables = pages.flatMap(page => Array.from(new JSDOM(page.contentHtml).window.document.querySelectorAll('table')));
    assert.ok(tables.length > 1);
    assert.equal(tables.reduce((count, table) => count + table.tBodies[0].rows.length, 0), 25);
    assert.deepEqual(tables.flatMap(table => Array.from(table.tBodies[0].rows, row => row.cells[0].textContent)),
        Array.from({ length: 25 }, (_, index) => `Baris ${index + 1}`));
    for (const table of tables) {
        assert.ok(table.classList.contains('wordcraft-wide-table'));
        assert.equal(table.querySelectorAll('col').length, 12);
        assert.equal(table.tHead.rows.length, 1);
    }
    assert.ok(pages.at(-1).contentHtml.includes('Setelah tabel'));
});

test('ordinary and row-spanning tables keep their content without duplicating cells', () => {
    const pages = paginate('<table><tbody><tr><td rowspan="2">Gabung</td><td>A</td></tr><tr><td>B</td></tr></tbody></table>');
    assert.equal(pages.length, 1);
    assert.equal((pages[0].contentHtml.match(/Gabung/g) || []).length, 1);
    assert.ok(pages[0].contentHtml.includes('rowspan="2"'));
    assert.ok(!pages[0].contentHtml.includes('wordcraft-wide-table'));
});

test('table footers remain footers when a table is too complex to split', () => {
    const pages = paginate('<table><thead><tr><th>Judul</th></tr></thead><tbody><tr><td>Isi</td></tr></tbody><tfoot><tr><td>Jumlah</td></tr></tfoot></table>');
    assert.equal(pages.length, 1);
    const table = new JSDOM(pages[0].contentHtml).window.document.querySelector('table');
    assert.equal(table.tFoot.textContent, 'Jumlah');
});
