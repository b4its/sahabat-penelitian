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
    preg_match('/<footer class="page-sheet-footer[^>]*>(.*?)<\/footer>/s', $content, $footerMatches);
    $footerInnerHtml = $footerMatches[1] ?? '';

    expect($content)->toContain('class="page-sheet-header')
        ->and($content)->toContain('class="header-doc-title')
        ->and($content)->toContain('class="page-sheet-footer')
        ->and($content)->toContain('class="footer-doc-title')
        ->and($content)->toContain('class="page-side-indicator')
        ->and($footerInnerHtml)->not->toContain('Halaman')
        ->and($footerInnerHtml)->not->toContain('dari')
        ->and($footerInnerHtml)->not->toContain('footer-page-num')
        ->and($content)->not->toMatch('/class="page-sheet-header[^"]*no-print/')
        ->and($content)->not->toMatch('/class="page-sheet-footer[^"]*no-print/');
});

test('black-box: editor view contains all Microsoft Word ribbon tabs including Berkas and Tinjau', function () {
    $response = $this->get('/editor');
    $response->assertStatus(200);

    // All Ribbon Tabs (Berkas, Beranda, Sisipkan, Tata Letak, Tinjau, Tampilan, Wordcraft)
    $response->assertSee('data-ribbon-tab="file"', false);
    $response->assertSee('data-ribbon-tab="home"', false);
    $response->assertSee('data-ribbon-tab="insert"', false);
    $response->assertSee('data-ribbon-tab="layout"', false);
    $response->assertSee('data-ribbon-tab="review"', false);
    $response->assertSee('data-ribbon-tab="view"', false);
    $response->assertSee('data-ribbon-tab="ai"', false);

    // Microsoft Word specific tools
    $response->assertSee('btn-format-painter', false);
    $response->assertSee('btn-font-grow', false);
    $response->assertSee('btn-font-shrink', false);
    $response->assertSee('btn-checklist', false);
    $response->assertSee('quick-style-btn', false);
    $response->assertSee('btn-read-aloud', false);
    $response->assertSee('btn-word-stats', false);
    $response->assertSee('btn-puebi-check', false);
    $response->assertSee('id="layout-margins"', false);
    $response->assertSee('value="skripsi"', false);
    $response->assertSee('data-load-sample="skripsi"', false);
    $response->assertSee('data-load-sample="report"', false);
});

test('black-box: editor view renders Google Docs style outline search and adaptation banner', function () {
    $response = $this->get('/editor');
    $response->assertStatus(200);

    // Google Docs Outline search & tree
    $response->assertSee('outline-sidebar', false);
    $response->assertSee('outline-search-input', false);
    $response->assertSee('outline-headings-list', false);

    // Local Document Adaptation Banner
    $response->assertSee('document-adaptation-banner', false);
    $response->assertSee('adaptation-banner-title', false);
    $response->assertSee('adaptation-banner-desc', false);
    $response->assertSee('banner-btn-skripsi', false);
    $response->assertSee('banner-btn-normal', false);
    $response->assertSee('banner-btn-close', false);
});

test('black-box: editor view renders Wordcraft and academic modals for covers, symbols, and citations', function () {
    $response = $this->get('/editor');
    $response->assertStatus(200);

    // Cover Page Modal
    $response->assertSee('modal-cover-page', false);
    $response->assertSee('data-insert-cover="skripsi"', false);
    $response->assertSee('data-insert-cover="corporate"', false);

    // Symbols Modal
    $response->assertSee('modal-symbols', false);
    $response->assertSee('data-insert-symbol="±"', false);
    $response->assertSee('data-insert-symbol="π"', false);
    $response->assertSee('data-insert-symbol="∑"', false);

    // Citation Modal
    $response->assertSee('modal-citation', false);
    $response->assertSee('btn-confirm-citation', false);
    $response->assertSee('citation-author', false);
    $response->assertSee('citation-year', false);
    $response->assertSee('citation-title', false);
    $response->assertSee('citation-source', false);
});

test('black-box: user can export document to docx, markdown, html, txt, and json formats', function () {
    $formats = ['docx', 'markdown', 'html', 'txt', 'json'];
    $sampleHtml = '<h1>BAB I PENDAHULUAN</h1><p>Penelitian komprehensif sistem dokumen.</p>';

    foreach ($formats as $fmt) {
        $response = $this->postJson('/api/documents/export', [
            'content' => $sampleHtml,
            'title' => 'Naskah Uji Coba',
            'format' => $fmt,
        ]);

        $response->assertStatus(200);
        $disposition = $response->headers->get('content-disposition');
        expect($disposition)->toContain('attachment');
        expect($disposition)->toContain('Naskah_Uji_Coba');
    }
});

test('black-box: editor view loads compiled client assets without temporal dead zone initialization errors', function () {
    $manifestPath = public_path('build/manifest.json');
    expect(file_exists($manifestPath))->toBeTrue();

    $manifest = json_decode(file_get_contents($manifestPath), true);
    expect($manifest)->toHaveKey('resources/js/app.js');

    $appJsFile = public_path('build/'.$manifest['resources/js/app.js']['file']);
    expect(file_exists($appJsFile))->toBeTrue();

    // Verify source code doesn't call engine.escapeHtml before engine assignment
    $sourceAppJs = file_get_contents(resource_path('js/app.js'));
    expect($sourceAppJs)->not->toContain('engine.escapeHtml');
    expect($sourceAppJs)->toContain('window.uiController = uiController');

    // Verify getActiveParagraphFormat does not reference undefined indentLeft/indentRight shorthands
    $sourceEngineJs = file_get_contents(resource_path('js/editor/document-engine.js'));
    expect($sourceEngineJs)->not->toContain('indentLeft,');
    expect($sourceEngineJs)->toContain('indentLeft: leftIndent,');
    expect($sourceEngineJs)->toContain('indentRight: rightIndent,');
});

test('black-box: editor view renders Header & Footer customization modal with full controls', function () {
    $response = $this->get('/editor');
    $response->assertStatus(200);

    // Modal Header & Footer
    $response->assertSee('id="modal-header-footer"', false);
    $response->assertSee('id="hf-header-input"', false);
    $response->assertSee('id="hf-footer-input"', false);
    $response->assertSee('id="hf-page-format-select"', false);
    $response->assertSee('id="hf-start-page-input"', false);
    $response->assertSee('id="hf-show-page-number-toggle"', false);
    $response->assertSee('id="hf-different-first-toggle"', false);
    $response->assertSee('id="btn-apply-header-footer"', false);
    $response->assertSee('id="btn-reset-header-footer"', false);

    // Ribbon actions
    $response->assertSee('data-action="header-footer-modal"', false);
    $response->assertSee('data-action="page-number-modal"', false);
});

test('black-box: user can export document with custom header and footer into doc format', function () {
    $response = $this->postJson('/api/documents/export', [
        'title' => 'Laporan Akhir Penelitian',
        'content' => '<p>Hasil pengujian model machine learning.</p>',
        'format' => 'doc',
        'header_text' => 'UNIVERSITAS GADJAH MADA - DEPARTEMEN ILMU KOMPUTER',
        'footer_text' => 'Draft Penelitian 2026 - Rahasia',
    ]);

    $response->assertStatus(200);
    $content = $response->getContent();

    expect($content)->toContain('mso-header: h1;')
        ->and($content)->toContain('mso-footer: f1;')
        ->and($content)->toContain('UNIVERSITAS GADJAH MADA - DEPARTEMEN ILMU KOMPUTER')
        ->and($content)->toContain('Draft Penelitian 2026 - Rahasia')
        ->and($content)->toContain('Hasil pengujian model machine learning');
});

test('black-box: editor view does not force default header or footer in canvas and displays unapplied indicators outside canvas', function () {
    $response = $this->get('/editor');
    $response->assertStatus(200);

    // Document canvas header and footer must NOT contain forced text
    $response->assertSee('class="header-doc-title truncate max-w-[55%] font-medium text-gray-600 dark:text-gray-300"></span>', false);
    $response->assertSee('class="footer-doc-title text-[11px] text-gray-400 font-mono truncate max-w-[85%]"></span>', false);

    // Outside document canvas, side badges must indicate header & footer have not yet been applied
    $response->assertSee('side-header-status', false);
    $response->assertSee('side-footer-status', false);
    $response->assertSee('Header:<br><span class="text-[7.5px] text-amber-500">Belum diterapkan</span>', false);
    $response->assertSee('Footer:<br><span class="text-[7.5px] text-amber-500">Belum diterapkan</span>', false);
});

test('black-box: exporting document without custom header and footer omits Word MsoHeader and MsoFooter elements and synthetic text', function () {
    $response = $this->postJson('/api/documents/export', [
        'title' => 'Naskah Bersih Uji Coba',
        'content' => '<p>Konten bebas header dan footer.</p>',
        'format' => 'docx',
        'header_text' => null,
        'footer_text' => null,
    ]);

    $response->assertStatus(200);
    $content = $response->getContent();

    expect($content)->not->toContain('mso-header: h1;')
        ->and($content)->not->toContain('mso-footer: f1;')
        ->and($content)->not->toContain('id="h1"')
        ->and($content)->not->toContain('id="f1"')
        ->and($content)->not->toContain('MsoHeader')
        ->and($content)->not->toContain('MsoFooter')
        ->and($content)->not->toContain('Dokumen VelScribe Wordcraft')
        ->and($content)->toContain('Konten bebas header dan footer');
});

test('black-box: user can upload docx with referenced headers and footers and receive them in response', function () {
    $zipPath = tempnam(sys_get_temp_dir(), 'docx_upload_hf_').'.docx';
    $zip = new ZipArchive;
    $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    $relsXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rIdHeaderDefault" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/header" Target="header2.xml"/>
    <Relationship Id="rIdFooterDefault" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/footer" Target="footer2.xml"/>
</Relationships>
XML;
    $zip->addFromString('word/_rels/document.xml.rels', $relsXml);

    $zip->addFromString('word/header2.xml', '<?xml version="1.0" encoding="UTF-8"?><w:hdr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:p><w:r><w:t>INSTITUT TEKNOLOGI BANDUNG</w:t></w:r></w:p></w:hdr>');
    $zip->addFromString('word/footer2.xml', '<?xml version="1.0" encoding="UTF-8"?><w:ftr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:p><w:r><w:t>Publikasi Jurnal 2026</w:t></w:r></w:p></w:ftr>');

    $docXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <w:body>
        <w:p><w:r><w:t>Konten Jurnal Ilmiah</w:t></w:r></w:p>
        <w:sectPr>
            <w:headerReference w:type="default" r:id="rIdHeaderDefault"/>
            <w:footerReference w:type="default" r:id="rIdFooterDefault"/>
            <w:titlePg/>
        </w:sectPr>
    </w:body>
</w:document>
XML;
    $zip->addFromString('word/document.xml', $docXml);
    $zip->close();

    $file = new UploadedFile($zipPath, 'jurnal_itb.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);

    $response = $this->postJson('/api/documents/import', [
        'file' => $file,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'header_text' => 'INSTITUT TEKNOLOGI BANDUNG',
            'footer_text' => 'Publikasi Jurnal 2026',
            'different_first_page' => true,
        ]);

    @unlink($zipPath);
});

test('black-box: editor view contains expanded font family selector with standard Word fonts', function () {
    $response = $this->get('/editor');
    $response->assertStatus(200);

    // Verify all standard Word and document fonts exist in dropdown
    $response->assertSee('value="\'Times New Roman\', serif"', false);
    $response->assertSee('value="Arial, sans-serif"', false);
    $response->assertSee('value="Calibri, sans-serif"', false);
    $response->assertSee('value="Aptos, \'Segoe UI\', sans-serif"', false);
    $response->assertSee('value="\'Segoe UI\', sans-serif"', false);
    $response->assertSee('value="Georgia, serif"', false);
    $response->assertSee('value="Garamond, serif"', false);
    $response->assertSee('value="Cambria, Georgia, serif"', false);
    $response->assertSee('value="Verdana, sans-serif"', false);
    $response->assertSee('value="Tahoma, sans-serif"', false);
    $response->assertSee('value="\'Courier New\', monospace"', false);
    $response->assertSee('value="Consolas, \'Courier New\', monospace"', false);
});

test('black-box: user can upload docx with custom colors and theme colors preserving accurate typography', function () {
    $zipPath = tempnam(sys_get_temp_dir(), 'docx_blackbox_font_').'.docx';
    $zip = new ZipArchive;
    $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    $themeXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<a:theme xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" name="Office Theme">
    <a:themeElements>
        <a:clrScheme name="Office">
            <a:dark1><a:sysClr val="windowText" lastClr="000000"/></a:dark1>
            <a:light1><a:sysClr val="window" lastClr="FFFFFF"/></a:light1>
            <a:dark2><a:srgbClr val="1F497D"/></a:dark2>
            <a:light2><a:srgbClr val="EEECE1"/></a:light2>
            <a:accent1><a:srgbClr val="2F5496"/></a:accent1>
        </a:clrScheme>
    </a:themeElements>
</a:theme>
XML;
    $zip->addFromString('word/theme/theme1.xml', $themeXml);

    $docDefaultsStyles = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:docDefaults>
        <w:rPrDefault>
            <w:rPr>
                <w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/>
                <w:sz w:val="24"/>
            </w:rPr>
        </w:rPrDefault>
    </w:docDefaults>
    <w:style w:type="paragraph" w:styleId="Heading1">
        <w:name w:val="heading 1"/>
        <w:rPr>
            <w:rFonts w:ascii="Calibri Light" w:hAnsi="Calibri Light"/>
            <w:color w:val="2F5496" w:themeColor="accent1"/>
            <w:sz w:val="32"/>
            <w:b/>
        </w:rPr>
    </w:style>
</w:styles>
XML;
    $zip->addFromString('word/styles.xml', $docDefaultsStyles);

    $docXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:p>
            <w:pPr>
                <w:pStyle w:val="Heading1"/>
            </w:pPr>
            <w:r>
                <w:t>BAB I PENDAHULUAN</w:t>
            </w:r>
        </w:p>
        <w:p>
            <w:r>
                <w:rPr>
                    <w:color w:val="E11D48"/>
                    <w:rFonts w:ascii="Arial"/>
                </w:rPr>
                <w:t>Catatan penting dalam warna rose.</w:t>
            </w:r>
        </w:p>
    </w:body>
</w:document>
XML;
    $zip->addFromString('word/document.xml', $docXml);
    $zip->close();

    $file = new UploadedFile($zipPath, 'laporan_font.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);

    $response = $this->postJson('/api/documents/import', [
        'file' => $file,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'default_font_family' => "'Calibri', sans-serif",
        ]);

    $content = $response->json('content_html');
    expect($content)->toContain('color: #2F5496;')
        ->and($content)->toContain('font-family: \'Calibri Light\', sans-serif;')
        ->and($content)->toContain('color: #E11D48;')
        ->and($content)->toContain('font-family: \'Arial\', sans-serif;');

    @unlink($zipPath);
});

test('black-box: editor view enforces high contrast typography and Linux serif fallbacks on simulated paper sheets', function () {
    $response = $this->get('/editor');
    $response->assertStatus(200);

    // Verify paper sheet high contrast rules exist
    $response->assertSee('.document-page-sheet:not(.canvas-dark-sheet) .page-sheet-body', false);
    $response->assertSee('color: #111827 !important;', false);

    // Verify Linux metric-compatible serif font stack
    $response->assertSee("'Liberation Serif'", false);
    $response->assertSee("'Nimbus Roman'", false);
    $response->assertSee("'DejaVu Serif'", false);

    // Verify unshaded white text inversion/suppression on white sheets
    $response->assertSee('[style*="color: #ffffff"]', false);
});

test('black-box: docx import suppresses unshaded near-white text and preserves high contrast on cover and body', function () {
    $zipPath = tempnam(sys_get_temp_dir(), 'docx_white_').'.docx';
    $zip = new ZipArchive;
    $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    $docXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:p>
            <w:r>
                <w:rPr>
                    <w:color w:val="FFFFFF"/>
                </w:rPr>
                <w:t>Teks tidak boleh jadi putih di atas kertas putih.</w:t>
            </w:r>
        </w:p>
        <w:p>
            <w:r>
                <w:t>SKRIPSI 236661027 ALVIN GRAND PUTRA</w:t>
            </w:r>
        </w:p>
        <w:p>
            <w:r>
                <w:t>Oleh :</w:t>
            </w:r>
        </w:p>
        <w:p>
            <w:r>
                <w:t>NIM 236661027</w:t>
            </w:r>
        </w:p>
        <w:p>
            <w:r>
                <w:t>POLITEKNIK NEGERI SAMARINDA</w:t>
            </w:r>
        </w:p>
    </w:body>
</w:document>
XML;
    $zip->addFromString('word/document.xml', $docXml);
    $zip->close();

    $file = new UploadedFile($zipPath, 'skripsi_test.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);

    $response = $this->postJson('/api/documents/import', [
        'file' => $file,
    ]);

    $response->assertStatus(200);
    $html = $response->json('content_html');

    // Unshaded white text must not have inline style="color: #ffffff;"
    expect($html)->not->toContain('color: #ffffff;')
        ->and($html)->not->toContain('color: #fff;')
        // Cover items must not contain inverted dark classes
        ->and($html)->not->toContain('dark:text-gray-100')
        ->and($html)->not->toContain('dark:text-gray-200')
        ->and($html)->not->toContain('dark:text-gray-300');

    @unlink($zipPath);
});

test('black-box: uploading docx with latex formula renders interactive equation with katex markup', function () {
    $zipPath = tempnam(sys_get_temp_dir(), 'docx_latex_').'.docx';
    $zip = new ZipArchive;
    $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    $docXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:p>
            <w:r>
                <w:t>$$F1\text {-} Score = 2 \times \frac {1 {,} 0 \times 1 {,} 0} {1 {,} 0 + 1 {,} 0} = 100 {,} 00 \% \quad (4.9)$$</w:t>
            </w:r>
        </w:p>
    </w:body>
</w:document>
XML;
    $zip->addFromString('word/document.xml', $docXml);
    $zip->close();

    $file = new UploadedFile($zipPath, 'skripsi_formula.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);

    $response = $this->postJson('/api/documents/import', [
        'file' => $file,
    ]);

    $response->assertStatus(200);
    $html = $response->json('content_html');

    expect($html)->toContain('latex-equation')
        ->and($html)->toContain('display-equation')
        ->and($html)->toContain('data-latex=');

    @unlink($zipPath);
});

test('black-box: uploading docx with OMML math translates into latex equation', function () {
    $zipPath = tempnam(sys_get_temp_dir(), 'docx_omml_').'.docx';
    $zip = new ZipArchive;
    $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    $docXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"
            xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math">
    <w:body>
        <w:p>
            <m:oMath>
                <m:f>
                    <m:num><m:r><m:t>a</m:t></m:r></m:num>
                    <m:den><m:r><m:t>b</m:t></m:r></m:den>
                </m:f>
            </m:oMath>
        </w:p>
    </w:body>
</w:document>
XML;
    $zip->addFromString('word/document.xml', $docXml);
    $zip->close();

    $file = new UploadedFile($zipPath, 'skripsi_omml.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);

    $response = $this->postJson('/api/documents/import', [
        'file' => $file,
    ]);

    $response->assertStatus(200);
    $html = $response->json('content_html');

    expect($html)->toContain('latex-equation')
        ->and($html)->toContain('data-latex=')
        ->and($html)->toContain('\frac{a}{b}');

    @unlink($zipPath);
});

test('black-box: editor page provides latex equation modal and insert button in insert ribbon', function () {
    $response = $this->get(route('editor.index'));
    $response->assertStatus(200);

    $response->assertSee('modal-latex-equation', false);
    $response->assertSee('insert-equation-modal', false);
    $response->assertSee('Rumus Matematika (LaTeX)', false);
    $response->assertSee('latex-preview', false);
    $response->assertSee('btn-delete-equation', false);
    $response->assertSee('btn-save-equation', false);
});

test('black-box: editor page provides visual dark and light theme switch controls across navigation, ribbon, and status bar', function () {
    $response = $this->get(route('editor.index'));
    $response->assertStatus(200);

    // 1. Top navigation visual switcher
    $response->assertSee('id="theme-toggle-btn"', false);
    $response->assertSee('role="switch"', false);
    $response->assertSee('id="theme-toggle-knob"', false);
    $response->assertSee('id="knob-icon"', false);
    $response->assertSee('id="theme-toggle-text"', false);
    $response->assertSee('id="theme-icon-sun"', false);
    $response->assertSee('id="theme-icon-moon"', false);
    $response->assertSee('Mode Terang', false);

    // 2. Early no-flicker initialization script in head
    $response->assertSee('localStorage.getItem(\'velscribe_theme\')', false);
    $response->assertSee('html.theme-transitioning', false);

    // 3. Ribbon View panel controls (Segmented switch & Sheet toggle)
    $response->assertSee('id="segment-theme-light"', false);
    $response->assertSee('id="segment-theme-dark"', false);
    $response->assertSee('id="segment-theme-system"', false);
    $response->assertSee('id="btn-toggle-dark-sheet"', false);
    $response->assertSee('id="sheet-theme-icon"', false);
    $response->assertSee('id="sheet-theme-label"', false);

    // 4. Status bar controls
    $response->assertSee('id="btn-status-theme-toggle"', false);
    $response->assertSee('id="status-theme-icon"', false);

    // 5. Visual Theme Selector Modal (Modal 13)
    $response->assertSee('id="modal-theme-selector"', false);
    $response->assertSee('data-theme-card="light"', false);
    $response->assertSee('data-theme-card="dark"', false);
    $response->assertSee('data-theme-card="system"', false);
    $response->assertSee('name="sheet-canvas-theme"', false);

    // 6. Floating quick-toggle widget
    $response->assertSee('id="floating-theme-widget"', false);
    $response->assertSee('id="floating-theme-toggle"', false);
    $response->assertSee('id="floating-theme-icon"', false);
});

test('black-box: user can search academic sources via api with query and type', function () {
    $response = $this->postJson(route('documents.search-sources'), [
        'query' => 'TCS34725 ESP32',
        'type' => 'all',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'query' => 'TCS34725 ESP32',
        ])
        ->assertJsonStructure([
            'success',
            'query',
            'count',
            'results' => [
                '*' => [
                    'id',
                    'title',
                    'author',
                    'year',
                    'preview_apa',
                    'preview_ieee',
                    'citation_intext_apa',
                    'citation_intext_ieee',
                ],
            ],
        ]);

    expect($response->json('count'))->toBeGreaterThan(0);
});

test('black-box: search sources endpoint validates query and rejects invalid inputs', function () {
    $this->postJson(route('documents.search-sources'), [
        'query' => '',
    ])->assertStatus(422);

    $this->postJson(route('documents.search-sources'), [
        'query' => 'Test',
        'type' => 'invalid_type_xyz',
    ])->assertStatus(422);
});

test('black-box: user can format bibliography via api in multiple citation styles', function () {
    $sources = [
        [
            'id' => 'src-1',
            'type' => 'journal',
            'author' => 'Santoso, Budi & Pratama, Aditya',
            'year' => '2024',
            'title' => 'Implementasi Sistem Sensor TCS34725',
            'journal' => 'Jurnal Rekayasa Sistem',
            'volume' => '8',
            'issue' => '2',
            'pages' => '112-120',
            'doi' => '10.29207/resti.v8i2.5678',
        ],
        [
            'id' => 'src-2',
            'type' => 'book',
            'author' => 'Sugiyono',
            'year' => '2024',
            'title' => 'Metode Penelitian Kuantitatif',
            'publisher' => 'Alfabeta',
        ],
    ];

    // 1. APA 7th Edition
    $resApa = $this->postJson(route('documents.format-bibliography'), [
        'sources' => $sources,
        'style' => 'apa',
        'heading' => 'DAFTAR PUSTAKA',
    ]);

    $resApa->assertStatus(200)
        ->assertJson(['success' => true, 'style' => 'apa']);
    $htmlApa = $resApa->json('html');
    expect($htmlApa)->toContain('DAFTAR PUSTAKA')
        ->and($htmlApa)->toContain('pl-8 -indent-8')
        ->and($htmlApa)->toContain('citation-entry')
        ->and($htmlApa)->toContain('Santoso, Budi & Pratama, Aditya');

    // 2. IEEE Numbered
    $resIeee = $this->postJson(route('documents.format-bibliography'), [
        'sources' => $sources,
        'style' => 'ieee',
        'heading' => 'REFERENSI',
    ]);

    $resIeee->assertStatus(200)
        ->assertJson(['success' => true, 'style' => 'ieee']);
    $htmlIeee = $resIeee->json('html');
    expect($htmlIeee)->toContain('REFERENSI')
        ->and($htmlIeee)->toContain('[1]')
        ->and($htmlIeee)->toContain('data-citation-style="ieee"');
});

test('black-box: editor page renders Microsoft Word style citations and bibliography suite across ribbon and modal', function () {
    $response = $this->get(route('editor.index'));
    $response->assertStatus(200);

    // 1. Ribbon References Controls
    $response->assertSee('data-action="insert-citation-modal"', false);
    $response->assertSee('data-action="open-source-manager"', false);
    $response->assertSee('id="ribbon-citation-style-select"', false);
    $response->assertSee('data-action="insert-bibliography-quick"', false);
    $response->assertSee('id="btn-update-bibliography"', false);
    $response->assertSee('data-action="open-academic-search"', false);

    // 2. Studio Modal Controls
    $response->assertSee('id="modal-citation"', false);
    $response->assertSee('data-citation-nav="search"', false);
    $response->assertSee('data-citation-nav="add"', false);
    $response->assertSee('data-citation-nav="manage"', false);
    $response->assertSee('data-citation-nav="bibliography"', false);

    // Tab 1 Online Search
    $response->assertSee('id="search-source-query"', false);
    $response->assertSee('id="search-source-type"', false);
    $response->assertSee('id="btn-execute-source-search"', false);
    $response->assertSee('id="search-results-list"', false);

    // Tab 2 Manual Add
    $response->assertSee('id="citation-type"', false);
    $response->assertSee('id="citation-author"', false);
    $response->assertSee('id="citation-year"', false);
    $response->assertSee('id="citation-title"', false);
    $response->assertSee('id="citation-source"', false);
    $response->assertSee('id="btn-confirm-citation"', false);
    $response->assertSee('id="btn-save-source-only"', false);

    // Tab 3 Manage Sources
    $response->assertSee('id="source-manager-items"', false);
    $response->assertSee('id="manage-source-filter"', false);
    $response->assertSee('id="btn-add-sample-sources"', false);
    $response->assertSee('id="btn-clear-all-sources"', false);

    // Tab 4 Bibliography
    $response->assertSee('id="modal-bibliography-style"', false);
    $response->assertSee('id="modal-bibliography-heading"', false);
    $response->assertSee('id="bibliography-preview-box"', false);
    $response->assertSee('id="btn-insert-bibliography-block"', false);
    $response->assertSee('id="btn-sync-bibliography-block"', false);
});

test('black-box: user can generate table of contents via api endpoint', function () {
    $contentHtml = <<<'HTML'
<div data-page-index="1">
    <h1>BAB I PENDAHULUAN</h1>
    <h2>1.1 Latar Belakang</h2>
</div>
<div data-page-index="2">
    <h2>1.2 Rumusan Masalah</h2>
    <h3>1.2.1 Identifikasi Masalah</h3>
</div>
<div data-page-index="3">
    <h1>BAB II LANDASAN TEORI</h1>
</div>
HTML;

    $response = $this->postJson(route('documents.generate-toc'), [
        'content_html' => $contentHtml,
        'heading' => 'DAFTAR ISI',
        'style' => 'template',
        'leader' => 'dots',
        'levels' => 3,
        'showPageNumbers' => true,
        'rightAlignPageNumbers' => true,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'count' => 5,
        ]);

    $html = $response->json('html');
    expect($html)->toContain('data-toc-block="true"')
        ->and($html)->toContain('DAFTAR ISI')
        ->and($html)->toContain('BAB I PENDAHULUAN')
        ->and($html)->toContain('1.1 Latar Belakang')
        ->and($html)->toContain('1.2 Rumusan Masalah')
        ->and($html)->toContain('1.2.1 Identifikasi Masalah')
        ->and($html)->toContain('BAB II LANDASAN TEORI')
        ->and($html)->toContain('toc-leader')
        ->and($html)->toContain('toc-page');
});

test('black-box: generate toc endpoint validates options', function () {
    $this->postJson(route('documents.generate-toc'), [
        'style' => 'invalid_style_xyz',
    ])->assertStatus(422);

    $this->postJson(route('documents.generate-toc'), [
        'levels' => 10,
    ])->assertStatus(422);
});

test('black-box: user can update table of contents via api endpoint (page numbers and entire)', function () {
    $initialHeadings = [
        ['id' => 'heading-bab-1', 'text' => 'BAB I PENDAHULUAN', 'level' => 1, 'page' => 1],
    ];

    $generateRes = $this->postJson(route('documents.generate-toc'), [
        'headings' => $initialHeadings,
        'heading' => 'DAFTAR ISI',
    ]);
    $initialHtml = $generateRes->json('html');

    // 1. Update Page Numbers Only
    $resPage = $this->postJson(route('documents.update-toc'), [
        'existing_toc_html' => $initialHtml,
        'headings' => [
            ['id' => 'heading-bab-1', 'text' => 'BAB I PENDAHULUAN', 'level' => 1, 'page' => 10],
        ],
        'update_type' => 'page_numbers',
    ]);

    $resPage->assertStatus(200)
        ->assertJson(['success' => true, 'update_type' => 'page_numbers']);
    expect($resPage->json('html'))->toContain('>10</span>');

    // 2. Update Entire Table
    $resEntire = $this->postJson(route('documents.update-toc'), [
        'existing_toc_html' => $initialHtml,
        'headings' => [
            ['id' => 'heading-bab-1', 'text' => 'BAB I PENDAHULUAN', 'level' => 1, 'page' => 1],
            ['id' => 'heading-bab-2', 'text' => 'BAB II METODE RISET', 'level' => 1, 'page' => 15],
        ],
        'update_type' => 'entire',
    ]);

    $resEntire->assertStatus(200)
        ->assertJson(['success' => true, 'update_type' => 'entire']);
    expect($resEntire->json('html'))->toContain('BAB II METODE RISET')
        ->and($resEntire->json('html'))->toContain('>15</span>');
});

test('black-box: editor page renders Microsoft Word Table of Contents suite across ribbon and modals', function () {
    $response = $this->get(route('editor.index'));
    $response->assertStatus(200);

    // 1. References Ribbon Controls
    $response->assertSee('id="btn-toc-dropdown"', false);
    $response->assertSee('id="toc-dropdown-menu"', false);
    $response->assertSee('data-toc-action="insert-auto1"', false);
    $response->assertSee('data-toc-action="insert-auto2"', false);
    $response->assertSee('data-toc-action="insert-manual"', false);
    $response->assertSee('data-action="open-custom-toc-modal"', false);
    $response->assertSee('data-toc-action="remove-toc"', false);
    $response->assertSee('id="btn-ribbon-update-toc"', false);
    $response->assertSee('id="ribbon-toc-level-select"', false);

    // 2. Custom Table of Contents Modal (Modal 14)
    $response->assertSee('id="modal-custom-toc"', false);
    $response->assertSee('id="toc-heading-input"', false);
    $response->assertSee('id="toc-show-page-numbers"', false);
    $response->assertSee('id="toc-right-align-pages"', false);
    $response->assertSee('id="toc-tab-leader-select"', false);
    $response->assertSee('id="toc-style-select"', false);
    $response->assertSee('id="toc-levels-select"', false);
    $response->assertSee('id="toc-preview-container"', false);
    $response->assertSee('id="btn-confirm-insert-toc"', false);

    // 3. Update Table of Contents Modal (Modal 15)
    $response->assertSee('id="modal-update-toc"', false);
    $response->assertSee('name="toc-update-scope"', false);
    $response->assertSee('value="page_numbers"', false);
    $response->assertSee('value="entire"', false);
    $response->assertSee('id="btn-confirm-update-toc"', false);
});

test('black-box: importing document with ellipses does not spam DAFTAR ISI or pollute document structure', function () {
    $docxContent = <<<'HTML'
        <p>BAB 1 PENDAHULUAN</p>
        <p>1.1 Latar Belakang</p>
        <p>Kajian ini mengeksplorasi penggunaan model pembelajaran mesin... dengan pendekatan terstruktur.</p>
        <p>Hasil awal menunjukkan tren yang sangat menjanjikan, dsb...</p>
        <p>Total pengujian yang dilakukan sebanyak   120</p>
        <p>DAFTAR ISI</p>
        <p>BAB 1 PENDAHULUAN ................................................ 1</p>
        <p>1.1 Latar Belakang ................................................ 1</p>
HTML;

    $file = UploadedFile::fake()->createWithContent('penelitian_ai.docx', $docxContent);

    $response = $this->postJson('/api/documents/import', [
        'file' => $file,
    ]);

    $response->assertStatus(200);
    $html = $response->json('content_html');

    // Normal paragraphs must remain normal text
    expect($html)->toContain('Kajian ini mengeksplorasi penggunaan model pembelajaran mesin... dengan pendekatan terstruktur.')
        ->and($html)->toContain('Hasil awal menunjukkan tren yang sangat menjanjikan, dsb...')
        ->and($html)->toContain('Total pengujian yang dilakukan sebanyak   120');

    // Only genuine TOC lines become toc-entry
    $tocCount = substr_count($html, 'toc-entry');
    expect($tocCount)->toBe(2);

    // Headings list should contain DAFTAR ISI and BAB 1 PENDAHULUAN, not ellipses
    $headings = $response->json('headings');
    $texts = array_column($headings, 'text');
    expect($texts)->toContain('DAFTAR ISI')
        ->and($texts)->toContain('BAB 1 PENDAHULUAN')
        ->and($texts)->not->toContain('Kajian ini mengeksplorasi penggunaan model pembelajaran mesin... dengan pendekatan terstruktur.');
});

test('black-box: generated table of contents provides editable elements and deletion capability', function () {
    $response = $this->postJson(route('documents.generate-toc'), [
        'heading' => 'DAFTAR ISI SKRIPSI',
        'headings' => [
            ['id' => 'heading-bab-1', 'text' => 'BAB I PENDAHULUAN', 'level' => 1, 'page' => 1],
            ['id' => 'heading-sub-1', 'text' => '1.1 Latar Belakang', 'level' => 2, 'page' => 2],
        ],
    ]);

    $response->assertStatus(200);
    $html = $response->json('html');

    // Ensure container has tabindex for keyboard backspace/delete focus
    expect($html)->toContain('tabindex="0"')
        ->and($html)->toContain('contenteditable="false"')
        // Ensure header is editable
        ->and($html)->toContain('<h1 class="toc-header heading-chapter text-center font-bold text-xl mb-6 uppercase tracking-wider text-gray-900 dark:text-white font-serif outline-none" contenteditable="true">DAFTAR ISI SKRIPSI</h1>')
        // Ensure entries container is editable
        ->and($html)->toContain('<div class="toc-entries space-y-1 font-serif" contenteditable="true">')
        // Ensure links and pages are editable
        ->and($html)->toContain('contenteditable="true">BAB I PENDAHULUAN</a>')
        ->and($html)->toContain('contenteditable="true">1</span>')
        // Ensure action bar has delete button
        ->and($html)->toContain('btn-toc-delete');
});

test('black-box: editor view renders progressive document chunk loader UI elements', function () {
    $response = $this->get('/editor');
    $response->assertStatus(200);

    // Verify chunk loader elements exist in DOM
    $response->assertSee('id="document-chunk-progress"', false);
    $response->assertSee('id="chunk-progress-label"', false);
    $response->assertSee('id="chunk-progress-percent"', false);
    $response->assertSee('id="chunk-progress-bar"', false);
    $response->assertSee('id="chunk-progress-page-count"', false);
    $response->assertSee('Memuat lembar dokumen secara bertahap...', false);
});

test('black-box: document engine client source provides chunk rendering system and session cancellation', function () {
    $sourceEngineJs = file_get_contents(resource_path('js/editor/document-engine.js'));

    // Check chunk system methods
    expect($sourceEngineJs)->toContain('createPageSheetElement(');
    expect($sourceEngineJs)->toContain('createPageGapElement(');
    expect($sourceEngineJs)->toContain('showChunkProgress(');
    expect($sourceEngineJs)->toContain('hideChunkProgress(');
    expect($sourceEngineJs)->toContain('ensureChunkLoadingFooter(');
    expect($sourceEngineJs)->toContain('flushChunkQueue()');
    expect($sourceEngineJs)->toContain('currentChunkSession');
    expect($sourceEngineJs)->toContain('isChunkLoading');
    expect($sourceEngineJs)->toContain('document:chunk-progress');
    expect($sourceEngineJs)->toContain('document:chunk-complete');
});

test('black-box: document engine client source prevents call stack overflow and guarantees smooth chunk streaming from top to bottom', function () {
    $sourceEngineJs = file_get_contents(resource_path('js/editor/document-engine.js'));
    $sourceImporterJs = file_get_contents(resource_path('js/editor/docx-importer.js'));
    $sourceAppJs = file_get_contents(resource_path('js/app.js'));

    // Verify DocxImporter syntax sanity
    expect($sourceImporterJs)->not->toContain('$fallback')
        ->and($sourceImporterJs)->toContain("if (fallback === 'serif')");

    // Verify non-recursive Mammoth transformDocument
    expect($sourceEngineJs)->toContain('const transformElement = (root) => {')
        ->and($sourceEngineJs)->toContain('while (stack.length > 0)');

    // Verify non-interrupting getContent during chunk streaming
    expect($sourceEngineJs)->toContain('if (this.isChunkLoading && this.pendingChunks && this.lastLoadedFullHtml)');

    // Verify app.js outline navigation handles pending chunks gracefully
    expect($sourceAppJs)->toContain('if (!targetEl && window.documentEngine && window.documentEngine.isChunkLoading)');
});
