<?php

use App\Services\DocxImporter;

test('docx parser preserves ordinary xml entities without resolving external entities', function () {
    $archive = tempnam(sys_get_temp_dir(), 'docx-xml-');
    $secret = tempnam(sys_get_temp_dir(), 'docx-secret-');
    file_put_contents($secret, 'CONFIDENTIAL_WORD_DATA');

    try {
        $xml = '<?xml version="1.0"?><!DOCTYPE w:document [<!ENTITY secret SYSTEM "file://'.$secret.'">]>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:body><w:p><w:r><w:t>A &amp; B &secret;</w:t></w:r></w:p></w:body></w:document>';
        $zip = new ZipArchive;
        expect($zip->open($archive, ZipArchive::OVERWRITE))->toBeTrue();
        $zip->addFromString('word/document.xml', $xml);
        $zip->close();

        $html = (new DocxImporter)->parse($archive);

        expect(html_entity_decode(strip_tags($html)))->toContain('A & B')
            ->not->toContain('CONFIDENTIAL_WORD_DATA');
    } finally {
        @unlink($archive);
        @unlink($secret);
    }
});
