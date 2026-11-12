<?php

use App\Services\DocumentModelService;

test('white-box: creates valid empty canonical document model v1', function () {
    $service = new DocumentModelService;
    $doc = $service->createEmptyDocument('Test Title', 'en-US');

    expect($doc)->toHaveKeys(['schemaVersion', 'id', 'title', 'metadata', 'pageSetup', 'styles', 'blocks'])
        ->and($doc['schemaVersion'])->toBe(1)
        ->and($doc['title'])->toBe('Test Title')
        ->and($doc['metadata']['language'])->toBe('en-US')
        ->and($doc['blocks'])->toBeArray()->not->toBeEmpty();

    $validation = $service->validateModel($doc);
    expect($validation['valid'])->toBeTrue()
        ->and($validation['errors'])->toBeEmpty();
});

test('white-box: htmlToModel converts headings, paragraphs, lists, tables, figures with stable IDs', function () {
    $service = new DocumentModelService;

    $html = <<<'HTML'
<h1 id="custom-h1">Bab 1 Pendahuluan</h1>
<p style="text-align: center;">Ini adalah pengantar dengan <strong>teks tebal</strong> dan <em>miring</em>.</p>
<ul id="custom-list">
    <li>Poin Satu</li>
    <li>Poin Dua</li>
</ul>
<table id="custom-tbl">
    <tr>
        <th>Kolom 1</th>
        <th>Kolom 2</th>
    </tr>
    <tr>
        <td>Data 1</td>
        <td>Data 2</td>
    </tr>
</table>
<figure id="custom-fig">
    <img src="test.png" alt="Deskripsi gambar" />
    <figcaption>Gambar 1. Ilustrasi</figcaption>
</figure>
<div class="page-break"></div>
<blockquote id="custom-quote"><p>Kutipan bijak</p></blockquote>
HTML;

    $model = $service->htmlToModel($html, 'Bab 1');

    expect($model['schemaVersion'])->toBe(1)
        ->and($model['title'])->toBe('Bab 1')
        ->and($model['blocks'])->toBeArray()->toHaveCount(7);

    // Verify block types and stable IDs
    $h1 = $model['blocks'][0];
    expect($h1['type'])->toBe('heading')
        ->and($h1['id'])->toBe('custom-h1')
        ->and($h1['level'])->toBe(1);

    $p = $model['blocks'][1];
    expect($p['type'])->toBe('paragraph')
        ->and($p['align'])->toBe('center')
        ->and($p['runs'])->toBeArray();

    $ul = $model['blocks'][2];
    expect($ul['type'])->toBe('list')
        ->and($ul['listType'])->toBe('bullet')
        ->and($ul['items'])->toHaveCount(2);

    $tbl = $model['blocks'][3];
    expect($tbl['type'])->toBe('table')
        ->and($tbl['rows'])->toHaveCount(2);

    $fig = $model['blocks'][4];
    expect($fig['type'])->toBe('figure')
        ->and($fig['caption'])->toBe('Gambar 1. Ilustrasi')
        ->and($fig['alt'])->toBe('Deskripsi gambar');

    $pb = $model['blocks'][5];
    expect($pb['type'])->toBe('break')
        ->and($pb['breakType'])->toBe('page');

    $q = $model['blocks'][6];
    expect($q['type'])->toBe('quote');

    // Model validation should pass
    $validation = $service->validateModel($model);
    expect($validation['valid'])->toBeTrue();
});

test('white-box: modelToHtml preserves structural tags and attributes', function () {
    $service = new DocumentModelService;

    $html = '<h1>Judul Naskah</h1><p>Paragraf dengan <strong>bold</strong> dan <a href="https://example.com">tautan</a>.</p>';
    $model = $service->htmlToModel($html, 'Judul Naskah');
    $rendered = $service->modelToHtml($model);

    expect($rendered)->toContain('<h1')
        ->and($rendered)->toContain('Judul Naskah')
        ->and($rendered)->toContain('<strong>bold</strong>')
        ->and($rendered)->toContain('<a href="https://example.com">tautan</a>');
});

test('white-box: migrateLegacyHtml returns validation report and rollback snapshot', function () {
    $service = new DocumentModelService;

    $legacyHtml = '<p>Dokumen versi lama yang perlu dimigrasi ke kanonikal skema v1.</p>';
    $migration = $service->migrateLegacyHtml($legacyHtml, 'Legacy Doc');

    expect($migration['success'])->toBeTrue()
        ->and($migration['schemaVersion'])->toBe(1)
        ->and($migration['model'])->toBeArray()
        ->and($migration['migratedHtml'])->toContain('Dokumen versi lama')
        ->and($migration['stats']['preservedRatio'])->toBeGreaterThanOrEqual(99.0)
        ->and($migration['rollback'])->toHaveKey('originalHash')
        ->and($migration['rollback'])->toHaveKey('originalHtml');
});

test('white-box: validateModel reports duplicate IDs and missing schema version', function () {
    $service = new DocumentModelService;

    $invalidModel = [
        'id' => 'doc-123',
        'blocks' => [
            ['id' => 'dup-1', 'type' => 'paragraph', 'runs' => []],
            ['id' => 'dup-1', 'type' => 'paragraph', 'runs' => []],
        ],
    ];

    $validation = $service->validateModel($invalidModel);
    expect($validation['valid'])->toBeFalse()
        ->and(count($validation['errors']))->toBeGreaterThanOrEqual(2);
});
