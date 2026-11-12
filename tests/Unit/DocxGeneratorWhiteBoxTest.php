<?php

use App\Services\DocxGenerator;
use App\Services\DocxImporter;

test('white-box: DocxGenerator produces valid OPC zip structure and standard Word parts', function () {
    $generator = new DocxGenerator;

    $html = <<<'HTML'
<h1>Laporan Riset Komprehensif</h1>
<p>Paragraf pengantar dengan <strong>tebal</strong>, <em>miring</em>, dan <u>garis bawah</u>.</p>
<ul>
    <li>Poin Satu</li>
    <li>Poin Dua</li>
</ul>
<table class="w-full">
    <tr>
        <th>Header 1</th>
        <th>Header 2</th>
    </tr>
    <tr>
        <td>Isi Sel 1</td>
        <td>Isi Sel 2</td>
    </tr>
</table>
HTML;

    $docxBytes = $generator->generate('Uji OPC', $html, 'Header Riset', 'Footer Rahasia', [
        'paper_size' => 'a4',
        'orientation' => 'portrait',
    ]);

    expect($docxBytes)->toBeString()->not->toBeEmpty();

    // Verify ZIP archive structure
    $tempFile = tempnam(sys_get_temp_dir(), 'test_docx_');
    file_put_contents($tempFile, $docxBytes);

    $zip = new ZipArchive;
    expect($zip->open($tempFile))->toBeTrue();

    // Check standard OOXML parts
    expect($zip->locateName('[Content_Types].xml'))->not->toBeFalse()
        ->and($zip->locateName('_rels/.rels'))->not->toBeFalse()
        ->and($zip->locateName('word/_rels/document.xml.rels'))->not->toBeFalse()
        ->and($zip->locateName('word/document.xml'))->not->toBeFalse()
        ->and($zip->locateName('word/styles.xml'))->not->toBeFalse()
        ->and($zip->locateName('word/numbering.xml'))->not->toBeFalse()
        ->and($zip->locateName('word/header1.xml'))->not->toBeFalse()
        ->and($zip->locateName('word/footer1.xml'))->not->toBeFalse();

    $docXml = $zip->getFromName('word/document.xml');
    expect($docXml)->toContain('w:document')
        ->and($docXml)->toContain('Laporan Riset Komprehensif')
        ->and($docXml)->toContain('Header 1')
        ->and($docXml)->toContain('w:tbl')
        ->and($docXml)->toContain('w:sectPr');

    $headerXml = $zip->getFromName('word/header1.xml');
    expect($headerXml)->toContain('Header Riset');

    $footerXml = $zip->getFromName('word/footer1.xml');
    expect($footerXml)->toContain('Footer Rahasia')
        ->and($footerXml)->toContain('w:instr="PAGE"');

    $zip->close();
    @unlink($tempFile);
});

test('white-box: round-trip fidelity between DocxGenerator and DocxImporter', function () {
    $generator = new DocxGenerator;
    $importer = new DocxImporter;

    $sourceHtml = <<<'HTML'
<h1>Analisis Kinerja Sistem</h1>
<p>Dokumen ini menguji integrasi round-trip antara DocxGenerator dan DocxImporter.</p>
<table>
    <tr>
        <th>Parameter</th>
        <th>Nilai</th>
    </tr>
    <tr>
        <td>Latensi</td>
        <td>12ms</td>
    </tr>
</table>
HTML;

    $docxBytes = $generator->generate('Uji Round Trip', $sourceHtml, 'Header Validasi', 'Footer Validasi');

    $tempFile = tempnam(sys_get_temp_dir(), 'roundtrip_');
    file_put_contents($tempFile, $docxBytes);

    try {
        $parsedHtml = $importer->parse($tempFile);

        expect($parsedHtml)->toContain('Analisis Kinerja Sistem')
            ->and($parsedHtml)->toContain('Dokumen ini menguji integrasi round-trip')
            ->and($parsedHtml)->toContain('Parameter')
            ->and($parsedHtml)->toContain('Nilai')
            ->and($parsedHtml)->toContain('Latensi')
            ->and($parsedHtml)->toContain('12ms');
    } finally {
        @unlink($tempFile);
    }
});

test('white-box: DocxImporter detects and quarantines macro parts safely', function () {
    $importer = new DocxImporter;

    $tempFile = tempnam(sys_get_temp_dir(), 'macro_');
    $zip = new ZipArchive;
    $zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('word/document.xml', '<?xml version="1.0"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>Naskah dengan makro</w:t></w:r></w:p></w:body></w:document>');
    $zip->addFromString('word/vbaProject.bin', 'DUMMY_VBA_BINARY_CODE');
    $zip->close();

    try {
        $meta = $importer->parseWithMetadata($tempFile);

        expect($meta['has_macros'])->toBeTrue()
            ->and($meta['html'])->toContain('Naskah dengan makro')
            ->and(implode(' ', $meta['unsupported_parts']))->toContain('Macro quarantined');
    } finally {
        @unlink($tempFile);
    }
});

test('white-box: DocxImporter rejects path traversal zip slip attempts', function () {
    $importer = new DocxImporter;

    $tempFile = tempnam(sys_get_temp_dir(), 'slip_');
    $zip = new ZipArchive;
    $zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('word/../../../etc/passwd', 'root:x:0:0:root');
    $zip->addFromString('word/document.xml', '<?xml version="1.0"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>Teks aman</w:t></w:r></w:p></w:body></w:document>');
    $zip->close();

    try {
        $meta = $importer->parseWithMetadata($tempFile);

        expect($meta['security_alerts'])->not->toBeEmpty()
            ->and($meta['security_alerts'][0])->toContain('path traversal');
    } finally {
        @unlink($tempFile);
    }
});
