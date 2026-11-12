<?php

use Illuminate\Http\UploadedFile;

test('a docx upload cannot read local files using XML entities', function () {
    $secret = tempnam(sys_get_temp_dir(), 'word-secret-');
    $archive = tempnam(sys_get_temp_dir(), 'word-docx-');
    file_put_contents($secret, 'WORD_PRIVATE_SECRET_92');

    try {
        $xml = '<?xml version="1.0"?><!DOCTYPE w:document [<!ENTITY secret SYSTEM "file://'.$secret.'">]>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:body><w:p><w:r><w:t>&secret;</w:t></w:r></w:p></w:body></w:document>';
        $zip = new ZipArchive;
        expect($zip->open($archive, ZipArchive::OVERWRITE))->toBeTrue();
        $zip->addFromString('word/document.xml', $xml);
        $zip->close();

        $response = $this->postJson('/api/documents/import', [
            'file' => new UploadedFile($archive, 'test.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true),
        ]);

        expect($response->getContent())->not->toContain('WORD_PRIVATE_SECRET_92');
    } finally {
        @unlink($secret);
        @unlink($archive);
    }
});
