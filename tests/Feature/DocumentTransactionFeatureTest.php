<?php

test('black-box: api/documents/transactions/apply and invert enable reversible undo operations', function () {
    $model = [
        'schemaVersion' => 1,
        'id' => 'doc-trans-test',
        'metadata' => ['version' => 1],
        'blocks' => [
            ['id' => 'blk-1', 'type' => 'paragraph', 'runs' => [['text' => 'Hello']]],
        ],
    ];

    $insertOp = [
        'type' => 'insert_block',
        'index' => 1,
        'payload' => ['id' => 'blk-2', 'type' => 'paragraph', 'runs' => [['text' => 'World']]],
    ];

    // 1. Apply transaction
    $applyRes = $this->postJson('/api/documents/transactions/apply', [
        'model' => $model,
        'operation' => $insertOp,
    ]);

    $applyRes->assertStatus(200)
        ->assertJson(['success' => true]);

    $updatedModel = $applyRes->json('model');
    expect($updatedModel['blocks'])->toHaveCount(2);

    // 2. Invert transaction
    $invertRes = $this->postJson('/api/documents/transactions/invert', [
        'operation' => $insertOp,
    ]);

    $invertRes->assertStatus(200)
        ->assertJson([
            'success' => true,
            'inverse' => [
                'type' => 'delete_block',
                'targetId' => 'blk-2',
            ],
        ]);

    // 3. Re-apply inverted op (undo)
    $undoRes = $this->postJson('/api/documents/transactions/apply', [
        'model' => $updatedModel,
        'operation' => $invertRes->json('inverse'),
    ]);

    $undoRes->assertStatus(200);
    expect($undoRes->json('model.blocks'))->toHaveCount(1)
        ->and($undoRes->json('model.blocks.0.id'))->toBe('blk-1');
});

test('black-box: api/documents/search-replace performs safe replacement without attribute corruption', function () {
    $html = '<h2 class="sub-title">Penelitian Robotika</h2><p>Robotika adalah masa depan otomatisasi.</p>';

    $response = $this->postJson('/api/documents/search-replace', [
        'html' => $html,
        'find' => 'Robotika',
        'replace' => 'Kecerdasan Buatan',
        'options' => [
            'case_sensitive' => true,
            'whole_word' => true,
        ],
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'count' => 2,
        ]);

    $replaced = $response->json('html');
    expect($replaced)->toContain('Penelitian Kecerdasan Buatan')
        ->and($replaced)->toContain('Kecerdasan Buatan adalah masa depan');
});

test('black-box: api/documents/accessibility-audit evaluates document accessibility structure', function () {
    $accessibleHtml = '<h1>Judul Naskah</h1><p>Paragraf isi.</p><figure><img src="foto.jpg" alt="Foto kegiatan"/></figure>';

    $response = $this->postJson('/api/documents/accessibility-audit', [
        'html' => $accessibleHtml,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'passed' => true,
        ]);

    expect($response->json('score'))->toBeGreaterThanOrEqual(90);
});
