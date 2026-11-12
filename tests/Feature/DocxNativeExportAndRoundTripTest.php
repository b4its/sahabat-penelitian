<?php

use Illuminate\Http\UploadedFile;

test('black-box: BB05 export returns genuine Open XML docx binary and retains legacy doc format', function () {
    $payload = [
        'title' => 'Riset Standar 2026',
        'content' => '<h1>Metodologi</h1><p>Paragraf isi metodologi riset <strong>terverifikasi</strong>.</p>',
    ];

    // 1. Native DOCX export
    $responseDocx = $this->postJson('/api/documents/export', array_merge($payload, ['format' => 'docx']));
    $responseDocx->assertStatus(200);

    expect($responseDocx->headers->get('Content-Type'))->toBe('application/vnd.openxmlformats-officedocument.wordprocessingml.document')
        ->and($responseDocx->headers->get('Content-Disposition'))->toContain('Riset_Standar_2026.docx');

    // Verify it is a real ZIP
    $docxBytes = $responseDocx->getContent();
    $tempFile = tempnam(sys_get_temp_dir(), 'bb_docx_');
    file_put_contents($tempFile, $docxBytes);

    $zip = new ZipArchive;
    expect($zip->open($tempFile))->toBeTrue();
    expect($zip->locateName('[Content_Types].xml'))->not->toBeFalse()
        ->and($zip->locateName('word/document.xml'))->not->toBeFalse();
    $zip->close();
    @unlink($tempFile);

    // 2. Legacy DOC export
    $responseDoc = $this->postJson('/api/documents/export', array_merge($payload, ['format' => 'doc']));
    $responseDoc->assertStatus(200);

    expect($responseDoc->headers->get('Content-Type'))->toContain('application/msword')
        ->and($responseDoc->headers->get('Content-Disposition'))->toContain('Riset_Standar_2026.doc')
        ->and($responseDoc->getContent())->toContain('<html', 'Metodologi');
});

test('black-box: BB04 round-trip import of native docx preserves content and structure', function () {
    // 1. Generate DOCX
    $exportRes = $this->postJson('/api/documents/export', [
        'title' => 'Dokumen Round Trip',
        'content' => '<h2>Bab Metrik</h2><p>Data kuantitatif hasil eksperimen.</p>',
        'format' => 'docx',
    ]);

    $docxBinary = $exportRes->getContent();

    $tempFile = tempnam(sys_get_temp_dir(), 'rt_upload_');
    file_put_contents($tempFile, $docxBinary);

    try {
        $uploadedFile = new UploadedFile(
            $tempFile,
            'dokumen_roundtrip.docx',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            null,
            true
        );

        $importRes = $this->postJson('/api/documents/import', [
            'file' => $uploadedFile,
        ]);

        $importRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'format' => 'docx',
            ]);

        $importedHtml = $importRes->json('content_html');
        expect($importedHtml)->toContain('Bab Metrik')
            ->and($importedHtml)->toContain('Data kuantitatif hasil eksperimen');
    } finally {
        @unlink($tempFile);
    }
});

test('black-box: BB14 import isolates malicious macro file safely without execution', function () {
    $tempFile = tempnam(sys_get_temp_dir(), 'macro_attack_');
    $zip = new ZipArchive;
    $zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('word/document.xml', '<?xml version="1.0"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>Dokumen Berisi Macro</w:t></w:r></w:p></w:body></w:document>');
    $zip->addFromString('word/vbaProject.bin', 'DANGEROUS_MACRO_BYTES');
    $zip->close();

    try {
        $uploaded = new UploadedFile(
            $tempFile,
            'laporan_macro.docm',
            'application/vnd.ms-word.document.macroEnabled.12',
            null,
            true
        );

        $response = $this->postJson('/api/documents/import', [
            'file' => $uploaded,
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        expect($response->json('content_html'))->toContain('Dokumen Berisi Macro')
            ->and($response->getContent())->not->toContain('DANGEROUS_MACRO_BYTES');
    } finally {
        @unlink($tempFile);
    }
});
