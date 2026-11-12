<?php

test('black-box: api/documents/model/parse converts valid HTML into canonical schema v1', function () {
    $response = $this->postJson('/api/documents/model/parse', [
        'title' => 'Riset 2026',
        'html' => '<h1>Pendahuluan</h1><p>Penjelasan sistematis penelitian.</p>',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'schemaVersion' => 1,
        ])
        ->assertJsonStructure([
            'success',
            'schemaVersion',
            'model' => [
                'id',
                'title',
                'metadata',
                'blocks',
            ],
        ]);

    $blocks = $response->json('model.blocks');
    expect($blocks)->toHaveCount(2)
        ->and($blocks[0]['type'])->toBe('heading')
        ->and($blocks[1]['type'])->toBe('paragraph');
});

test('black-box: api/documents/model/render validates schema and converts model back to HTML', function () {
    $model = [
        'schemaVersion' => 1,
        'id' => 'doc-uuid-1234',
        'title' => 'Test',
        'metadata' => [],
        'blocks' => [
            [
                'id' => 'blk-1',
                'type' => 'heading',
                'level' => 2,
                'runs' => [['text' => 'Sub Judul']],
            ],
            [
                'id' => 'blk-2',
                'type' => 'paragraph',
                'runs' => [['text' => 'Isi teks dengan ', 'bold' => false], ['text' => 'tebal', 'bold' => true]],
            ],
        ],
    ];

    $response = $this->postJson('/api/documents/model/render', [
        'model' => $model,
    ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true])
        ->assertJsonStructure(['success', 'html']);

    $html = $response->json('html');
    expect($html)->toContain('<h2 id="blk-1">Sub Judul</h2>')
        ->and($html)->toContain('<strong>tebal</strong>');
});

test('black-box: api/documents/model/render rejects malformed schema with 422', function () {
    $invalidModel = [
        'schemaVersion' => 999, // invalid version
        'id' => '',
    ];

    $response = $this->postJson('/api/documents/model/render', [
        'model' => $invalidModel,
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'Model dokumen tidak valid.',
        ]);
});

test('black-box: api/documents/model/migrate performs safe legacy migration and provides rollback', function () {
    $legacyHtml = '<h2>Warisan Dokumen</h2><p>Teks lama tanpa ID stabil.</p>';

    $response = $this->postJson('/api/documents/model/migrate', [
        'html' => $legacyHtml,
        'title' => 'Dokumen Migrasi Lama',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'schemaVersion' => 1,
        ])
        ->assertJsonStructure([
            'success',
            'schemaVersion',
            'documentId',
            'model',
            'migratedHtml',
            'validation' => ['valid'],
            'stats' => ['originalWords', 'migratedWords', 'preservedRatio'],
            'rollback' => ['originalHash', 'originalHtml', 'migratedAt'],
        ]);

    expect($response->json('stats.preservedRatio'))->toBeGreaterThanOrEqual(99.0);
});
