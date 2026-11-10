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
    $response->assertSee('modal-stats', false);
    $response->assertSee('modal-shortcuts', false);

    // Floating Context Toolbars
    $response->assertSee('floating-table-toolbar', false);
    $response->assertSee('floating-image-toolbar', false);
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
    expect($html)->toMatch('/<h1[^>]*>Laporan Hasil Riset 2026<\/h1>/')
        ->and($html)->toContain('Poin Pertama');
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
    expect($sanitized)->toMatch('/<h1[^>]*>Studi Kasus<\/h1>/')
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
    expect($html)->toMatch('/<p[^>]*>Paragraf pembuka teks\.<\/p>/')
        ->and($html)->toMatch('/<p[^>]*>Paragraf kedua naskah riset\.<\/p>/');
});

test('black-box: user can import a local skripsi document and academic presets are applied', function () {
    $skripsiContent = <<<'HTML'
        <p>SKRIPSI</p>
        <p>PENERAPAN ALGORITMA SISTEM MONITORING</p>
        <p>Oleh : BAITS RIKA SAPUTRA</p>
        <p>NIM. 23.6651.071</p>
        <p>POLITEKNIK NEGERI SAMARINDA</p>
        <p>2026</p>
        <p>BAB I PENDAHULUAN</p>
        <p>1.1 Latar Belakang Masalah</p>
        <p>Limbah cair industri merupakan permasalahan lingkungan yang kritis.</p>
HTML;

    $file = UploadedFile::fake()->createWithContent('SKRIPSI_BAITS.docx', $skripsiContent);

    $response = $this->postJson('/api/documents/import', [
        'file' => $file,
    ]);

    $response->assertStatus(200);
    $adapted = $response->json('adapted_settings');

    expect($adapted['is_academic'])->toBeTrue()
        ->and($adapted['margins'])->toBe('skripsi')
        ->and($adapted['margin_details']['top'])->toBe('4cm')
        ->and($adapted['margin_details']['left'])->toBe('4cm')
        ->and($adapted['margin_details']['right'])->toBe('3cm')
        ->and($adapted['margin_details']['bottom'])->toBe('3cm')
        ->and($adapted['font_size'])->toBe('12pt')
        ->and($adapted['line_spacing'])->toBe('1.5');

    $html = $response->json('content_html');
    expect($html)->toContain('text-center font-bold')
        ->and($html)->toContain('heading-chapter')
        ->and($html)->toContain('heading-section')
        ->and($html)->toContain('data-page-break');
});

test('black-box: user can import a multi-page skripsi with preliminaries and receive partitioned page breaks', function () {
    $thesisDoc = <<<'HTML'
        <p>SKRIPSI</p>
        <p>PENERAPAN ENSEMBLE LEARNING UNTUK KLASIFIKASI KUALITAS LIMBAH CAIR TEKSTIL</p>
        <p>Oleh : BAITS RIKA SAPUTRA</p>
        <p>NIM. 23.6651.071</p>
        <p>POLITEKNIK NEGERI SAMARINDA</p>
        <p>2026</p>
        <p>THESIS</p>
        <p>IMPLEMENTATION OF ENSEMBLE LEARNING FOR TEXTILE LIQUID WASTE</p>
        <p>PERNYATAAN KEASLIAN SKRIPSI</p>
        <p>Saya menyatakan bahwa karya ini adalah asli dan bebas plagiasi.</p>
        <p>LEMBAR PERSETUJUAN</p>
        <p>Disetujui oleh pembimbing akademik.</p>
        <p>ABSTRAK</p>
        <p>Limbah cair industri tekstil berpotensi mencemari ekosistem perairan jika tidak dipantau secara ketat.</p>
        <p>BAB 1 PENDAHULUAN</p>
        <p>1.1 Latar Belakang</p>
        <p>Latar belakang riset otomasi pemantauan limbah berbasis sensor dan mikrokontroler.</p>
        <p>BAB 2 TINJAUAN PUSTAKA</p>
        <p>2.1 Sensor Warna TCS34725</p>
        <p>Penjelasan teknis fotometri dan kalibrasi sensor.</p>
        <p>BAB 3 METODE PENELITIAN</p>
        <p>3.1 Tahapan Riset</p>
        <p>Metode pengumpulan data limbah dan pelatihan model ensemble.</p>
HTML;

    $file = UploadedFile::fake()->createWithContent('SKRIPSI 236651071 BAITS RIKA SAPUTRA.docx', $thesisDoc);

    $response = $this->postJson('/api/documents/import', [
        'file' => $file,
    ]);

    $response->assertStatus(200);
    $html = $response->json('content_html');

    expect($html)->toContain('THESIS')
        ->and($html)->toContain('PERNYATAAN KEASLIAN SKRIPSI')
        ->and($html)->toContain('LEMBAR PERSETUJUAN')
        ->and($html)->toContain('ABSTRAK')
        ->and($html)->toContain('BAB 1 PENDAHULUAN')
        ->and($html)->toContain('BAB 2 TINJAUAN PUSTAKA')
        ->and($html)->toContain('BAB 3 METODE PENELITIAN')
        ->and(substr_count($html, 'data-page-break="true"'))->toBeGreaterThanOrEqual(6);
});

test('black-box: user can import docx with table of contents and ensure dot-leader entries are rendered as toc-entry without outline pollution', function () {
    $thesisWithToc = <<<'HTML'
        <p>SKRIPSI</p>
        <p>PENERAPAN ENSEMBLE LEARNING UNTUK KLASIFIKASI KUALITAS LIMBAH CAIR TEKSTIL</p>
        <p>Oleh : BAITS RIKA SAPUTRA</p>
        <p>NIM. 23.6651.071</p>
        <p>POLITEKNIK NEGERI SAMARINDA</p>
        <p>2026</p>
        <p>DAFTAR ISI</p>
        <p>BAB 1 PENDAHULUAN ................................................ 1</p>
        <p>1.1 Latar Belakang ................................................ 1</p>
        <p>1.2 Rumusan Masalah ................................................ 3</p>
        <p>BAB 2 TINJAUAN PUSTAKA ................................................ 6</p>
        <p>2.1 Landasan Teori ................................................ 6</p>
        <p>BAB 1 PENDAHULUAN</p>
        <p>1.1 Latar Belakang</p>
        <p>Air limbah tekstil mengandung zat pewarna azo berbahaya.</p>
        <p>BAB 2 TINJAUAN PUSTAKA</p>
        <p>2.1 Landasan Teori</p>
        <p>Ensemble learning menggabungkan beberapa algoritma prediksi.</p>
HTML;

    $file = UploadedFile::fake()->createWithContent('SKRIPSI 236651071 BAITS RIKA SAPUTRA.docx', $thesisWithToc);

    $response = $this->postJson('/api/documents/import', [
        'file' => $file,
    ]);

    $response->assertStatus(200);
    $html = $response->json('content_html');

    // Verify TOC entries rendered with proper styling
    expect($html)->toContain('class="toc-entry')
        ->and($html)->toContain('border-dotted')
        ->and($html)->toContain('toc-page');

    // Verify headings do not contain Table of Contents lines or author metadata
    $headings = $response->json('headings');
    $texts = array_column($headings, 'text');

    expect($texts)->not->toContain('BAB 1 PENDAHULUAN ................................................ 1')
        ->and($texts)->not->toContain('1.1 Latar Belakang ................................................ 1')
        ->and($texts)->not->toContain('BAITS RIKA SAPUTRA')
        ->and($texts)->not->toContain('NIM. 23.6651.071')
        ->and($texts)->toContain('DAFTAR ISI')
        ->and($texts)->toContain('BAB 1 PENDAHULUAN')
        ->and($texts)->toContain('1.1 Latar Belakang');
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

test('black-box: user can import multi-cover academic document with NIM and verify page breaks and no false TOC', function () {
    $multiCoverDoc = <<<'HTML'
        <p><strong>SKRIPSI</strong></p>
        <p><strong>PENERAPAN ENSEMBLE LEARNING UNTUK KLASIFIKASI KUALITAS LIMBAH CAIR TEKSTIL BERBASIS SENSOR WARNA</strong></p>
        <figure><img src="data:image/png;base64,sample" /></figure>
        <p>Oleh :<br />BAITS RIKA SAPUTRA<br />NIM. 23.6651.071</p>
        <p>POLITEKNIK NEGERI SAMARINDA<br />2026</p>
        <div class="page-break" data-page-break="true"><span class="page-break-tag">Pemisah Halaman</span></div>
        <p><strong>SKRIPSI</strong></p>
        <p><strong>PENERAPAN ENSEMBLE LEARNING</strong></p>
        <p>Sebagai salah satu syarat untuk menyelesaikan Program Studi Diploma IV (Sarjana Terapan)<br />Jurusan Teknologi Informasi Politeknik Negeri Samarinda</p>
        <p>Oleh :<br />BAITS RIKA SAPUTRA<br />NIM. 23.6651.071</p>
        <p>POLITEKNIK NEGERI SAMARINDA<br />2026</p>
        <div class="page-break" data-page-break="true"><span class="page-break-tag">Pemisah Halaman</span></div>
        <p><strong>THESIS</strong></p>
        <p><strong>IMPLEMENTATION OF ENSEMBLE LEARNING FOR TEXTILE LIQUID WASTE</strong></p>
        <p>Submitted as a partial fulfillment of the requirements for Applied Bachelor Degree<br />Department of Information Technology Samarinda State Polytechnic</p>
        <p>By :<br />BAITS RIKA SAPUTRA<br />NIM. 23.6651.071</p>
        <p>STATE POLYTECHNIC<br />2026</p>
        <div class="page-break" data-page-break="true"><span class="page-break-tag">Pemisah Halaman</span></div>
        <p><strong>PERNYATAAN KEASLIAN SKRIPSI</strong></p>
        <p>Saya menyatakan dengan sesungguhnya bahwa Skripsi ini adalah asli karya saya.</p>
        <p><strong>BAB 1<br />PENDAHULUAN</strong></p>
        <p>1.1 Latar Belakang</p>
        <p>Limbah tekstil memiliki bahaya lingkungan.</p>
HTML;

    $file = UploadedFile::fake()->createWithContent('SKRIPSI_236651071_BAITS_RIKA_SAPUTRA.docx', $multiCoverDoc);

    $response = $this->postJson('/api/documents/import', [
        'file' => $file,
    ]);

    $response->assertStatus(200);
    $html = $response->json('content_html');

    // NIM must NOT be converted to a Table of Contents dot leader entry
    expect($html)->not->toContain('toc-entry flex items-baseline justify-between')
        ->and($html)->toContain('NIM. 23.6651.071')
        ->and($html)->not->toContain('BAITS RIKA SAPUTRANIM')
        ->and($html)->toContain('cover-author-label')
        ->and($html)->toContain('cover-subtitle')
        ->and($html)->toContain('PERNYATAAN KEASLIAN SKRIPSI')
        ->and(substr_count($html, 'data-page-break="true"'))->toBeGreaterThanOrEqual(3);

    $headings = array_column($response->json('headings'), 'text');
    expect($headings)->toContain('PERNYATAAN KEASLIAN SKRIPSI')
        ->and($headings)->toContain('BAB 1 PENDAHULUAN')
        ->and($headings)->toContain('1.1 Latar Belakang')
        ->and($headings)->not->toContain('SKRIPSI')
        ->and($headings)->not->toContain('THESIS');
});

test('black-box: user can upload real docx with tables and receive parsed table elements in json response', function () {
    $tableDocxPath = base_path('node_modules/mammoth/test/test-data/tables.docx');
    expect(file_exists($tableDocxPath))->toBeTrue();

    $file = new UploadedFile(
        $tableDocxPath,
        'tabel_evaluasi.docx',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        null,
        true
    );

    $response = $this->postJson('/api/documents/import', [
        'file' => $file,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'format' => 'docx',
            'filename' => 'tabel_evaluasi.docx',
        ]);

    $html = $response->json('content_html');
    expect($html)->toContain('<table')
        ->and($html)->toContain('<tbody>')
        ->and($html)->toContain('Top left')
        ->and($html)->toContain('Top right')
        ->and($html)->toContain('Bottom left')
        ->and($html)->toContain('Bottom right')
        ->and($html)->toContain('Above')
        ->and($html)->toContain('Below');
});

test('black-box: user can upload real docx with underlines and lists and receive formatted html', function () {
    $uPath = base_path('node_modules/mammoth/test/test-data/underline.docx');
    $uFile = new UploadedFile(
        $uPath,
        'dokumen_garis_bawah.docx',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        null,
        true
    );

    $uRes = $this->postJson('/api/documents/import', ['file' => $uFile]);
    $uRes->assertStatus(200);
    $uHtml = $uRes->json('content_html');
    expect($uHtml)->toContain('<u>')
        ->and($uHtml)->toContain('Sunset')
        ->and($uHtml)->toContain('<strong>');

    $lPath = base_path('node_modules/mammoth/test/test-data/simple-list.docx');
    $lFile = new UploadedFile(
        $lPath,
        'daftar_belanja.docx',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        null,
        true
    );

    $lRes = $this->postJson('/api/documents/import', ['file' => $lFile]);
    $lRes->assertStatus(200);
    $lHtml = $lRes->json('content_html');
    expect($lHtml)->toContain('<ul')
        ->and($lHtml)->toContain('<li>Apple</li>')
        ->and($lHtml)->toContain('<li>Banana</li>');
});

test('black-box: user can upload real docx with embedded images and receive base64 image', function () {
    $picPath = base_path('node_modules/mammoth/test/test-data/tiny-picture.docx');
    $picFile = new UploadedFile(
        $picPath,
        'dokumen_bergambar.docx',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        null,
        true
    );

    $response = $this->postJson('/api/documents/import', ['file' => $picFile]);
    $response->assertStatus(200);
    $html = $response->json('content_html');
    expect($html)->toContain('<img src="data:image/png;base64,')
        ->and($html)->toContain('alt="Dokumen Gambar"');
});

test('black-box: user can import a non-academic docx report and typography remains normal without false cover page', function () {
    $reportDocxPath = base_path('node_modules/mammoth/test/test-data/single-paragraph.docx');
    $reportFile = new UploadedFile(
        $reportDocxPath,
        'laporan_harian_kantor.docx',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        null,
        true
    );

    $response = $this->postJson('/api/documents/import', ['file' => $reportFile]);
    $response->assertStatus(200);

    $presets = $response->json('adapted_settings');
    expect($presets['is_academic'])->toBeFalse()
        ->and($presets['margins'])->toBe('normal')
        ->and($response->json('content_html'))->not->toContain('cover-badge')
        ->and($response->json('content_html'))->not->toContain('cover-title');
});

test('black-box: user can import docx with headers and footers and receive metadata in response', function () {
    $zipPath = tempnam(sys_get_temp_dir(), 'docx_feat_').'.docx';
    $zip = new ZipArchive;
    $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    $hdrXml = '<?xml version="1.0" encoding="UTF-8"?><w:hdr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:p><w:r><w:t>Header Dokumen Resmi</w:t></w:r></w:p></w:hdr>';
    $ftrXml = '<?xml version="1.0" encoding="UTF-8"?><w:ftr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:p><w:r><w:t>Footer Kerahasiaan Tingkat Tinggi</w:t></w:r></w:p></w:ftr>';
    $docXml = '<?xml version="1.0" encoding="UTF-8"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>Isi berkas penting.</w:t></w:r></w:p></w:body></w:document>';

    $zip->addFromString('word/header1.xml', $hdrXml);
    $zip->addFromString('word/footer1.xml', $ftrXml);
    $zip->addFromString('word/document.xml', $docXml);
    $zip->close();

    $file = new UploadedFile(
        $zipPath,
        'dokumen_resmi.docx',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        null,
        true
    );

    $response = $this->postJson('/api/documents/import', ['file' => $file]);
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'header_text' => 'Header Dokumen Resmi',
            'footer_text' => 'Footer Kerahasiaan Tingkat Tinggi',
        ]);

    @unlink($zipPath);
});

test('black-box: editor view renders running header and footer structure without no-print on sheet elements', function () {
    $response = $this->get('/editor');
    $response->assertStatus(200);

    $content = $response->getContent();
    expect($content)->toContain('class="page-sheet-header')
        ->and($content)->toContain('class="header-doc-title')
        ->and($content)->toContain('class="page-sheet-footer')
        ->and($content)->toContain('class="footer-doc-title')
        ->and($content)->toContain('class="footer-page-num')
        ->and($content)->not->toMatch('/class="page-sheet-header[^"]*no-print/')
        ->and($content)->not->toMatch('/class="page-sheet-footer[^"]*no-print/');
});
