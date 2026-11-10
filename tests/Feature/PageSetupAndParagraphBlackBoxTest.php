<?php

use Illuminate\Http\UploadedFile;

/*
|--------------------------------------------------------------------------
| Black Box Feature Tests: Microsoft Word Page Setup & Paragraph Suite
|--------------------------------------------------------------------------
| Tests user-facing UI modals, ribbons, options, dropdowns, import, and export
| for full margins, paper sizes, orientation, paragraph formatting, and line spacing.
*/

test('black-box: editor view renders Microsoft Word Page Setup modal with all tabs, inputs, and previews', function () {
    $response = $this->get('/editor');
    $response->assertStatus(200);

    // Modal container
    $response->assertSee('id="modal-page-setup"', false);
    $response->assertSee('Penyetelan Halaman (Page Setup)', false);

    // Tab buttons
    $response->assertSee('data-page-setup-tab="margins"', false);
    $response->assertSee('data-page-setup-tab="paper"', false);
    $response->assertSee('data-page-setup-tab="layout"', false);

    // Margin inputs
    $response->assertSee('id="ps-margin-top"', false);
    $response->assertSee('id="ps-margin-bottom"', false);
    $response->assertSee('id="ps-margin-left"', false);
    $response->assertSee('id="ps-margin-right"', false);
    $response->assertSee('id="ps-gutter"', false);
    $response->assertSee('id="ps-gutter-pos"', false);

    // Orientation cards
    $response->assertSee('data-orient="portrait"', false);
    $response->assertSee('data-orient="landscape"', false);

    // Presets
    $response->assertSee('data-ps-preset="skripsi"', false);
    $response->assertSee('data-ps-preset="normal"', false);
    $response->assertSee('data-ps-preset="narrow"', false);
    $response->assertSee('data-ps-preset="moderate"', false);
    $response->assertSee('data-ps-preset="wide"', false);
    $response->assertSee('data-ps-preset="mirrored"', false);

    // Live preview canvas
    $response->assertSee('id="ps-preview-canvas"', false);
    $response->assertSee('id="ps-preview-content-box"', false);
    $response->assertSee('id="ps-preview-caption"', false);

    // Paper tab inputs
    $response->assertSee('id="ps-paper-size-select"', false);
    $response->assertSee('id="ps-custom-width"', false);
    $response->assertSee('id="ps-custom-height"', false);

    // Action buttons
    $response->assertSee('id="btn-confirm-page-setup"', false);
    $response->assertSee('id="btn-ps-set-default"', false);
});

test('black-box: editor view renders Microsoft Word Paragraph Formatting modal with all tabs, inputs, and preview', function () {
    $response = $this->get('/editor');
    $response->assertStatus(200);

    // Modal container
    $response->assertSee('id="modal-paragraph-format"', false);
    $response->assertSee('Format Paragraf (Paragraph Options)', false);

    // Tabs
    $response->assertSee('data-para-tab="indents-spacing"', false);
    $response->assertSee('data-para-tab="line-breaks"', false);

    // Indents & Spacing inputs
    $response->assertSee('id="para-align-select"', false);
    $response->assertSee('id="para-indent-left"', false);
    $response->assertSee('id="para-indent-right"', false);
    $response->assertSee('id="para-special-indent"', false);
    $response->assertSee('id="para-special-val"', false);
    $response->assertSee('id="para-spacing-before"', false);
    $response->assertSee('id="para-spacing-after"', false);
    $response->assertSee('id="para-line-spacing-type"', false);
    $response->assertSee('id="para-line-spacing-at"', false);

    // Pagination controls
    $response->assertSee('id="para-widow-orphan"', false);
    $response->assertSee('id="para-keep-with-next"', false);
    $response->assertSee('id="para-keep-lines-together"', false);
    $response->assertSee('id="para-page-break-before"', false);

    // Live sample preview
    $response->assertSee('id="para-preview-sample"', false);

    // Action buttons
    $response->assertSee('id="btn-confirm-paragraph-format"', false);
    $response->assertSee('id="btn-para-set-default"', false);
});

test('black-box: editor view renders Home ribbon Line & Paragraph Spacing dropdown menu', function () {
    $response = $this->get('/editor');
    $response->assertStatus(200);

    // Dropdown trigger & menu
    $response->assertSee('id="btn-line-spacing-dropdown"', false);
    $response->assertSee('id="line-spacing-dropdown-menu"', false);

    // Line spacing options
    $response->assertSee('data-spacing-val="1.0"', false);
    $response->assertSee('data-spacing-val="1.15"', false);
    $response->assertSee('data-spacing-val="1.5"', false);
    $response->assertSee('data-spacing-val="2.0"', false);
    $response->assertSee('data-spacing-val="2.5"', false);
    $response->assertSee('data-spacing-val="3.0"', false);

    // Spacing actions
    $response->assertSee('data-action="add-space-before"', false);
    $response->assertSee('data-action="remove-space-before"', false);
    $response->assertSee('data-action="add-space-after"', false);
    $response->assertSee('data-action="remove-space-after"', false);
    $response->assertSee('data-action="open-paragraph-modal"', false);

    // Launchers
    $response->assertSee('id="btn-open-paragraph-modal"', false);
});

test('black-box: editor view renders Layout ribbon Margins, Paper Size, Orientation, and Paragraph quick inputs', function () {
    $response = $this->get('/editor');
    $response->assertStatus(200);

    // Margins select presets
    $response->assertSee('id="layout-margins"', false);
    $response->assertSee('value="skripsi"', false);
    $response->assertSee('value="normal"', false);
    $response->assertSee('value="narrow"', false);
    $response->assertSee('value="moderate"', false);
    $response->assertSee('value="wide"', false);
    $response->assertSee('value="mirrored"', false);

    // Paper size select
    $response->assertSee('id="layout-paper-size"', false);
    $response->assertSee('value="a4"', false);
    $response->assertSee('value="letter"', false);
    $response->assertSee('value="legal"', false);
    $response->assertSee('value="folio"', false);
    $response->assertSee('value="executive"', false);
    $response->assertSee('value="a3"', false);
    $response->assertSee('value="a5"', false);
    $response->assertSee('value="b5"', false);
    $response->assertSee('value="statement"', false);

    // Orientation select
    $response->assertSee('id="layout-orientation"', false);
    $response->assertSee('value="portrait"', false);
    $response->assertSee('value="landscape"', false);

    // Paragraph quick inputs
    $response->assertSee('id="layout-indent-left"', false);
    $response->assertSee('id="layout-indent-right"', false);
    $response->assertSee('id="layout-spacing-before"', false);
    $response->assertSee('id="layout-spacing-after"', false);

    // Launcher buttons
    $response->assertSee('id="btn-open-page-setup-modal"', false);
    $response->assertSee('id="btn-layout-open-paragraph-modal"', false);
});

test('black-box: user can export document with custom layout parameters via API', function () {
    $payload = [
        'title' => 'Proposal Skripsi Teknik Informatika',
        'content' => '<h1>BAB I PENDAHULUAN</h1><p>Penelitian ini bertujuan untuk mengoptimalkan layout dokumen digital.</p>',
        'format' => 'html',
        'paper_size' => 'legal',
        'orientation' => 'landscape',
        'margin_preset' => 'skripsi',
        'margins' => [
            'top' => 40,
            'bottom' => 30,
            'left' => 40,
            'right' => 30,
        ],
        'line_spacing' => '1.5',
        'paragraph_spacing' => [
            'before' => 0,
            'after' => 6,
        ],
    ];

    $response = $this->post('/api/documents/export', $payload);

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');

    $content = $response->getContent();
    expect($content)->toContain('@page { size: 355.6mm 215.9mm; margin: 40mm 30mm 30mm 40mm; }')
        ->and($content)->toContain('line-height: 1.5;')
        ->and($content)->toContain('margin-top: 0pt;')
        ->and($content)->toContain('margin-bottom: 6pt;');
});

test('black-box: user can export document as JSON and preserve layout options', function () {
    $payload = [
        'title' => 'Backup Naskah Riset',
        'content' => '<p>Uji cadangan layout.</p>',
        'format' => 'json',
        'paper_size' => 'folio',
        'orientation' => 'portrait',
        'margin_preset' => 'custom',
        'margins' => [
            'top' => 35,
            'bottom' => 25,
            'left' => 35,
            'right' => 25,
        ],
        'line_spacing' => '2.0',
    ];

    $response = $this->post('/api/documents/export', $payload);

    $response->assertStatus(200);
    $data = json_decode($response->getContent(), true);

    expect($data)->toBeArray()
        ->and($data)->toHaveKey('layout_options')
        ->and($data['layout_options']['paper_size'])->toBe('folio')
        ->and($data['layout_options']['orientation'])->toBe('portrait')
        ->and($data['layout_options']['line_spacing'])->toBe('2.0')
        ->and($data['layout_options']['margins']['top'])->toBe(35);
});

test('black-box: user can upload DOCX file and receive parsed page setup and paragraph metadata', function () {
    $zipPath = tempnam(sys_get_temp_dir(), 'docx_page_setup_').'.docx';
    $zip = new ZipArchive();
    $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    // Minimal DOCX with sectPr A4 skripsi margins & paragraph spacing/indents
    $docXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:p>
            <w:pPr>
                <w:spacing w:before="240" w:after="120" w:line="360" w:lineRule="auto"/>
            </w:pPr>
            <w:r>
                <w:t>BAB I PENDAHULUAN RISET</w:t>
            </w:r>
        </w:p>
        <w:p>
            <w:pPr>
                <w:spacing w:before="120" w:after="120" w:line="360" w:lineRule="auto"/>
                <w:ind w:left="720" w:firstLine="720"/>
            </w:pPr>
            <w:r>
                <w:t>Paragraf teks isi penelitian akademik dengan indentasi baris pertama.</w:t>
            </w:r>
        </w:p>
        <w:sectPr>
            <w:pgSz w:w="11906" w:h="16838" w:orient="portrait"/>
            <w:pgMar w:top="2268" w:right="1701" w:bottom="1701" w:left="2268" w:gutter="0"/>
        </w:sectPr>
    </w:body>
</w:document>
XML;

    $zip->addFromString('word/document.xml', $docXml);
    $zip->close();

    $file = new UploadedFile($zipPath, 'test_setup.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);

    $response = $this->post('/api/documents/import', [
        'file' => $file,
    ]);

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'content_html',
        'title',
        'format',
        'page_setup',
    ]);

    $setup = $response->json('page_setup');
    expect($setup)->toBeArray()
        ->and($setup['paper_size'])->toBe('a4')
        ->and($setup['orientation'])->toBe('portrait')
        ->and($setup['margin_preset'])->toBe('skripsi');

    $html = $response->json('content_html');
    expect($html)->toContain('margin-top: 12pt')
        ->and($html)->toContain('margin-bottom: 6pt')
        ->and($html)->toContain('line-height: 1.5')
        ->and($html)->toContain('indent-first-line')
        ->and($html)->toContain('text-indent: 1.27cm');

    @unlink($zipPath);
});
