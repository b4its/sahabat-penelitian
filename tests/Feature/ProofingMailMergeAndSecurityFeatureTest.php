<?php

test('black-box: api/documents/proofing/check returns PUEBI corrections and readability metrics', function () {
    $response = $this->postJson('/api/documents/proofing/check', [
        'html' => '<p>Hasil analisa menunjukkan praktek yang efektifitas nya tinggi.</p>',
    ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true])
        ->assertJsonStructure([
            'success',
            'issuesCount',
            'suggestions',
            'readability' => ['readingEaseScore', 'readingLevel', 'wordCount'],
        ]);

    expect($response->json('issuesCount'))->toBeGreaterThanOrEqual(2);
});

test('black-box: api/documents/proofing/speech-segments formats clean sentences for Read Aloud (BB13)', function () {
    $response = $this->postJson('/api/documents/proofing/speech-segments', [
        'html' => '<h2>Judul</h2><p>Paragraf satu narasi. Paragraf dua narasi.</p>',
    ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $segments = $response->json('segments');
    expect($segments)->toHaveCount(2)
        ->and($segments[0]['text'])->toBe('JudulParagraf satu narasi.')
        ->or->and($segments[0]['text'])->toContain('Paragraf satu narasi.');
});

test('black-box: BB12 mail merge preview and CSV batch merge with isolated recipients', function () {
    $template = 'Halo {{nama}}, departemen Anda adalah {{departemen}}.';

    // 1. Live preview
    $previewRes = $this->postJson('/api/documents/mail-merge/preview', [
        'template' => $template,
        'record' => ['nama' => 'Irfan', 'departemen' => 'Riset'],
    ]);

    $previewRes->assertStatus(200);
    expect($previewRes->json('merged'))->toBe('Halo Irfan, departemen Anda adalah Riset.');

    // 2. Batch merge with CSV
    $csv = "nama,departemen\nAndi,Informatika\nCitra,Biomedis";
    $batchRes = $this->postJson('/api/documents/mail-merge/batch', [
        'template' => $template,
        'csv' => $csv,
    ]);

    $batchRes->assertStatus(200)
        ->assertJson([
            'success' => true,
            'totalRecords' => 2,
        ]);

    $results = $batchRes->json('results');
    expect($results[0]['content'])->toContain('Andi', 'Informatika')
        ->and($results[0]['content'])->not->toContain('Citra', 'Biomedis')
        ->and($results[1]['content'])->toContain('Citra', 'Biomedis')
        ->and($results[1]['content'])->not->toContain('Andi', 'Informatika');
});

test('black-box: BB15 document password protection and restriction verify', function () {
    $createRes = $this->postJson('/api/documents/server/create', [
        'title' => 'Naskah Terproteksi',
        'content_html' => '<p>Konten sangat rahasia.</p>',
    ]);
    $uuid = $createRes->json('document.uuid');

    // Set protection
    $protectRes = $this->postJson("/api/documents/server/{$uuid}/protection", [
        'mode' => 'read_only',
        'password' => 'kunciRahasia2026',
    ]);

    $protectRes->assertStatus(200)
        ->assertJson([
            'success' => true,
            'restricted_mode' => 'read_only',
            'is_protected' => true,
        ]);

    // Verify invalid password
    $invalidRes = $this->postJson("/api/documents/server/{$uuid}/protection/verify", [
        'password' => 'passwordsalah',
    ]);
    $invalidRes->assertStatus(403)
        ->assertJson(['success' => false]);

    // Verify correct password
    $validRes = $this->postJson("/api/documents/server/{$uuid}/protection/verify", [
        'password' => 'kunciRahasia2026',
    ]);
    $validRes->assertStatus(200)
        ->assertJson(['success' => true]);
});
