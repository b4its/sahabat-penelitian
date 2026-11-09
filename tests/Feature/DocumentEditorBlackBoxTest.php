<?php

use Illuminate\Http\UploadedFile;

/*
|--------------------------------------------------------------------------
| Black Box Feature Tests: VelScribe Wordcraft Document Editor
|--------------------------------------------------------------------------
| Testing user-facing endpoints, views, file upload/import, export,
| Wordcraft AI operations, status codes, and security boundaries.
*/

test('black-box: document editor view loads successfully at root and /editor routes', function () {
    $responseRoot = $this->get('/');
    $responseRoot->assertStatus(200);
    $responseRoot->assertSee('VelScribe', false);
    $responseRoot->assertSee('Wordcraft', false);
    $responseRoot->assertSee('document-editor', false);
    $responseRoot->assertSee('document-page-canvas', false);
    $responseRoot->assertSee('outline-sidebar', false);
    $responseRoot->assertSee('wordcraft-sidebar', false);
    $responseRoot->assertSee('ribbon-bar', false);

    $responseEditor = $this->get('/editor');
    $responseEditor->assertStatus(200);
    $responseEditor->assertSee('document-editor', false);
});

test('black-box: document editor view renders all ribbon tabs and interactive tools', function () {
    $response = $this->get('/');
    $response->assertStatus(200);

    // Ribbon tabs
    $response->assertSee('Beranda (Home)');
    $response->assertSee('Sisipkan (Insert)');
    $response->assertSee('Tata Letak (Layout)');
    $response->assertSee('Tampilan (View)');
    $response->assertSee('Asisten AI Wordcraft');

    // Modals
    $response->assertSee('modal-open-file', false);
    $response->assertSee('modal-find-replace', false);
    $response->assertSee('modal-insert-link', false);
    $response->assertSee('modal-insert-table', false);
    $response->assertSee('modal-insert-image', false);
    $response->assertSee('modal-export', false);
});

test('black-box: user can import a local markdown document and receive adapted format', function () {
    $markdownContent = "# Laporan Hasil Riset 2026\n\nPenelitian ini menguji **adaptasi format** otomatis.\n\n- Poin Pertama\n- Poin Kedua";
    $file = UploadedFile::fake()->createWithContent('riset_2026.md', $markdownContent);

    $response = $this->postJson('/api/documents/import', [
        'file' => $file,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'format' => 'markdown',
            'filename' => 'riset_2026.md',
        ])
        ->assertJsonStructure([
            'success',
            'title',
            'filename',
            'format',
            'content_html',
            'stats' => ['word_count', 'char_count', 'reading_time'],
            'adapted_settings',
            'message',
        ]);

    $html = $response->json('content_html');
    expect($html)->toContain('<h1>Laporan Hasil Riset 2026</h1>')
        ->and($html)->toContain('<strong>adaptasi format</strong>')
        ->and($html)->toContain('<li>Poin Pertama</li>');
});

test('black-box: user can import a local html document with sanitization', function () {
    $htmlContent = '<h1>Studi Kasus</h1><p>Paragraf penjelasan.</p><script>alert("hack")</script>';
    $file = UploadedFile::fake()->createWithContent('studi.html', $htmlContent);

    $response = $this->postJson('/api/documents/import', [
        'file' => $file,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'format' => 'html',
        ]);

    $sanitized = $response->json('content_html');
    expect($sanitized)->toContain('<h1>Studi Kasus</h1>')
        ->and($sanitized)->not->toContain('<script>')
        ->and($sanitized)->not->toContain('alert(');
});

test('black-box: user can import a local plain text file and paragraphs are formatted', function () {
    $textContent = "Paragraf pembuka teks.\n\nParagraf kedua naskah riset.";
    $file = UploadedFile::fake()->createWithContent('catatan.txt', $textContent);

    $response = $this->postJson('/api/documents/import', [
        'file' => $file,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'format' => 'txt',
        ]);

    $html = $response->json('content_html');
    expect($html)->toContain('<p>Paragraf pembuka teks.</p>')
        ->and($html)->toContain('<p>Paragraf kedua naskah riset.</p>');
});

test('black-box: user can import a local json backup file and restore document state', function () {
    $jsonContent = json_encode([
        'title' => 'Naskah Proyek JSON',
        'content' => '<h1>Judul Naskah JSON</h1><p>Konten tersimpan.</p>',
    ]);
    $file = UploadedFile::fake()->createWithContent('backup.json', $jsonContent);

    $response = $this->postJson('/api/documents/import', [
        'file' => $file,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'format' => 'json',
            'title' => 'Judul Naskah JSON',
        ]);
});

test('black-box: user can import raw pasted content with filename', function () {
    $response = $this->postJson('/api/documents/import', [
        'raw_content' => "# Judul Paste\n\nIsi teks paste.",
        'filename' => 'dokumen_paste.md',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'format' => 'markdown',
            'title' => 'Judul Paste',
        ]);
});

test('black-box: import validates empty requests and returns 422', function () {
    $response = $this->postJson('/api/documents/import', []);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
        ]);
});

test('black-box: user can export document in multiple formats', function () {
    $payload = [
        'title' => 'Uji Coba Ekspor',
        'content' => '<h1>Bab 1</h1><p>Uji ekspor konten.</p>',
    ];

    // Export as Word (.docx / .doc)
    $resDocx = $this->postJson('/api/documents/export', array_merge($payload, ['format' => 'docx']));
    $resDocx->assertStatus(200);
    expect($resDocx->headers->get('Content-Type'))->toContain('application/msword')
        ->and($resDocx->headers->get('Content-Disposition'))->toContain('attachment');

    // Export as Markdown (.md)
    $resMd = $this->postJson('/api/documents/export', array_merge($payload, ['format' => 'markdown']));
    $resMd->assertStatus(200);
    expect($resMd->headers->get('Content-Type'))->toContain('text/markdown')
        ->and($resMd->getContent())->toContain('# Uji Coba Ekspor');

    // Export as HTML (.html)
    $resHtml = $this->postJson('/api/documents/export', array_merge($payload, ['format' => 'html']));
    $resHtml->assertStatus(200);
    expect($resHtml->headers->get('Content-Type'))->toContain('text/html')
        ->and($resHtml->getContent())->toContain('<!DOCTYPE html>');

    // Export as Plain Text (.txt)
    $resTxt = $this->postJson('/api/documents/export', array_merge($payload, ['format' => 'txt']));
    $resTxt->assertStatus(200);
    expect($resTxt->headers->get('Content-Type'))->toContain('text/plain')
        ->and($resTxt->getContent())->toContain('Uji Coba Ekspor');

    // Export as JSON (.json)
    $resJson = $this->postJson('/api/documents/export', array_merge($payload, ['format' => 'json']));
    $resJson->assertStatus(200);
    expect($resJson->headers->get('Content-Type'))->toContain('application/json');
    $decoded = json_decode($resJson->getContent(), true);
    expect($decoded)->toHaveKeys(['title', 'content', 'stats', 'exported_at']);
});

test('black-box: export validates input and rejects unsupported format', function () {
    $response = $this->postJson('/api/documents/export', [
        'title' => 'Dokumen',
        'content' => '<p>Konten</p>',
        'format' => 'exe',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['format']);
});

test('black-box: wordcraft ai assistant handles continue, elaborate, rewrite, grammar, summarize, brainstorm, and chat actions', function () {
    $actions = [
        ['action' => 'continue', 'selection' => 'Pendahuluan riset menunjukkan'],
        ['action' => 'elaborate', 'selection' => 'Peningkatan performa operasional'],
        ['action' => 'rewrite', 'selection' => 'Kami sangat yakin bahwa ini hal yang amat bagus.', 'style' => 'formal'],
        ['action' => 'grammar', 'selection' => 'jadual aktifitas dimana merubah sistem'],
        ['action' => 'summarize', 'selection' => 'Dokumen panjang tentang evaluasi sistem penulisan komprehensif.'],
        ['action' => 'brainstorm', 'prompt' => 'Ide bab selanjutnya untuk naskah'],
        ['action' => 'chat', 'prompt' => 'Apa simpulan dokumen ini?'],
    ];

    foreach ($actions as $act) {
        $response = $this->postJson('/api/documents/ai-assistant', $act);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'action' => $act['action'],
            ])
            ->assertJsonStructure([
                'success',
                'action',
                'title',
                'options',
            ]);

        expect(count($response->json('options')))->toBeGreaterThan(0);
    }
});

test('black-box: wordcraft ai assistant rejects invalid action with 422', function () {
    $response = $this->postJson('/api/documents/ai-assistant', [
        'action' => 'unsupported_action',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['action']);
});
