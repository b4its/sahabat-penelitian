<?php

test('black-box: exporting document with advanced tables and RTL returns valid docx with all properties', function () {
    $html = <<<'HTML'
<h2>Tabel Kompleks</h2>
<table>
    <tr>
        <th colspan="2">Grup Metrik</th>
    </tr>
    <tr>
        <td>Metrik A</td>
        <td>100%</td>
    </tr>
</table>
<p dir="rtl">نص باللغة العربية مع اتجاه من اليمين إلى اليسار</p>
<div class="section-break"></div>
<p>Halaman pada bagian berikutnya.</p>
HTML;

    $response = $this->postJson('/api/documents/export', [
        'title' => 'Naskah Lanjut',
        'content' => $html,
        'format' => 'docx',
        'orientation' => 'landscape',
    ]);

    $response->assertStatus(200);
    expect($response->headers->get('Content-Type'))->toBe('application/vnd.openxmlformats-officedocument.wordprocessingml.document');

    $bytes = $response->getContent();
    $tempFile = tempnam(sys_get_temp_dir(), 'adv_bb_');
    file_put_contents($tempFile, $bytes);

    $zip = new ZipArchive;
    expect($zip->open($tempFile))->toBeTrue();

    $docXml = $zip->getFromName('word/document.xml');
    expect($docXml)->toContain('w:gridSpan')
        ->and($docXml)->toContain('w:bidi')
        ->and($docXml)->toContain('w:sectPr')
        ->and($docXml)->toContain('w:orient="landscape"');

    $zip->close();
    @unlink($tempFile);
});
