<?php

test('black-box: BB08 accept and reject tracked changes via API', function () {
    $html = '<p>Naskah <span class="docx-revision" data-revision-id="r1" data-revision-type="insert">disetujui</span> dan <span class="docx-revision" data-revision-id="r2" data-revision-type="delete"><del>dihapus</del></span>.</p>';

    // 1. Accept insert
    $acceptRes = $this->postJson('/api/documents/review/accept', [
        'html' => $html,
        'revision_id' => 'r1',
    ]);

    $acceptRes->assertStatus(200)
        ->assertJson(['success' => true]);

    $afterAccept = $acceptRes->json('html');
    expect($afterAccept)->toContain('disetujui')
        ->and($afterAccept)->not->toContain('data-revision-id="r1"');

    // 2. Reject delete (restores text)
    $rejectRes = $this->postJson('/api/documents/review/reject', [
        'html' => $afterAccept,
        'revision_id' => 'r2',
    ]);

    $rejectRes->assertStatus(200)
        ->assertJson(['success' => true]);

    $finalHtml = $rejectRes->json('html');
    expect($finalHtml)->toContain('disetujui')
        ->and($finalHtml)->toContain('dihapus')
        ->and($finalHtml)->not->toContain('data-revision-id="r2"');
});

test('black-box: BB08 accept-all and reject-all batch operations', function () {
    $html = '<p><span class="docx-revision" data-revision-id="r1" data-revision-type="insert">A</span><span class="docx-revision" data-revision-id="r2" data-revision-type="delete"><del>B</del></span></p>';

    // Accept all
    $acceptAllRes = $this->postJson('/api/documents/review/accept-all', ['html' => $html]);
    $acceptAllRes->assertStatus(200);
    expect($acceptAllRes->json('html'))->toBe('<p>A</p>');

    // Reject all
    $rejectAllRes = $this->postJson('/api/documents/review/reject-all', ['html' => $html]);
    $rejectAllRes->assertStatus(200);
    expect($rejectAllRes->json('html'))->toBe('<p>B</p>');
});

test('black-box: compare documents generates semantic revision diff', function () {
    $orig = '<h1>Studi Lama</h1><p>Metodologi tradisional.</p>';
    $mod = '<h1>Studi Baru</h1><p>Metodologi modern cerdas.</p>';

    $response = $this->postJson('/api/documents/review/compare', [
        'original_html' => $orig,
        'modified_html' => $mod,
        'author' => 'Reviewer Ahli',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'author' => 'Reviewer Ahli',
        ]);

    $diffHtml = $response->json('diffHtml');
    expect($diffHtml)->toContain('data-revision-type="insert"')
        ->and($diffHtml)->toContain('data-revision-type="delete"');
});

test('black-box: BB09 review comments can be listed with anchors', function () {
    $html = '<p>Dokumen dengan <span class="comment" data-comment-id="c-99" data-comment-text="Perlu revisi paragraf" data-comment-author="Reviewer 1">kalimat utama</span> selesai.</p>';

    $response = $this->postJson('/api/documents/review/comments', [
        'html' => $html,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'count' => 1,
        ]);

    $comments = $response->json('comments');
    expect($comments[0]['id'])->toBe('c-99')
        ->and($comments[0]['anchorText'])->toBe('kalimat utama')
        ->and($comments[0]['text'])->toBe('Perlu revisi paragraf');
});
