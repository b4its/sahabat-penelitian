<?php

use App\Services\DocxGenerator;

test('white-box: DocxGenerator generates table headers, merged cells, and table centering', function () {
    $generator = new DocxGenerator;

    $html = <<<'HTML'
<table>
    <thead>
        <tr>
            <th colspan="2">Judul Kolom Gabungan</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td rowspan="2">Data Baris Gabungan</td>
            <td>Detail 1</td>
        </tr>
        <tr>
            <td>Detail 2</td>
        </tr>
    </tbody>
</table>
HTML;

    $docxBytes = $generator->generate('Uji Tabel Lanjut', $html);
    $tempFile = tempnam(sys_get_temp_dir(), 'adv_tbl_');
    file_put_contents($tempFile, $docxBytes);

    $zip = new ZipArchive;
    expect($zip->open($tempFile))->toBeTrue();

    $docXml = $zip->getFromName('word/document.xml');
    expect($docXml)->toContain('w:tblHeader')
        ->and($docXml)->toContain('w:gridSpan w:val="2"')
        ->and($docXml)->toContain('w:vMerge w:val="restart"')
        ->and($docXml)->toContain('w:jc w:val="center"');

    $zip->close();
    @unlink($tempFile);
});

test('white-box: DocxGenerator preserves RTL text direction and bidi markers', function () {
    $generator = new DocxGenerator;

    $html = '<p dir="rtl">بسم الله الرحمن الرحيم - Teks Arab RTL.</p>';
    $docxBytes = $generator->generate('Uji RTL', $html);

    $tempFile = tempnam(sys_get_temp_dir(), 'rtl_');
    file_put_contents($tempFile, $docxBytes);

    $zip = new ZipArchive;
    expect($zip->open($tempFile))->toBeTrue();

    $docXml = $zip->getFromName('word/document.xml');
    expect($docXml)->toContain('<w:bidi/>')
        ->and($docXml)->toContain('<w:rtl/>')
        ->and($docXml)->toContain('بسم الله الرحمن الرحيم');

    $zip->close();
    @unlink($tempFile);
});

test('white-box: DocxGenerator handles section breaks and page layouts', function () {
    $generator = new DocxGenerator;

    $html = '<p>Bagian Pertama Portrait</p><div class="section-break"></div><p>Bagian Kedua Landscape</p>';
    $docxBytes = $generator->generate('Uji Multi Section', $html, null, null, [
        'orientation' => 'landscape',
        'paper_size' => 'a4',
    ]);

    $tempFile = tempnam(sys_get_temp_dir(), 'sect_');
    file_put_contents($tempFile, $docxBytes);

    $zip = new ZipArchive;
    expect($zip->open($tempFile))->toBeTrue();

    $docXml = $zip->getFromName('word/document.xml');
    expect($docXml)->toContain('w:type w:val="nextPage"')
        ->and($docXml)->toContain('w:orient="landscape"');

    $zip->close();
    @unlink($tempFile);
});
