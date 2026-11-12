<?php

namespace Tests\Unit;

use App\Services\DocumentService;
use App\Services\DocxImporter;
use DOMDocument;
use DOMXPath;
use Tests\TestCase;
use ZipArchive;

class PageSetupAndParagraphWhiteBoxTest extends TestCase
{
    protected DocxImporter $importer;

    protected DocumentService $docService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importer = new DocxImporter;
        $this->docService = new DocumentService;
    }

    public function test_parse_page_setup_detects_a4_portrait_skripsi_margins(): void
    {
        // A4: 11906 x 16838 twips (210 x 297 mm)
        // Margins: Top 40mm (2268 twips), Left 40mm (2268 twips), Bottom 30mm (1701 twips), Right 30mm (1701 twips)
        $docXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:p><w:r><w:t>Halo Dunia</w:t></w:r></w:p>
        <w:sectPr>
            <w:pgSz w:w="11906" w:h="16838" w:orient="portrait"/>
            <w:pgMar w:top="2268" w:right="1701" w:bottom="1701" w:left="2268" w:gutter="0"/>
        </w:sectPr>
    </w:body>
</w:document>
XML;

        $setup = $this->importer->parsePageSetup($docXml);

        $this->assertEquals('a4', $setup['paper_size']);
        $this->assertEquals('portrait', $setup['orientation']);
        $this->assertEquals(210.0, $setup['width_mm']);
        $this->assertEquals(297.0, $setup['height_mm']);
        $this->assertEquals(40.0, $setup['margins']['top']);
        $this->assertEquals(40.0, $setup['margins']['left']);
        $this->assertEquals(30.0, $setup['margins']['bottom']);
        $this->assertEquals(30.0, $setup['margins']['right']);
        $this->assertEquals('skripsi', $setup['margin_preset']);
    }

    public function test_parse_page_setup_detects_letter_landscape_normal_margins(): void
    {
        // Letter landscape: 15840 x 12240 twips (279.4 x 215.9 mm)
        // Margins: Normal 25.4mm (1440 twips)
        $docXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:sectPr>
            <w:pgSz w:w="15840" w:h="12240" w:orient="landscape"/>
            <w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440"/>
        </w:sectPr>
    </w:body>
</w:document>
XML;

        $setup = $this->importer->parsePageSetup($docXml);

        $this->assertEquals('letter', $setup['paper_size']);
        $this->assertEquals('landscape', $setup['orientation']);
        $this->assertEquals(25.4, $setup['margins']['top']);
        $this->assertEquals(25.4, $setup['margins']['left']);
        $this->assertEquals(25.4, $setup['margins']['bottom']);
        $this->assertEquals(25.4, $setup['margins']['right']);
        $this->assertEquals('normal', $setup['margin_preset']);
    }

    public function test_parse_page_setup_detects_legal_and_folio_paper_sizes(): void
    {
        // Legal: 12240 x 20160 twips
        $legalXml = <<<'XML'
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:sectPr>
            <w:pgSz w:w="12240" w:h="20160"/>
            <w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440"/>
        </w:sectPr>
    </w:body>
</w:document>
XML;
        $legalSetup = $this->importer->parsePageSetup($legalXml);
        $this->assertEquals('legal', $legalSetup['paper_size']);

        // Folio / F4: 12189 x 18708 twips (215 x 330 mm)
        $folioXml = <<<'XML'
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:sectPr>
            <w:pgSz w:w="12189" w:h="18708"/>
            <w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440"/>
        </w:sectPr>
    </w:body>
</w:document>
XML;
        $folioSetup = $this->importer->parsePageSetup($folioXml);
        $this->assertEquals('folio', $folioSetup['paper_size']);
    }

    public function test_parse_paragraph_spacing_and_first_line_indent(): void
    {
        $xml = <<<'XML'
<w:p xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:pPr>
        <w:spacing w:before="240" w:after="120" w:line="360" w:lineRule="auto"/>
        <w:ind w:left="720" w:right="360" w:firstLine="720"/>
    </w:pPr>
    <w:r><w:t>Paragraf teks akademik dengan indentasi baris pertama.</w:t></w:r>
</w:p>
XML;

        $dom = new DOMDocument;
        $dom->loadXML($xml);
        $pNode = $dom->documentElement;
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $zip = new ZipArchive;

        $reflection = new \ReflectionClass(DocxImporter::class);
        $method = $reflection->getMethod('parseParagraph');
        $method->setAccessible(true);

        $parsed = $method->invoke($this->importer, $pNode, $xpath, $zip, [], [], []);
        $html = $parsed['html'];

        $this->assertStringContainsString('margin-top: 12pt', $html);
        $this->assertStringContainsString('margin-bottom: 6pt', $html);
        $this->assertStringContainsString('line-height: 1.5', $html);
        $this->assertStringContainsString('margin-left: 1.27cm', $html);
        $this->assertStringContainsString('margin-right: 0.64cm', $html);
        $this->assertStringContainsString('indent-first-line', $html);
        $this->assertStringContainsString('text-indent: 1.27cm', $html);
    }

    public function test_parse_paragraph_hanging_indent(): void
    {
        $xml = <<<'XML'
<w:p xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:pPr>
        <w:spacing w:before="0" w:after="160" w:line="240" w:lineRule="auto"/>
        <w:ind w:left="720" w:hanging="720"/>
    </w:pPr>
    <w:r><w:t>Kotler, P., &amp; Keller, K. L. (2016). Marketing Management (15th ed.). Pearson Education.</w:t></w:r>
</w:p>
XML;

        $dom = new DOMDocument;
        $dom->loadXML($xml);
        $pNode = $dom->documentElement;
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $zip = new ZipArchive;

        $reflection = new \ReflectionClass(DocxImporter::class);
        $method = $reflection->getMethod('parseParagraph');
        $method->setAccessible(true);

        $parsed = $method->invoke($this->importer, $pNode, $xpath, $zip, [], [], []);
        $html = $parsed['html'];

        $this->assertStringContainsString('indent-hanging', $html);
        $this->assertStringContainsString('text-indent: -1.27cm', $html);
        $this->assertStringContainsString('padding-left: 1.27cm', $html);
    }

    public function test_document_service_export_embeds_layout_options_in_html(): void
    {
        $content = '<p>Naskah dalam format lanskap.</p>';
        $layoutOptions = [
            'paper_size' => 'legal',
            'orientation' => 'landscape',
            'margins' => ['top' => 30, 'bottom' => 30, 'left' => 35, 'right' => 25],
            'line_spacing' => '2.0',
            'paragraph_spacing' => ['before' => 6, 'after' => 12],
        ];

        $result = $this->docService->exportDocument('Laporan Lanskap', $content, 'html', null, null, $layoutOptions);
        $htmlOutput = $result['content'];

        $this->assertStringContainsString('@page {', $htmlOutput);
        $this->assertStringContainsString('size: 355.6mm 215.9mm', $htmlOutput);
        $this->assertStringContainsString('margin: 30mm 25mm 30mm 35mm', $htmlOutput);
        $this->assertStringContainsString('line-height: 2.0', $htmlOutput);
        $this->assertStringContainsString('margin-top: 6pt', $htmlOutput);
        $this->assertStringContainsString('margin-bottom: 12pt', $htmlOutput);
    }

    public function test_document_service_export_docx_xml_embeds_page_sz_and_margins(): void
    {
        $content = '<p>Naskah skripsi akademik.</p>';
        $layoutOptions = [
            'paper_size' => 'a4',
            'orientation' => 'portrait',
            'margin_preset' => 'skripsi',
            'margins' => ['top' => 40, 'bottom' => 30, 'left' => 40, 'right' => 30],
            'line_spacing' => '1.5',
            'paragraph_spacing' => ['before' => 0, 'after' => 6],
        ];

        $result = $this->docService->exportDocument('Skripsi Bab I', $content, 'doc', null, null, $layoutOptions);
        $docOutput = $result['content'];

        $this->assertStringContainsString('@page Section1 {', $docOutput);
        $this->assertStringContainsString('size: 210mm 297mm;', $docOutput);
        $this->assertStringContainsString('margin: 40mm 30mm 30mm 40mm;', $docOutput);
        $this->assertStringContainsString('mso-page-orientation: portrait;', $docOutput);
        $this->assertStringContainsString('line-height: 1.5;', $docOutput);
        $this->assertStringContainsString('margin-top: 0pt;', $docOutput);
        $this->assertStringContainsString('margin-bottom: 6pt;', $docOutput);
    }

    public function test_document_service_export_json_preserves_layout_options(): void
    {
        $content = '<p>Dokumen Uji JSON</p>';
        $layoutOptions = [
            'paper_size' => 'folio',
            'orientation' => 'portrait',
            'margin_preset' => 'custom',
            'margins' => ['top' => 30, 'bottom' => 25, 'left' => 30, 'right' => 20],
        ];

        $result = $this->docService->exportDocument('JSON Dokumen', $content, 'json', null, null, $layoutOptions);
        $data = json_decode($result['content'], true);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('layout_options', $data);
        $this->assertEquals('folio', $data['layout_options']['paper_size']);
        $this->assertEquals('portrait', $data['layout_options']['orientation']);
        $this->assertEquals(30, $data['layout_options']['margins']['top']);
    }

    public function test_parse_page_setup_detects_tabloid_paper_size_and_office2003_margins(): void
    {
        // Tabloid: 15840 x 24480 twips (279.4 x 431.8 mm)
        // Office 2003 margins: top/bottom 25.4mm (1440 twips), left/right 31.8mm (1800 twips)
        $docXml = <<<'XML'
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:sectPr>
            <w:pgSz w:w="15840" w:h="24480" w:orient="portrait"/>
            <w:pgMar w:top="1440" w:right="1800" w:bottom="1440" w:left="1800"/>
            <w:vAlign w:val="center"/>
        </w:sectPr>
    </w:body>
</w:document>
XML;

        $setup = $this->importer->parsePageSetup($docXml);

        $this->assertEquals('tabloid', $setup['paper_size']);
        $this->assertEquals('portrait', $setup['orientation']);
        $this->assertEquals('office2003', $setup['margin_preset']);
        $this->assertEquals(25.4, $setup['margins']['top']);
        $this->assertEquals(31.8, $setup['margins']['left']);
        $this->assertEquals('center', $setup['vertical_align']);
    }

    public function test_document_service_export_embeds_tabloid_and_office2003(): void
    {
        $content = '<p>Dokumen berukuran Tabloid dengan format margin Office 2003.</p>';
        $layoutOptions = [
            'paper_size' => 'tabloid',
            'orientation' => 'landscape',
            'margin_preset' => 'office2003',
            'line_spacing' => '1.15',
            'paragraph_spacing' => ['before' => 3, 'after' => 8],
            'paragraph_indent' => ['left' => 1.0, 'right' => 0.5],
        ];

        $htmlResult = $this->docService->exportDocument('Tabloid Uji', $content, 'html', null, null, $layoutOptions);
        $this->assertStringContainsString('size: 431.8mm 279.4mm;', $htmlResult['content']);
        $this->assertStringContainsString('margin: 25.4mm 31.8mm 25.4mm 31.8mm;', $htmlResult['content']);
        $this->assertStringContainsString('line-height: 1.15;', $htmlResult['content']);

        $docxResult = $this->docService->exportDocument('Tabloid Docx', $content, 'docx', null, null, $layoutOptions);
        $this->assertStringContainsString('size: 431.8mm 279.4mm;', $docxResult['content']);
        $this->assertStringContainsString('margin: 25.4mm 31.8mm 25.4mm 31.8mm;', $docxResult['content']);
        $this->assertStringContainsString('line-height: 1.15;', $docxResult['content']);
    }

    public function test_document_service_export_docx_embeds_columns_hyphenation_and_break_tags(): void
    {
        $content = '<p>Paragraf pembuka.</p><div class="column-break">Pemisah Kolom</div><p>Paragraf kedua.</p><div class="section-break">Pemisah Bagian</div><p>Paragraf ketiga.</p>';
        $layoutOptions = [
            'paper_size' => 'a4',
            'columns' => 2,
            'hyphenation' => 'auto',
            'tab_stops' => [
                ['pos' => 1.25, 'align' => 'left', 'leader' => 'none'],
                ['pos' => 5.0, 'align' => 'right', 'leader' => 'dots'],
            ],
            'default_tab_stop' => 1.27,
        ];

        $docxResult = $this->docService->exportDocument('Uji Kolom dan Tanda Hubung', $content, 'doc', null, null, $layoutOptions);
        $docxContent = $docxResult['content'];

        $this->assertStringContainsString('column-count: 2;', $docxContent);
        $this->assertStringContainsString('mso-column-count: 2;', $docxContent);
        $this->assertStringContainsString('hyphens: auto;', $docxContent);
        $this->assertStringContainsString('mso-hyphenate: auto;', $docxContent);
        $this->assertStringContainsString('mso-break-type:column', $docxContent);
        $this->assertStringContainsString('mso-break-type:section-break', $docxContent);
    }

    public function test_document_service_export_json_preserves_columns_hyphenation_and_tab_stops(): void
    {
        $content = '<p>Dokumen Uji Multi-Kolom JSON</p>';
        $layoutOptions = [
            'paper_size' => 'a4',
            'columns' => 3,
            'hyphenation' => 'auto',
            'tab_stops' => [
                ['pos' => 2.0, 'align' => 'left'],
                ['pos' => 8.0, 'align' => 'right'],
            ],
            'default_tab_stop' => 1.27,
        ];

        $jsonResult = $this->docService->exportDocument('Uji JSON Kolom', $content, 'json', null, null, $layoutOptions);
        $data = json_decode($jsonResult['content'], true);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('layout_options', $data);
        $this->assertEquals(3, $data['layout_options']['columns']);
        $this->assertEquals('auto', $data['layout_options']['hyphenation']);
        $this->assertCount(2, $data['layout_options']['tab_stops']);
        $this->assertEquals(1.27, $data['layout_options']['default_tab_stop']);
    }

    public function test_document_service_export_embeds_watermark_page_color_border_and_line_numbers(): void
    {
        $content = '<p>Dokumen berstempel rahasia dan berbingkai warna.</p>';
        $layoutOptions = [
            'paper_size' => 'a4',
            'watermark' => [
                'type' => 'text',
                'text' => 'SANGAT RAHASIA',
                'font' => 'Georgia',
                'color' => '#e11d48',
                'opacity' => 0.35,
                'orientation' => 'diagonal',
            ],
            'page_color' => '#fefce8',
            'page_border' => [
                'style' => 'double',
                'width' => '3pt',
                'color' => '#1e40af',
                'margin' => '18mm',
                'applyTo' => 'whole',
            ],
            'line_numbers' => 'continuous',
        ];

        // 1. DOCX/DOC Export
        $docxResult = $this->docService->exportDocument('Uji Cap Air DOCX', $content, 'docx', null, null, $layoutOptions);
        $docxContent = $docxResult['content'];

        $this->assertStringContainsString('SANGAT RAHASIA', $docxContent);
        $this->assertStringContainsString('background-color: #fefce8;', $docxContent);
        $this->assertStringContainsString('border: 3pt double #1e40af;', $docxContent);
        $this->assertStringContainsString('counter-reset: line-counter;', $docxContent);

        // 2. HTML Export
        $htmlResult = $this->docService->exportDocument('Uji Cap Air HTML', $content, 'html', null, null, $layoutOptions);
        $htmlContent = $htmlResult['content'];

        $this->assertStringContainsString('SANGAT RAHASIA', $htmlContent);
        $this->assertStringContainsString('background-color: #fefce8;', $htmlContent);
        $this->assertStringContainsString('border: 3pt double #1e40af;', $htmlContent);
        $this->assertStringContainsString('counter-reset: line-counter;', $htmlContent);

        // 3. JSON Export
        $jsonResult = $this->docService->exportDocument('Uji Cap Air JSON', $content, 'json', null, null, $layoutOptions);
        $jsonData = json_decode($jsonResult['content'], true);

        $this->assertIsArray($jsonData);
        $this->assertEquals('SANGAT RAHASIA', $jsonData['layout_options']['watermark']['text']);
        $this->assertEquals('#fefce8', $jsonData['layout_options']['page_color']);
        $this->assertEquals('double', $jsonData['layout_options']['page_border']['style']);
        $this->assertEquals('continuous', $jsonData['layout_options']['line_numbers']);
    }

    public function test_paragraph_shading_borders_and_drop_cap_export_parity(): void
    {
        $content = '<p style="background-color: #fef08a; padding: 6px 12px; border-left: 3.5px solid #4f46e5;"><span class="drop-cap" style="float: left; font-size: 3.2em; font-weight: bold; margin-right: 0.2cm;">P</span>aragraf pembuka dengan huruf awal kapital besar (drop cap), warna latar kuning sorotan, dan garis batas kiri gaya Word.</p>';

        $layoutOptions = [
            'paper_size' => 'a4',
            'orientation' => 'portrait',
        ];

        // DOCX / Word HTML Export
        $docxResult = $this->docService->exportDocument('Uji Paragraf Lengkap DOCX', $content, 'doc', null, null, $layoutOptions);
        $this->assertStringContainsString('background-color: #fef08a;', $docxResult['content']);
        $this->assertStringContainsString('border-left: 3.5px solid #4f46e5;', $docxResult['content']);
        $this->assertStringContainsString('class="drop-cap"', $docxResult['content']);

        // HTML Export
        $htmlResult = $this->docService->exportDocument('Uji Paragraf Lengkap HTML', $content, 'html', null, null, $layoutOptions);
        $this->assertStringContainsString('background-color: #fef08a;', $htmlResult['content']);
        $this->assertStringContainsString('border-left: 3.5px solid #4f46e5;', $htmlResult['content']);
        $this->assertStringContainsString('class="drop-cap"', $htmlResult['content']);

        // JSON Export
        $jsonResult = $this->docService->exportDocument('Uji Paragraf Lengkap JSON', $content, 'json', null, null, $layoutOptions);
        $jsonData = json_decode($jsonResult['content'], true);
        $this->assertIsArray($jsonData);
        $this->assertStringContainsString('drop-cap', $jsonData['content']);
    }

    public function test_bidirectional_text_direction_and_paragraph_sorting_export_fidelity(): void
    {
        $content = <<<'HTML'
<p dir="rtl" style="direction: rtl; text-align: right; font-family: 'Amiri', 'Times New Roman', serif;">
    بسم الله الرحمن الرحيم - Teks berbahasa Arab dengan arah penulisan Kanan-ke-Kiri (RTL).
</p>
<p dir="ltr" style="direction: ltr; text-align: left;">
    Paragraf A: Analisis pendahuluan data penelitian.
</p>
<p dir="ltr" style="direction: ltr; text-align: left;">
    Paragraf B: Batasan masalah dan tinjauan pustaka sistematis.
</p>
HTML;

        $layoutOptions = [
            'paper_size' => 'a4',
            'orientation' => 'portrait',
            'margins' => [
                'top' => 40,
                'left' => 40,
                'bottom' => 30,
                'right' => 30,
            ],
        ];

        // 1. DOCX / Word HTML Export
        $docxResult = $this->docService->exportDocument('Uji BiDi dan Urutan DOCX', $content, 'doc', null, null, $layoutOptions);
        $docxContent = $docxResult['content'];
        $this->assertStringContainsString('dir="rtl"', $docxContent);
        $this->assertStringContainsString('direction: rtl;', $docxContent);
        $this->assertStringContainsString('Paragraf A:', $docxContent);
        $this->assertStringContainsString('Paragraf B:', $docxContent);

        // 2. HTML Export
        $htmlResult = $this->docService->exportDocument('Uji BiDi dan Urutan HTML', $content, 'html', null, null, $layoutOptions);
        $htmlContent = $htmlResult['content'];
        $this->assertStringContainsString('dir="rtl"', $htmlContent);
        $this->assertStringContainsString('direction: rtl;', $htmlContent);
        $this->assertStringContainsString('Paragraf A:', $htmlContent);

        // 3. JSON Export
        $jsonResult = $this->docService->exportDocument('Uji BiDi dan Urutan JSON', $content, 'json', null, null, $layoutOptions);
        $jsonData = json_decode($jsonResult['content'], true);
        $this->assertIsArray($jsonData);
        $this->assertStringContainsString('dir="rtl"', $jsonData['content']);
    }

    public function test_multiple_pages_mirror_margins_and_vertical_alignment_export_fidelity(): void
    {
        $content = '<p>Dokumen dengan margin cermin buku dan perataan vertikal tengah (cover page / lembar pengesahan).</p>';

        $layoutOptions = [
            'paper_size' => 'a4',
            'orientation' => 'portrait',
            'multiple_pages' => 'mirror',
            'vertical_align' => 'center',
            'margins' => [
                'top' => 30,
                'bottom' => 30,
                'left' => 35,
                'right' => 25,
            ],
        ];

        // 1. DOCX / Word HTML Export
        $docxResult = $this->docService->exportDocument('Uji Mirror Margins DOCX', $content, 'doc', null, null, $layoutOptions);
        $this->assertStringContainsString('mso-mirror-margins: yes;', $docxResult['content']);
        $this->assertStringContainsString('justify-content: center;', $docxResult['content']);

        // 2. HTML Export
        $htmlResult = $this->docService->exportDocument('Uji Mirror Margins HTML', $content, 'html', null, null, $layoutOptions);
        $this->assertStringContainsString('justify-content: center;', $htmlResult['content']);

        // 3. Two pages per sheet mode
        $twoPagesLayout = [
            'paper_size' => 'a4',
            'orientation' => 'landscape',
            'multiple_pages' => '2pages',
        ];
        $twoPagesDocx = $this->docService->exportDocument('Uji 2 Pages DOCX', $content, 'doc', null, null, $twoPagesLayout);
        $this->assertStringContainsString('column-count: 2;', $twoPagesDocx['content']);
        $this->assertStringContainsString('mso-column-count: 2;', $twoPagesDocx['content']);
    }
}
