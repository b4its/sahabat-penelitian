<?php

namespace Tests\Unit;

use App\Services\DocumentService;
use App\Services\DocxImporter;
use Illuminate\Http\UploadedFile;

/*
|--------------------------------------------------------------------------
| White Box Unit Tests: DocumentService
|--------------------------------------------------------------------------
| Testing internal state machines, format detection paths, sanitizers,
| markdown converter, statistics calculations, and edge cases.
*/

beforeEach(function () {
    $this->service = new DocumentService;
});

test('white-box: detectFormat accurately resolves all format branches', function () {
    // By filename extension
    expect($this->service->detectFormat('naskah.docx'))->toBe('docx')
        ->and($this->service->detectFormat('naskah.doc'))->toBe('docx')
        ->and($this->service->detectFormat('README.md'))->toBe('markdown')
        ->and($this->service->detectFormat('notes.markdown'))->toBe('markdown')
        ->and($this->service->detectFormat('index.html'))->toBe('html')
        ->and($this->service->detectFormat('page.htm'))->toBe('html')
        ->and($this->service->detectFormat('plain.txt'))->toBe('txt')
        ->and($this->service->detectFormat('data.json'))->toBe('json');

    // By MIME type fallback
    expect($this->service->detectFormat('unknown_file', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'))->toBe('docx')
        ->and($this->service->detectFormat('unknown_file', 'text/html'))->toBe('html')
        ->and($this->service->detectFormat('unknown_file', 'text/markdown'))->toBe('markdown')
        ->and($this->service->detectFormat('unknown_file', 'application/json'))->toBe('json');

    // By content sniffing fallback
    expect($this->service->detectFormat('file_tanpa_ekstensi', null, '{"title": "Test"}'))->toBe('json')
        ->and($this->service->detectFormat('file_tanpa_ekstensi', null, '<h1>Judul</h1><p>Teks</p>'))->toBe('html')
        ->and($this->service->detectFormat('file_tanpa_ekstensi', null, "# Judul Markdown\n\nIsi"))->toBe('markdown');

    // Default fallback
    expect($this->service->detectFormat('tanpa_ekstensi', null, 'hanya teks biasa'))->toBe('txt');
});

test('white-box: sanitizeHtml eliminates XSS vectors while preserving rich text markup', function () {
    $malicious = <<<'HTML'
        <div>
            <h1>Judul Aman</h1>
            <script>alert("XSS")</script>
            <script type="text/javascript">window.location="http://evil.com"</script>
            <p onclick="alert(1)" onmouseover="evil()">Paragraf aman <a href="javascript:alert(2)">Tautan Berbahaya</a></p>
            <img src="safe.jpg" onerror="alert(3)" alt="Aman" />
            <iframe src="http://evil.com"></iframe>
            <style>body { display: none; }</style>
            <table>
                <thead><tr><th>Kolom 1</th></tr></thead>
                <tbody><tr><td>Data 1</td></tr></tbody>
            </table>
        </div>
HTML;

    $sanitized = $this->service->sanitizeHtml($malicious);

    expect($sanitized)->not->toContain('<script>')
        ->and($sanitized)->not->toContain('alert(')
        ->and($sanitized)->not->toContain('onclick')
        ->and($sanitized)->not->toContain('onmouseover')
        ->and($sanitized)->not->toContain('onerror')
        ->and($sanitized)->not->toContain('javascript:')
        ->and($sanitized)->not->toContain('<iframe>')
        ->and($sanitized)->not->toContain('<style>')
        ->and($sanitized)->toContain('<h1>Judul Aman</h1>')
        ->and($sanitized)->toContain('<img src="safe.jpg"')
        ->and($sanitized)->toContain('<table>')
        ->and($sanitized)->toContain('<td>Data 1</td>');
});

test('white-box: parseMarkdown parses headings, lists, blockquotes, code blocks, and inline styles', function () {
    $markdown = <<<'MD'
# Judul 1
## Subjudul 2
### Bagian 3

> Ini adalah kutipan inspiratif.

```
function test() {
    return true;
}
```

- Poin satu
- Poin dua

1. Langkah satu
2. Langkah dua

Teks dengan **tebal**, *miring*, ~~coret~~, dan `kode inline`.
[Tautan Google](https://google.com)
MD;

    $html = $this->service->parseMarkdown($markdown);

    expect($html)->toContain('<h1>Judul 1</h1>')
        ->and($html)->toContain('<h2>Subjudul 2</h2>')
        ->and($html)->toContain('<h3>Bagian 3</h3>')
        ->and($html)->toContain('<blockquote')
        ->and($html)->toContain('<pre')
        ->and($html)->toContain('<ul')
        ->and($html)->toContain('<ol')
        ->and($html)->toContain('<strong>tebal</strong>')
        ->and($html)->toContain('<em>miring</em>')
        ->and($html)->toContain('<del>coret</del>')
        ->and($html)->toContain('<code')
        ->and($html)->toContain('<a href="https://google.com"');
});

test('white-box: convertToMarkdown produces clean markdown from HTML elements', function () {
    $html = <<<'HTML'
<h1>Judul Penting</h1>
<p>Paragraf dengan <strong>teks tebal</strong> dan <em>teks miring</em>.</p>
<blockquote>Sebuah kutipan naskah.</blockquote>
<ul>
    <li>Item satu</li>
    <li>Item dua</li>
</ul>
<a href="https://example.com">Kunjungi Situs</a>
HTML;

    $md = $this->service->convertToMarkdown($html);

    expect($md)->toContain('# Judul Penting')
        ->and($md)->toContain('**teks tebal**')
        ->and($md)->toContain('*teks miring*')
        ->and($md)->toContain('> Sebuah kutipan naskah.')
        ->and($md)->toContain('- Item satu')
        ->and($md)->toContain('[Kunjungi Situs](https://example.com)');
});

test('white-box: convertToPlainText strips HTML tags and normalizes whitespace', function () {
    $html = '<h1>Judul</h1><p>Paragraf 1</p><p>Paragraf 2</p>';
    $plain = $this->service->convertToPlainText($html);

    expect($plain)->toContain('Judul')
        ->and($plain)->toContain('Paragraf 1')
        ->and($plain)->toContain('Paragraf 2')
        ->and($plain)->not->toContain('<');
});

test('white-box: calculateStats computes accurate words, characters, paragraphs, reading time, and pages', function () {
    $html = '<h1>Judul Dokumen</h1><p>Satu dua tiga empat lima enam tujuh delapan sembilan sepuluh.</p>';
    $stats = $this->service->calculateStats($html);

    expect($stats['word_count'])->toBeGreaterThanOrEqual(10)
        ->and($stats['char_count'])->toBeGreaterThan(20)
        ->and($stats['reading_time_minutes'])->toBeGreaterThanOrEqual(1)
        ->and($stats['page_estimate'])->toBeGreaterThanOrEqual(1);
});

test('white-box: calculateStats handles empty and whitespace content without crashing', function () {
    $statsEmpty = $this->service->calculateStats('');
    expect($statsEmpty['word_count'])->toBe(0)
        ->and($statsEmpty['char_count'])->toBe(0)
        ->and($statsEmpty['reading_time_minutes'])->toBe(1);

    $statsSpaces = $this->service->calculateStats("   \n\n\t   ");
    expect($statsSpaces['word_count'])->toBe(0);
});

test('white-box: extractTitle prioritizes H1 tag over filename', function () {
    $htmlWithH1 = '<h1>Strategi Transformasi Digital</h1><p>Konten</p>';
    $title1 = $this->service->extractTitle('file_lama.docx', $htmlWithH1);
    expect($title1)->toBe('Strategi Transformasi Digital');

    $htmlWithoutH1 = '<p>Hanya paragraf tanpa judul utama.</p>';
    $title2 = $this->service->extractTitle('rencana_kerja_tahunan_2026.docx', $htmlWithoutH1);
    expect($title2)->toBe('Rencana Kerja Tahunan 2026');

    $title3 = $this->service->extractTitle('', '<p>Kosong</p>');
    expect($title3)->toBe('Dokumen Tanpa Judul');
});

test('white-box: adaptDocumentStructure promotes chapters and configures academic skripsi margins', function () {
    $inputHtml = <<<'HTML'
        <p>SKRIPSI</p>
        <p>PENELITIAN LIMBAH CAIR</p>
        <p>Oleh : Peneliti</p>
        <p>NIM. 123456</p>
        <p>UNIVERSITAS INDONESIA</p>
        <p>2026</p>
        <p>BAB I PENDAHULUAN</p>
        <p>1.1 Latar Belakang Masalah</p>
        <p>Paragraf isi latar belakang permasalahan.</p>
        <p>1.1.1 Identifikasi Masalah</p>
        <p>Sub poin identifikasi.</p>
HTML;

    $result = $this->service->adaptDocumentStructure($inputHtml, 'skripsi_penelitian.docx');

    expect($result['presets']['is_academic'])->toBeTrue()
        ->and($result['presets']['margins'])->toBe('skripsi')
        ->and($result['presets']['margin_details']['top'])->toBe('4cm')
        ->and($result['presets']['margin_details']['left'])->toBe('4cm')
        ->and($result['presets']['margin_details']['right'])->toBe('3cm')
        ->and($result['presets']['margin_details']['bottom'])->toBe('3cm')
        ->and($result['presets']['font_family'])->toBe('Times New Roman, serif')
        ->and($result['presets']['font_size'])->toBe('12pt')
        ->and($result['presets']['line_spacing'])->toBe('1.5')
        ->and($result['adapted_html'])->toContain('heading-chapter')
        ->and($result['adapted_html'])->toContain('heading-section')
        ->and($result['adapted_html'])->toContain('heading-subsection')
        ->and($result['adapted_html'])->toContain('data-page-break')
        ->and(count($result['headings']))->toBeGreaterThanOrEqual(3);
});

test('white-box: adaptDocumentStructure intelligently merges two-line chapter headings and preliminary pages', function () {
    $inputHtml = <<<'HTML'
        <p>LEMBAR PENGESAHAN</p>
        <p>Disetujui oleh dosen pembimbing.</p>
        <p>BAB II</p>
        <p>LANDASAN TEORI</p>
        <p>2.1 Tinjauan Pustaka Sensor Warna</p>
        <p>Sensor TCS3200 mendeteksi frekuensi spektrum cahaya.</p>
        <p>DAFTAR PUSTAKA</p>
        <p>[1] Smith, J. 2024. IoT Sensors Handbook.</p>
HTML;

    $result = $this->service->adaptDocumentStructure($inputHtml, 'skripsi.docx');

    expect($result['adapted_html'])->toContain('BAB II LANDASAN TEORI')
        ->and($result['adapted_html'])->toContain('LEMBAR PENGESAHAN')
        ->and($result['adapted_html'])->toContain('DAFTAR PUSTAKA')
        ->and($result['headings'])->toContain([
            'id' => 'heading-1',
            'level' => 1,
            'text' => 'BAB II LANDASAN TEORI',
        ]);
});

test('white-box: adaptDocumentStructure separates academic preliminaries like THESIS, LEMBAR PERSETUJUAN, and PERNYATAAN KEASLIAN with page breaks', function () {
    $inputHtml = <<<'HTML'
        <p>SKRIPSI</p>
        <p>SAMPUL DEPAN</p>
        <p>2026</p>
        <p>THESIS</p>
        <p>IMPLEMENTATION OF COLOR SENSORS</p>
        <p>PERNYATAAN KEASLIAN SKRIPSI</p>
        <p>Saya menyatakan bahwa karya ini adalah asli.</p>
        <p>LEMBAR PERSETUJUAN PEMBIMBING</p>
        <p>Disetujui untuk diujikan.</p>
        <p>BAB 1 PENDAHULUAN</p>
        <p>1.1 Latar Belakang Masalah</p>
        <p>Latar belakang skripsi.</p>
        <p>BAB 2 TINJAUAN PUSTAKA</p>
        <p>2.1 Kajian Pustaka</p>
HTML;

    $result = $this->service->adaptDocumentStructure($inputHtml, 'SKRIPSI 236651071 BAITS RIKA SAPUTRA.docx');

    expect($result['adapted_html'])->toContain('THESIS')
        ->and($result['adapted_html'])->toContain('PERNYATAAN KEASLIAN SKRIPSI')
        ->and($result['adapted_html'])->toContain('LEMBAR PERSETUJUAN PEMBIMBING')
        ->and($result['adapted_html'])->toContain('BAB 1 PENDAHULUAN')
        ->and($result['adapted_html'])->toContain('BAB 2 TINJAUAN PUSTAKA')
        ->and(substr_count($result['adapted_html'], 'data-page-break="true"'))->toBeGreaterThanOrEqual(4);
});

test('white-box: adaptDocumentStructure converts Table of Contents lines to .toc-entry without creating false headings', function () {
    $inputHtml = <<<'HTML'
        <p>SKRIPSI</p>
        <p>PENERAPAN ENSEMBLE LEARNING UNTUK KLASIFIKASI KUALITAS LIMBAH CAIR TEKSTIL</p>
        <p>Oleh :</p>
        <p>BAITS RIKA SAPUTRA</p>
        <p>NIM. 23.6651.071</p>
        <p>POLITEKNIK NEGERI SAMARINDA</p>
        <p>2026</p>
        <p>DAFTAR ISI</p>
        <p>BAB 1 PENDAHULUAN ................................................ 1</p>
        <p>1.1 Latar Belakang ................................................ 1</p>
        <p>1.2 Rumusan Masalah ................................................ 3</p>
        <p>BAB 2 TINJAUAN PUSTAKA ................................................ 6</p>
        <p>2.1 Landasan Teori ................................................ 6</p>
        <p>2.1.1 Ensemble Learning ................................................ 7</p>
        <p>BAB 1 PENDAHULUAN</p>
        <p>1.1 Latar Belakang</p>
        <p>Air limbah industri tekstil merupakan persoalan lingkungan serius.</p>
HTML;

    $result = $this->service->adaptDocumentStructure($inputHtml, 'skripsi.docx');

    // TOC entries must be formatted with class toc-entry and dotted borders
    expect($result['adapted_html'])->toContain('toc-entry')
        ->and($result['adapted_html'])->toContain('border-dotted')
        ->and($result['adapted_html'])->toContain('toc-page');

    // Headings list MUST NOT contain TOC dot leaders or author metadata
    $headingTexts = array_column($result['headings'], 'text');
    expect($headingTexts)->not->toContain('BAB 1 PENDAHULUAN ................................................ 1')
        ->and($headingTexts)->not->toContain('1.1 Latar Belakang ................................................ 1')
        ->and($headingTexts)->not->toContain('BAITS RIKA SAPUTRA')
        ->and($headingTexts)->not->toContain('NIM. 23.6651.071')
        ->and($headingTexts)->toContain('DAFTAR ISI')
        ->and($headingTexts)->toContain('BAB 1 PENDAHULUAN')
        ->and($headingTexts)->toContain('1.1 Latar Belakang');
});

test('white-box: adaptDocumentStructure separates multi-cover pages with explicit page breaks and preserves <br> lines without squishing', function () {
    $multiCoverHtml = <<<'HTML'
        <p><strong>SKRIPSI</strong></p>
        <p><strong>PENERAPAN ENSEMBLE LEARNING UNTUK KLASIFIKASI KUALITAS LIMBAH CAIR TEKSTIL</strong></p>
        <figure><img src="data:image/png;base64,abc" /></figure>
        <p>Oleh :<br />BAITS RIKA SAPUTRA<br />NIM. 23.6651.071</p>
        <p>PROGRAM STUDI SARJANA TERAPAN TEKNIK INFORMATIKA MULTIMEDIA<br />POLITEKNIK NEGERI SAMARINDA<br />2026</p>
        <div class="page-break" data-page-break="true"><span class="page-break-tag">Pemisah Halaman</span></div>
        <p><strong>SKRIPSI</strong></p>
        <p><strong>PENERAPAN ENSEMBLE LEARNING</strong></p>
        <p>Sebagai salah satu syarat untuk menyelesaikan Program Studi Diploma IV<br />Jurusan Teknologi Informasi Politeknik Negeri Samarinda</p>
        <p>Oleh :<br />BAITS RIKA SAPUTRA<br />NIM. 23.6651.071</p>
        <p>POLITEKNIK NEGERI SAMARINDA<br />2026</p>
        <div class="page-break" data-page-break="true"><span class="page-break-tag">Pemisah Halaman</span></div>
        <p><strong>THESIS</strong></p>
        <p><strong>IMPLEMENTATION OF ENSEMBLE LEARNING</strong></p>
        <p>Submitted as a partial fulfillment of the requirements<br />Samarinda State Polytechnic</p>
        <p>By :<br />BAITS RIKA SAPUTRA<br />NIM. 23.6651.071</p>
        <p>STATE POLYTECHNIC<br />2026</p>
        <div class="page-break" data-page-break="true"><span class="page-break-tag">Pemisah Halaman</span></div>
        <p><strong>PERNYATAAN KEASLIAN SKRIPSI</strong></p>
        <p>Saya menyatakan dengan sesungguhnya bahwa Skripsi ini adalah asli karya saya.</p>
HTML;

    $result = $this->service->adaptDocumentStructure($multiCoverHtml, 'skripsi.docx');

    // Page breaks must be preserved between the covers
    expect(substr_count($result['adapted_html'], 'data-page-break="true"'))->toBeGreaterThanOrEqual(3)
        // Must contain cover styling
        ->and($result['adapted_html'])->toContain('cover-badge')
        ->and($result['adapted_html'])->toContain('cover-title')
        ->and($result['adapted_html'])->toContain('cover-subtitle')
        ->and($result['adapted_html'])->toContain('cover-author-label')
        ->and($result['adapted_html'])->toContain('cover-institution')
        // Lines with <br /> must preserve <br /> and NOT squish into "BAITS RIKA SAPUTRANIM"
        ->and($result['adapted_html'])->not->toContain('BAITS RIKA SAPUTRANIM')
        ->and($result['adapted_html'])->toContain('NIM. 23.6651.071')
        ->and($result['adapted_html'])->toContain('heading-preliminary');

    // Headings list must NOT contain the 3 covers as headings
    $headingTexts = array_column($result['headings'], 'text');
    expect($headingTexts)->not->toContain('SKRIPSI')
        ->and($headingTexts)->not->toContain('THESIS')
        ->and($headingTexts)->toContain('PERNYATAAN KEASLIAN SKRIPSI');
});

test('white-box: adaptDocumentStructure normalizes chapter titles with <br> or missing spacing', function () {
    $inputHtml = <<<'HTML'
        <p>SKRIPSI</p>
        <p>JUDUL PENELITIAN</p>
        <p>2026</p>
        <p><strong>BAB 1<br />PENDAHULUAN</strong></p>
        <p>1.1 Latar Belakang</p>
        <p>Isi bab 1.</p>
        <p><strong>BAB 2TINJAUAN PUSTAKA</strong></p>
        <p>2.1 Kajian Teori</p>
        <p>Isi bab 2.</p>
        <p><strong>BAB 3METODE PENELITIAN</strong></p>
        <p>3.1 Metode</p>
        <p>Isi bab 3.</p>
HTML;

    $result = $this->service->adaptDocumentStructure($inputHtml, 'skripsi.docx');
    $headingTexts = array_column($result['headings'], 'text');

    expect($headingTexts)->toContain('BAB 1 PENDAHULUAN')
        ->and($headingTexts)->toContain('BAB 2 TINJAUAN PUSTAKA')
        ->and($headingTexts)->toContain('BAB 3 METODE PENELITIAN')
        ->and($headingTexts)->not->toContain('BAB 2TINJAUAN PUSTAKA')
        ->and($headingTexts)->not->toContain('BAB 3METODE PENELITIAN');
});

test('white-box: extractDocxText extracts explicit page breaks and line breaks from real docx file', function () {
    if (! file_exists('storage/test.docx')) {
        $this->markTestSkipped('storage/test.docx not present');
    }

    $reflector = new \ReflectionClass($this->service);
    $method = $reflector->getMethod('extractDocxText');
    $method->setAccessible(true);
    $html = $method->invoke($this->service, 'storage/test.docx');

    expect($html)->toContain('data-page-break="true"')
        ->and($html)->toContain('<br />')
        ->and(substr_count($html, 'data-page-break="true"'))->toBeGreaterThanOrEqual(15);
});

test('white-box: extractDocxText extracts real docx tables preserving table, tr, td, and inner paragraphs', function () {
    $tableDocx = 'node_modules/mammoth/test/test-data/tables.docx';
    expect(file_exists($tableDocx))->toBeTrue();

    $reflector = new \ReflectionClass($this->service);
    $method = $reflector->getMethod('extractDocxText');
    $method->setAccessible(true);
    $html = $method->invoke($this->service, $tableDocx);

    expect($html)->toContain('<table')
        ->and($html)->toContain('<tbody>')
        ->and($html)->toContain('<tr>')
        ->and($html)->toContain('<td')
        ->and($html)->toContain('Top left')
        ->and($html)->toContain('Top right')
        ->and($html)->toContain('Bottom left')
        ->and($html)->toContain('Bottom right')
        ->and($html)->toContain('<p>Above</p>')
        ->and($html)->toContain('<p>Below</p>');
});

test('white-box: extractDocxText extracts real docx underlines and strikethroughs with exact typography', function () {
    $underlineDocx = 'node_modules/mammoth/test/test-data/underline.docx';
    $strikeDocx = 'node_modules/mammoth/test/test-data/strikethrough.docx';

    $reflector = new \ReflectionClass($this->service);
    $method = $reflector->getMethod('extractDocxText');
    $method->setAccessible(true);

    $uHtml = $method->invoke($this->service, $underlineDocx);
    expect($uHtml)->toContain('<u>')
        ->and($uHtml)->toContain('Sunset')
        ->and($uHtml)->toContain('<strong>');

    $sHtml = $method->invoke($this->service, $strikeDocx);
    expect($sHtml)->toContain('<s>')
        ->and($sHtml)->toContain('Today&#039;s Special: Salmon')
        ->and($sHtml)->toContain('Sold out');
});

test('white-box: extractDocxText extracts real docx lists preserving ul and li structure', function () {
    $listDocx = 'node_modules/mammoth/test/test-data/simple-list.docx';

    $reflector = new \ReflectionClass($this->service);
    $method = $reflector->getMethod('extractDocxText');
    $method->setAccessible(true);

    $html = $method->invoke($this->service, $listDocx);
    expect($html)->toContain('<ul')
        ->and($html)->toContain('<li>Apple</li>')
        ->and($html)->toContain('<li>Banana</li>');
});

test('white-box: extractDocxText extracts real docx images as base64 data URIs', function () {
    $picDocx = 'node_modules/mammoth/test/test-data/tiny-picture.docx';

    $reflector = new \ReflectionClass($this->service);
    $method = $reflector->getMethod('extractDocxText');
    $method->setAccessible(true);

    $html = $method->invoke($this->service, $picDocx);
    expect($html)->toContain('<img src="data:image/png;base64,')
        ->and($html)->toContain('alt="Dokumen Gambar"');
});

test('white-box: adaptDocumentStructure preserves text-center, text-right, text-justify alignments without destroying them', function () {
    $alignedHtml = <<<'HTML'
        <p class="text-center" style="text-align: center;">LAPORAN LABA RUGI TAHUN 2026</p>
        <p class="text-justify" style="text-align: justify;">Berikut ini adalah ringkasan performa finansial kuartal pertama tahun berjalan dengan hasil yang sangat memuaskan bagi para pemegang saham perusahaan.</p>
        <p class="text-right" style="text-align: right;">Jakarta, 10 November 2026</p>
        <p class="text-right" style="text-align: right;">Direktur Keuangan</p>
HTML;

    $result = $this->service->adaptDocumentStructure($alignedHtml, 'laporan_keuangan.docx');

    expect($result['adapted_html'])->toContain('text-center')
        ->and($result['adapted_html'])->toContain('class="text-right')
        ->and($result['adapted_html'])->toContain('LAPORAN LABA RUGI')
        ->and($result['adapted_html'])->toContain('Jakarta, 10 November 2026')
        ->and($result['presets']['is_academic'])->toBeFalse();
});

test('white-box: adaptDocumentStructure does not falsely flag general documents with tables or letters as academic skripsi', function () {
    $generalReportHtml = <<<'HTML'
        <h1>SURAT KEPUTUSAN DIREKSI</h1>
        <p>Berdasarkan evaluasi kinerja semester kedua Politeknik dan Universitas mitra kerja sama.</p>
        <table class="w-full border-collapse">
            <thead><tr><th>No</th><th>Departemen</th><th>Status</th></tr></thead>
            <tbody><tr><td>1</td><td>R&D</td><td>Lulus</td></tr></tbody>
        </table>
        <p class="text-right">Tertanda,</p>
        <p class="text-right">Manajemen</p>
HTML;

    $result = $this->service->adaptDocumentStructure($generalReportHtml, 'sk_direksi.docx');

    expect($result['presets']['is_academic'])->toBeFalse()
        ->and($result['presets']['margins'])->toBe('normal')
        ->and($result['adapted_html'])->toContain('<table')
        ->and($result['adapted_html'])->toContain('class="text-right')
        ->and($result['adapted_html'])->not->toContain('cover-badge')
        ->and($result['adapted_html'])->not->toContain('cover-title');
});

test('white-box: DocxImporter extracts header and footer text while removing PAGE instruction codes', function () {
    $zipPath = tempnam(sys_get_temp_dir(), 'docx_hdr_').'.docx';
    $zip = new \ZipArchive;
    $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

    // Header XML
    $hdrXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:hdr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:p><w:r><w:t>Kementerian Pendidikan dan Riset</w:t></w:r></w:p>
</w:hdr>
XML;
    $zip->addFromString('word/header1.xml', $hdrXml);

    // Footer XML with PAGE field code
    $ftrXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:ftr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:p>
        <w:r><w:t>Dokumen Rahasia Perusahaan</w:t></w:r>
        <w:r><w:t>PAGE \* MERGEFORMAT 1</w:t></w:r>
    </w:p>
</w:ftr>
XML;
    $zip->addFromString('word/footer1.xml', $ftrXml);

    // Document XML
    $docXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:p><w:r><w:t>Isi Dokumen Utama</w:t></w:r></w:p>
    </w:body>
</w:document>
XML;
    $zip->addFromString('word/document.xml', $docXml);
    $zip->close();

    $importer = new DocxImporter;
    $meta = $importer->parseWithMetadata($zipPath);

    expect($meta['header_text'])->toBe('Kementerian Pendidikan dan Riset')
        ->and($meta['footer_text'])->toBe('Dokumen Rahasia Perusahaan 1')
        ->and($meta['html'])->toContain('Isi Dokumen Utama');

    @unlink($zipPath);
});

test('white-box: parseFile returns extracted header_text and footer_text for DOCX', function () {
    $zipPath = tempnam(sys_get_temp_dir(), 'docx_meta_').'.docx';
    $zip = new \ZipArchive;
    $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

    $hdrXml = '<?xml version="1.0" encoding="UTF-8"?><w:hdr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:p><w:r><w:t>Header Laporan Tahunan</w:t></w:r></w:p></w:hdr>';
    $ftrXml = '<?xml version="1.0" encoding="UTF-8"?><w:ftr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:p><w:r><w:t>Footer Hak Cipta 2026</w:t></w:r></w:p></w:ftr>';
    $docXml = '<?xml version="1.0" encoding="UTF-8"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>Konten Dokumen</w:t></w:r></w:p></w:body></w:document>';

    $zip->addFromString('word/header1.xml', $hdrXml);
    $zip->addFromString('word/footer1.xml', $ftrXml);
    $zip->addFromString('word/document.xml', $docXml);
    $zip->close();

    $file = new UploadedFile($zipPath, 'laporan_tahunan.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);
    $response = $this->service->parseFile($file);

    expect($response['success'])->toBeTrue()
        ->and($response['header_text'])->toBe('Header Laporan Tahunan')
        ->and($response['footer_text'])->toBe('Footer Hak Cipta 2026')
        ->and($response['content_html'])->toContain('Konten Dokumen');

    @unlink($zipPath);
});

test('white-box: adaptDocumentStructure registers headings with id and level for outline and running headers', function () {
    $html = <<<'HTML'
        <h1>BAB 1 PENDAHULUAN</h1>
        <p>Pengantar bab 1.</p>
        <h2>1.1 Latar Belakang</h2>
        <p>Latar belakang masalah.</p>
        <h3>1.1.1 Identifikasi Masalah</h3>
        <p>Identifikasi detil.</p>
        <h1>BAB 2 KAJIAN PUSTAKA</h1>
        <p>Pengantar bab 2.</p>
HTML;

    $result = $this->service->adaptDocumentStructure($html, 'skripsi.docx');

    expect($result['headings'])->toHaveCount(4)
        ->and($result['headings'][0]['text'])->toBe('BAB 1 PENDAHULUAN')
        ->and($result['headings'][0]['level'])->toBe(1)
        ->and($result['headings'][1]['text'])->toBe('1.1 Latar Belakang')
        ->and($result['headings'][1]['level'])->toBe(2)
        ->and($result['headings'][2]['text'])->toBe('1.1.1 Identifikasi Masalah')
        ->and($result['headings'][2]['level'])->toBe(3)
        ->and($result['headings'][3]['text'])->toBe('BAB 2 KAJIAN PUSTAKA')
        ->and($result['headings'][3]['level'])->toBe(1);
});

test('white-box: sanitizeHtml and format conversion faithfully preserves mathematical, scientific, and typography symbols', function () {
    $rawSymbolsHtml = '<p>Formula: ∑ x_i ≈ 3.14 (π) ± 0.05, temp: 25℃, rights: © 2026, arrow: &rarr; &le; &ge; &ne;</p>';
    $sanitized = $this->service->sanitizeHtml($rawSymbolsHtml);

    expect($sanitized)->toContain('∑')
        ->and($sanitized)->toContain('≈')
        ->and($sanitized)->toContain('π')
        ->and($sanitized)->toContain('±')
        ->and($sanitized)->toContain('℃')
        ->and($sanitized)->toContain('©');

    $plainText = $this->service->convertToPlainText($sanitized);
    expect($plainText)->toContain('∑')
        ->and($plainText)->toContain('π')
        ->and($plainText)->toContain('±');
});

test('white-box: calculateStats handles complex academic citations and nested lists accurately', function () {
    $academicHtml = <<<'HTML'
        <h1>BAB I PENDAHULUAN</h1>
        <p>Berdasarkan penelitian sebelumnya (Sugiyono, 2024), metodologi ini sangat efektif.</p>
        <p>Berikut adalah poin-poin utama:</p>
        <ul>
            <li>Analisis data kuantitatif</li>
            <li>Pengujian validitas instrumen</li>
            <li>Evaluasi model machine learning</li>
        </ul>
        <h3>Daftar Pustaka</h3>
        <p>Sugiyono. (2024). Metode Penelitian Kualitatif, Kuantitatif, dan R&D. Bandung: Alfabeta.</p>
HTML;

    $stats = $this->service->calculateStats($academicHtml);

    expect($stats['word_count'])->toBeGreaterThan(20)
        ->and($stats['char_count'])->toBeGreaterThan(150)
        ->and($stats['paragraph_count'])->toBeGreaterThanOrEqual(4)
        ->and($stats['reading_time_minutes'])->toBeGreaterThanOrEqual(1);
});

test('white-box: DocxImporter extracts header and footer referenced via relationships rId and titlePg', function () {
    $zipPath = tempnam(sys_get_temp_dir(), 'docx_rel_hf_').'.docx';
    $zip = new \ZipArchive;
    $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

    // Relationships in document.xml.rels
    $relsXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rIdHeaderDefault" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/header" Target="header2.xml"/>
    <Relationship Id="rIdFooterDefault" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/footer" Target="footer2.xml"/>
</Relationships>
XML;
    $zip->addFromString('word/_rels/document.xml.rels', $relsXml);

    // Header 1 (first page empty) and Header 2 (running header)
    $zip->addFromString('word/header1.xml', '<?xml version="1.0" encoding="UTF-8"?><w:hdr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"/>');
    $zip->addFromString('word/header2.xml', '<?xml version="1.0" encoding="UTF-8"?><w:hdr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:p><w:r><w:t>UNIVERSITAS NEGERI SEMARANG - TEKNIK INFORMATIKA</w:t></w:r></w:p></w:hdr>');

    // Footer 1 and Footer 2
    $zip->addFromString('word/footer2.xml', '<?xml version="1.0" encoding="UTF-8"?><w:ftr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:p><w:r><w:t>Laporan Tugas Akhir 2026</w:t></w:r><w:r><w:t>PAGE \* MERGEFORMAT</w:t></w:r></w:p></w:ftr>');

    // Document with headerReference, footerReference, and titlePg
    $docXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <w:body>
        <w:p><w:r><w:t>Halaman Isi Skripsi</w:t></w:r></w:p>
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

    $importer = new DocxImporter;
    $meta = $importer->parseWithMetadata($zipPath);

    expect($meta['header_text'])->toBe('UNIVERSITAS NEGERI SEMARANG - TEKNIK INFORMATIKA')
        ->and($meta['footer_text'])->toBe('Laporan Tugas Akhir 2026')
        ->and($meta['different_first_page'])->toBeTrue()
        ->and($importer->getDifferentFirstPage())->toBeTrue();

    @unlink($zipPath);
});

test('white-box: exportDocument embeds Word MsoHeader and MsoFooter when headerText and footerText are specified', function () {
    $exported = $this->service->exportDocument(
        'Laporan Riset AI',
        '<p>Konten hasil riset</p>',
        'doc',
        'KEMENTERIAN RISET DAN TEKNOLOGI',
        'Hak Cipta 2026 Dilindungi'
    );

    expect($exported['mime'])->toBe('application/msword; charset=UTF-8')
        ->and($exported['content'])->toContain('mso-header: h1;')
        ->and($exported['content'])->toContain('mso-footer: f1;')
        ->and($exported['content'])->toContain('KEMENTERIAN RISET DAN TEKNOLOGI')
        ->and($exported['content'])->toContain('Hak Cipta 2026 Dilindungi')
        ->and($exported['content'])->not->toContain('Halaman <span style="mso-field-code: PAGE "></span> dari');
});

test('white-box: DocxImporter::formatFontFamily correctly assigns sans-serif, monospace, and serif fallbacks', function () {
    // Sans-serif fonts
    expect(DocxImporter::formatFontFamily('Arial'))->toBe("'Arial', sans-serif")
        ->and(DocxImporter::formatFontFamily('Calibri'))->toBe("'Calibri', sans-serif")
        ->and(DocxImporter::formatFontFamily('Aptos'))->toBe("'Aptos', sans-serif")
        ->and(DocxImporter::formatFontFamily('Segoe UI'))->toBe("'Segoe UI', sans-serif")
        ->and(DocxImporter::formatFontFamily('Verdana'))->toBe("'Verdana', sans-serif")
        ->and(DocxImporter::formatFontFamily('Tahoma'))->toBe("'Tahoma', sans-serif")
        ->and(DocxImporter::formatFontFamily('Instrument Sans'))->toBe("'Instrument Sans', sans-serif");

    // Monospace fonts
    expect(DocxImporter::formatFontFamily('Courier New'))->toBe("'Courier New', monospace")
        ->and(DocxImporter::formatFontFamily('Consolas'))->toBe("'Consolas', monospace");

    // Serif fonts
    expect(DocxImporter::formatFontFamily('Times New Roman'))->toBe("'Times New Roman', serif")
        ->and(DocxImporter::formatFontFamily('Georgia'))->toBe("'Georgia', serif")
        ->and(DocxImporter::formatFontFamily('Cambria'))->toBe("'Cambria', serif")
        ->and(DocxImporter::formatFontFamily('Garamond'))->toBe("'Garamond', serif");

    // Empty or sanitized inputs
    expect(DocxImporter::formatFontFamily(''))->toBe('')
        ->and(DocxImporter::formatFontFamily("'Calibri'"))->toBe("'Calibri', sans-serif");
});

test('white-box: DocxImporter resolves theme colors and extracts styles with font family and colors', function () {
    $zipPath = tempnam(sys_get_temp_dir(), 'docx_whitebox_font_').'.docx';
    $zip = new \ZipArchive;
    $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

    // Theme XML defining custom accent colors
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
            <a:accent2><a:srgbClr val="C00000"/></a:accent2>
        </a:clrScheme>
    </a:themeElements>
</a:theme>
XML;
    $zip->addFromString('word/theme/theme1.xml', $themeXml);

    // Styles XML defining docDefaults and Heading1 style with accent1
    $stylesXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:docDefaults>
        <w:rPrDefault>
            <w:rPr>
                <w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/>
                <w:sz w:val="22"/>
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
    $zip->addFromString('word/styles.xml', $stylesXml);

    // Document XML with a heading using Heading1 style and runs with custom color
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
                <w:t>Teks biasa tanpa warna khusus.</w:t>
            </w:r>
            <w:r>
                <w:rPr>
                    <w:color w:val="C00000" w:themeColor="accent2"/>
                    <w:rFonts w:ascii="Arial"/>
                </w:rPr>
                <w:t>Teks merah penting.</w:t>
            </w:r>
        </w:p>
    </w:body>
</w:document>
XML;
    $zip->addFromString('word/document.xml', $docXml);
    $zip->close();

    $importer = new DocxImporter;
    $meta = $importer->parseWithMetadata($zipPath);

    expect($importer->getDefaultFontFamily())->toBe("'Calibri', sans-serif")
        ->and($meta['default_font_family'])->toBe("'Calibri', sans-serif")
        ->and($meta['html'])->toContain('style="color: #2F5496; font-family: \'Calibri Light\', sans-serif;"')
        ->and($meta['html'])->toContain('color: #C00000;')
        ->and($meta['html'])->toContain('font-family: \'Arial\', sans-serif;')
        ->and(str_contains($meta['html'], 'color: #000000;'))->toBeFalse()
        ->and(str_contains($meta['html'], ", serif;'"))->toBeFalse();

    @unlink($zipPath);
});

test('white-box: adaptDocumentStructure preserves inline heading styles without escaping quotes', function () {
    $html = '<h1 style="color: #2F5496; font-family: \'Calibri Light\', sans-serif;" class="heading-chapter">BAB I PENDAHULUAN</h1><p>Isi bab 1.</p>';
    $adapted = $this->service->adaptDocumentStructure($html, 'laporan.docx');

    expect(str_contains($adapted['adapted_html'], 'style="color: #2F5496; font-family: \'Calibri Light\', sans-serif;"'))->toBeTrue();
    expect(str_contains($adapted['adapted_html'], '&#039;'))->toBeFalse();
    expect(count($adapted['headings']))->toBe(1);
});

test('white-box: isNearWhiteColor accurately identifies near-white colors and avoids suppressing legitimate colors', function () {
    expect(DocxImporter::isNearWhiteColor('#ffffff'))->toBeTrue()
        ->and(DocxImporter::isNearWhiteColor('#fff'))->toBeTrue()
        ->and(DocxImporter::isNearWhiteColor('white'))->toBeTrue()
        ->and(DocxImporter::isNearWhiteColor('#f8f9fa'))->toBeTrue()
        ->and(DocxImporter::isNearWhiteColor('#f3f4f6'))->toBeTrue()
        ->and(DocxImporter::isNearWhiteColor('#fafafa'))->toBeTrue()
        ->and(DocxImporter::isNearWhiteColor('#111827'))->toBeFalse()
        ->and(DocxImporter::isNearWhiteColor('#000000'))->toBeFalse()
        ->and(DocxImporter::isNearWhiteColor('#2F5496'))->toBeFalse()
        ->and(DocxImporter::isNearWhiteColor('#C00000'))->toBeFalse()
        ->and(DocxImporter::isNearWhiteColor(null))->toBeFalse();
});

test('white-box: adaptDocumentStructure guarantees high contrast text and excludes inverted dark-mode classes on white sheets', function () {
    $inputHtml = <<<'HTML'
        <p>SKRIPSI 236661027 ALVIN GRAND PUTRA</p>
        <p>SKRIPSI</p>
        <p>IMPLEMENTASI SISTEM KENDALI KUALITAS AIR PADA PROTOTIPE PENGOLAHAN LIMBAH TEKSTIL</p>
        <p>Oleh :</p>
        <p>ALVIN GRAND PUTRA</p>
        <p>236661027</p>
        <p>PROGRAM STUDI TEKNOLOGI REKAYASA KOMPUTER</p>
        <p>JURUSAN TEKNOLOGI INFORMASI</p>
        <p>POLITEKNIK NEGERI SAMARINDA</p>
        <p>2026</p>
        <p>BAB I PENDAHULUAN</p>
        <p>1.1 Latar Belakang</p>
        <p>1.1.1 Identifikasi Masalah</p>
        <p>Sistem ini dirancang untuk mendeteksi kualitas air secara real-time.</p>
        <p>Daftar Isi .......... 14</p>
HTML;

    $adapted = $this->service->adaptDocumentStructure($inputHtml, 'skripsi_alvin.docx');

    expect($adapted['adapted_html'])->not->toContain('dark:text-gray-100')
        ->and($adapted['adapted_html'])->not->toContain('dark:text-gray-200')
        ->and($adapted['adapted_html'])->not->toContain('dark:text-gray-300')
        ->and($adapted['adapted_html'])->toContain('cover-title')
        ->and($adapted['adapted_html'])->toContain('cover-author-label')
        ->and($adapted['adapted_html'])->toContain('cover-author-nim')
        ->and($adapted['adapted_html'])->toContain('cover-institution')
        ->and($adapted['adapted_html'])->toContain('heading-chapter text-center font-bold text-xl my-6 text-gray-900 uppercase')
        ->and($adapted['adapted_html'])->toContain('heading-section font-bold text-lg my-4 text-gray-900')
        ->and($adapted['adapted_html'])->toContain('heading-subsection font-semibold text-base my-3 text-gray-800');
});

test('white-box: DocxImporter ommlToLatex accurately translates Word OMML elements to LaTeX', function () {
    // 1. Fraction test
    $dom = new \DOMDocument;
    $dom->loadXML('<m:f xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math"><m:num><m:r><m:t>1</m:t></m:r></m:num><m:den><m:r><m:t>2</m:t></m:r></m:den></m:f>');
    $latex = DocxImporter::ommlToLatex($dom->documentElement);
    expect($latex)->toBe('\frac{1}{2}');

    // 2. Superscript and Subscript
    $dom->loadXML('<m:sSup xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math"><m:e><m:r><m:t>x</m:t></m:r></m:e><m:sup><m:r><m:t>2</m:t></m:r></m:sup></m:sSup>');
    expect(DocxImporter::ommlToLatex($dom->documentElement))->toBe('{x}^{2}');

    $dom->loadXML('<m:sSub xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math"><m:e><m:r><m:t>x</m:t></m:r></m:e><m:sub><m:r><m:t>i</m:t></m:r></m:sub></m:sSub>');
    expect(DocxImporter::ommlToLatex($dom->documentElement))->toBe('{x}_{i}');

    // 3. Radical (Square root)
    $dom->loadXML('<m:rad xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math"><m:deg/><m:e><m:r><m:t>b^2 - 4ac</m:t></m:r></m:e></m:rad>');
    expect(DocxImporter::ommlToLatex($dom->documentElement))->toBe('\sqrt{b^2 - 4ac}');

    // 4. Delimiters (brackets)
    $dom->loadXML('<m:d xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math"><m:e><m:r><m:t>a + b</m:t></m:r></m:e></m:d>');
    expect(DocxImporter::ommlToLatex($dom->documentElement))->toBe('\left( a + b \right)');
});

test('white-box: adaptDocumentStructure detects raw LaTeX formula strings and converts to interactive latex-equation blocks', function () {
    $rawFormula = '$$F1\text {-} Score = 2 \times \frac {1 {,} 0 \times 1 {,} 0} {1 {,} 0 + 1 {,} 0} = 100 {,} 00 \% \quad (4.9)$$';
    $inputHtml = '<p>'.$rawFormula.'</p>';

    $adapted = $this->service->adaptDocumentStructure($inputHtml, 'skripsi_formula.docx');

    expect($adapted['adapted_html'])->toContain('latex-equation')
        ->and($adapted['adapted_html'])->toContain('display-equation')
        ->and($adapted['adapted_html'])->toContain('contenteditable="false"')
        ->and($adapted['adapted_html'])->toContain('data-latex=');
});

test('white-box: convertToMarkdown and convertToPlainText preserve latex-equation formulas', function () {
    $formula = 'E = mc^2';
    $html = '<p>Energi relativitas: <span class="latex-equation display-equation" data-latex="'.$formula.'" contenteditable="false">$$'.$formula.'$$</span>.</p>';

    $markdown = $this->service->convertToMarkdown($html);
    expect($markdown)->toContain('$$'.$formula.'$$');

    $plainText = $this->service->convertToPlainText($html);
    expect($plainText)->toContain('$$'.$formula.'$$');
});

test('white-box: app css stylesheet defines custom-variant for class-based dark mode', function () {
    $cssPath = dirname(__DIR__, 2).'/resources/css/app.css';
    $css = file_get_contents($cssPath);
    expect($css)->toContain('@custom-variant dark (&:where(.dark, .dark *));');
});
