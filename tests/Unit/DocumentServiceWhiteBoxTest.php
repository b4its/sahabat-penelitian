<?php

namespace Tests\Unit;

use App\Services\DocumentService;

/*
|--------------------------------------------------------------------------
| White Box Unit Tests: DocumentService
|--------------------------------------------------------------------------
| Testing internal state machines, format detection paths, sanitizers,
| markdown converter, statistics calculations, and edge cases.
*/

beforeEach(function () {
    $this->service = new DocumentService();
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
    $malicious = <<<HTML
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
    $markdown = <<<MD
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
    $html = <<<HTML
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
    $inputHtml = <<<HTML
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
    $inputHtml = <<<HTML
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
