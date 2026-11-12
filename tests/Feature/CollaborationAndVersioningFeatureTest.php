<?php

test('black-box: BB06 document versioning snapshot and non-destructive restore via API', function () {
    // 1. Create document
    $createRes = $this->postJson('/api/documents/server/create', [
        'title' => 'Naskah Kolaboratif 2026',
        'content_html' => '<p>Paragraf V1 Awal.</p>',
    ]);

    $createRes->assertStatus(201);
    $uuid = $createRes->json('document.uuid');
    expect($uuid)->not->toBeEmpty();

    // 2. Create version 2 snapshot
    $v2Res = $this->postJson("/api/documents/server/{$uuid}/versions", [
        'author' => 'Dr. Hadi',
        'summary' => 'Revisi pendahuluan',
        'content_html' => '<p>Paragraf V2 Revisi.</p>',
    ]);
    $v2Res->assertStatus(201);
    expect($v2Res->json('version.version_number'))->toBe(3);

    // 3. List versions
    $listRes = $this->getJson("/api/documents/server/{$uuid}/versions");
    $listRes->assertStatus(200)
        ->assertJson(['success' => true]);
    expect($listRes->json('count'))->toBeGreaterThanOrEqual(2);

    // 4. Restore version 2 (first manual snapshot)
    $restoreRes = $this->postJson("/api/documents/server/{$uuid}/versions/2/restore", [
        'author' => 'Admin Pemulihan',
    ]);

    $restoreRes->assertStatus(200);
    expect($restoreRes->json('restored_version.version_number'))->toBe(4)
        ->and($restoreRes->json('document.content_html'))->toBe('<p>Paragraf V1 Awal.</p>');
});

test('black-box: BB10 collaborative operational transaction updates document version deterministically', function () {
    // 1. Create document
    $createRes = $this->postJson('/api/documents/server/create', [
        'title' => 'Uji Operasi Kolaborasi',
        'content_html' => '<h1>Judul Awal</h1>',
    ]);
    $uuid = $createRes->json('document.uuid');

    // 2. Apply collaborative insert operation
    $insertOp = [
        'type' => 'insert_block',
        'index' => 1,
        'payload' => [
            'id' => 'blk-collab-1',
            'type' => 'paragraph',
            'runs' => [['text' => 'Paragraf ditambahkan oleh kolaborator']],
        ],
    ];

    $opRes = $this->postJson("/api/documents/server/{$uuid}/operations", [
        'operation' => $insertOp,
        'actor' => 'Kolaborator 1',
    ]);

    $opRes->assertStatus(200)
        ->assertJson(['success' => true]);

    expect($opRes->json('html'))->toContain('Paragraf ditambahkan oleh kolaborator');
});

test('black-box: BB10 presence heartbeat registers active collaborators', function () {
    $uuid = 'test-doc-presence-'.uniqid();

    $heartbeatRes = $this->postJson("/api/documents/server/{$uuid}/presence", [
        'user_id' => 'usr-101',
        'user_name' => 'Prof. Sartono',
        'cursor' => ['blockId' => 'blk-1', 'offset' => 45],
    ]);

    $heartbeatRes->assertStatus(200)
        ->assertJson(['success' => true]);

    $users = $heartbeatRes->json('active_users');
    expect($users)->toHaveCount(1)
        ->and($users[0]['name'])->toBe('Prof. Sartono');

    // Query active presence
    $getPresenceRes = $this->getJson("/api/documents/server/{$uuid}/presence");
    $getPresenceRes->assertStatus(200);
    expect($getPresenceRes->json('active_users'))->toHaveCount(1);
});

test('black-box: audit log events are recorded on document operations', function () {
    $createRes = $this->postJson('/api/documents/server/create', [
        'title' => 'Dokumen Audit',
        'content_html' => '<p>Audit test.</p>',
    ]);
    $uuid = $createRes->json('document.uuid');

    $auditRes = $this->getJson("/api/documents/server/{$uuid}/audit");
    $auditRes->assertStatus(200)
        ->assertJson(['success' => true]);

    expect($auditRes->json('count'))->toBeGreaterThanOrEqual(1)
        ->and($auditRes->json('events.0.event_type'))->toBe('version_created');
});
