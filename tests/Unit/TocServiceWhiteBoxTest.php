<?php

namespace Tests\Unit;

use App\Services\TocService;
use Tests\TestCase;

class TocServiceWhiteBoxTest extends TestCase
{
    protected TocService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TocService;
    }

    public function test_extract_headings_from_html_detects_levels_and_pages(): void
    {
        $html = <<<'HTML'
<div data-page-index="1">
    <h1>BAB I PENDAHULUAN</h1>
    <h2>1.1 Latar Belakang</h2>
</div>
<div data-page-index="2">
    <h2>1.2 Rumusan Masalah</h2>
    <h3>1.2.1 Identifikasi Masalah</h3>
</div>
<div data-page-index="3">
    <h1>BAB II TINJAUAN PUSTAKA</h1>
</div>
HTML;

        $headings = $this->service->extractHeadingsFromHtml($html, 3);

        $this->assertCount(5, $headings);

        $this->assertEquals('BAB I PENDAHULUAN', $headings[0]['text']);
        $this->assertEquals(1, $headings[0]['level']);
        $this->assertEquals(1, $headings[0]['page']);

        $this->assertEquals('1.1 Latar Belakang', $headings[1]['text']);
        $this->assertEquals(2, $headings[1]['level']);
        $this->assertEquals(1, $headings[1]['page']);

        $this->assertEquals('1.2 Rumusan Masalah', $headings[2]['text']);
        $this->assertEquals(2, $headings[2]['level']);
        $this->assertEquals(2, $headings[2]['page']);

        $this->assertEquals('1.2.1 Identifikasi Masalah', $headings[3]['text']);
        $this->assertEquals(3, $headings[3]['level']);
        $this->assertEquals(2, $headings[3]['page']);

        $this->assertEquals('BAB II TINJAUAN PUSTAKA', $headings[4]['text']);
        $this->assertEquals(1, $headings[4]['level']);
        $this->assertEquals(3, $headings[4]['page']);
    }

    public function test_extract_headings_from_html_ignores_toc_title_itself(): void
    {
        $html = <<<'HTML'
<h1>DAFTAR ISI</h1>
<h1>BAB I PENDAHULUAN</h1>
<h2>TABLE OF CONTENTS</h2>
<h2>1.1 Latar Belakang</h2>
<h3>KONTEN</h3>
HTML;

        $headings = $this->service->extractHeadingsFromHtml($html, 3);

        $this->assertCount(2, $headings);
        $this->assertEquals('BAB I PENDAHULUAN', $headings[0]['text']);
        $this->assertEquals('1.1 Latar Belakang', $headings[1]['text']);
    }

    public function test_extract_headings_from_html_filters_by_max_level(): void
    {
        $html = <<<'HTML'
<h1>BAB I PENDAHULUAN</h1>
<h2>1.1 Latar Belakang</h2>
<h3>1.1.1 Detail Latar Belakang</h3>
<h4>1.1.1.1 Sub-detail</h4>
HTML;

        $headings = $this->service->extractHeadingsFromHtml($html, 2);

        $this->assertCount(2, $headings);
        $this->assertEquals(1, $headings[0]['level']);
        $this->assertEquals(2, $headings[1]['level']);
    }

    public function test_generate_toc_html_creates_proper_container_and_entries_with_dot_leader(): void
    {
        $headings = [
            ['id' => 'heading-bab-1', 'text' => 'BAB I PENDAHULUAN', 'level' => 1, 'page' => 1],
            ['id' => 'heading-sub-1-1', 'text' => '1.1 Latar Belakang', 'level' => 2, 'page' => 1],
        ];

        $html = $this->service->generateTocHtml($headings, [
            'heading' => 'DAFTAR ISI',
            'style' => 'template',
            'leader' => 'dots',
            'levels' => 3,
            'showPageNumbers' => true,
            'rightAlignPageNumbers' => true,
        ]);

        $this->assertStringContainsString('class="table-of-contents', $html);
        $this->assertStringContainsString('data-toc-block="true"', $html);
        $this->assertStringContainsString('DAFTAR ISI', $html);
        $this->assertStringContainsString('toc-toolbar', $html);
        $this->assertStringContainsString('btn-toc-update', $html);
        $this->assertStringContainsString('href="#heading-bab-1"', $html);
        $this->assertStringContainsString('BAB I PENDAHULUAN', $html);
        $this->assertStringContainsString('border-dotted', $html);
        $this->assertStringContainsString('toc-page', $html);
    }

    public function test_generate_toc_html_supports_tab_leaders_and_formats(): void
    {
        $headings = [
            ['id' => 'sec-1', 'text' => 'Pendahuluan', 'level' => 1, 'page' => 2],
        ];

        // Dashes leader & Modern style
        $htmlDashes = $this->service->generateTocHtml($headings, [
            'leader' => 'dashes',
            'style' => 'modern',
        ]);
        $this->assertStringContainsString('border-dashed', $htmlDashes);
        $this->assertStringContainsString('data-toc-style="modern"', $htmlDashes);

        // Line leader & Formal style
        $htmlLine = $this->service->generateTocHtml($headings, [
            'leader' => 'line',
            'style' => 'formal',
        ]);
        $this->assertStringContainsString('border-solid', $htmlLine);
        $this->assertStringContainsString('data-toc-style="formal"', $htmlLine);

        // None leader
        $htmlNone = $this->service->generateTocHtml($headings, [
            'leader' => 'none',
        ]);
        $this->assertStringNotContainsString('toc-leader flex-1', $htmlNone);
    }

    public function test_generate_toc_html_supports_manual_template(): void
    {
        $html = $this->service->generateTocHtml([], [
            'templateType' => 'manual',
            'heading' => 'DAFTAR ISI',
        ]);

        $this->assertStringContainsString('BAB I PENDAHULUAN', $html);
        $this->assertStringContainsString('1.1 Latar Belakang Masalah', $html);
        $this->assertStringContainsString('DAFTAR PUSTAKA', $html);
    }

    public function test_update_toc_html_updates_page_numbers_only_preserving_titles(): void
    {
        $initialHeadings = [
            ['id' => 'heading-bab-1', 'text' => 'BAB I PENDAHULUAN (TERUBAH MANUAL)', 'level' => 1, 'page' => 1],
            ['id' => 'heading-sub-1-1', 'text' => '1.1 Latar Belakang Masalah', 'level' => 2, 'page' => 2],
        ];

        $initialHtml = $this->service->generateTocHtml($initialHeadings, [
            'heading' => 'DAFTAR ISI',
            'leader' => 'dots',
        ]);

        $newHeadings = [
            ['id' => 'heading-bab-1', 'text' => 'BAB I PENDAHULUAN ASLI', 'level' => 1, 'page' => 5],
            ['id' => 'heading-sub-1-1', 'text' => '1.1 Latar Belakang Masalah', 'level' => 2, 'page' => 7],
        ];

        $updatedHtml = $this->service->updateTocHtml($initialHtml, $newHeadings, 'page_numbers');

        // Preserves custom text
        $this->assertStringContainsString('BAB I PENDAHULUAN (TERUBAH MANUAL)', $updatedHtml);
        // Updates page numbers to 5 and 7
        $this->assertStringContainsString('>5</span>', $updatedHtml);
        $this->assertStringContainsString('>7</span>', $updatedHtml);
    }

    public function test_update_toc_html_updates_entire_table(): void
    {
        $initialHeadings = [
            ['id' => 'heading-bab-1', 'text' => 'BAB I PENDAHULUAN', 'level' => 1, 'page' => 1],
        ];

        $initialHtml = $this->service->generateTocHtml($initialHeadings, [
            'heading' => 'DAFTAR ISI',
        ]);

        $newHeadings = [
            ['id' => 'heading-bab-1', 'text' => 'BAB I PENDAHULUAN', 'level' => 1, 'page' => 1],
            ['id' => 'heading-bab-2', 'text' => 'BAB II LANDASAN TEORI', 'level' => 1, 'page' => 4],
        ];

        $updatedHtml = $this->service->updateTocHtml($initialHtml, $newHeadings, 'entire');

        $this->assertStringContainsString('BAB I PENDAHULUAN', $updatedHtml);
        $this->assertStringContainsString('BAB II LANDASAN TEORI', $updatedHtml);
        $this->assertStringContainsString('>4</span>', $updatedHtml);
    }

    public function test_generate_toc_html_includes_editable_headings_and_tabindex_for_deletion(): void
    {
        $headings = [
            ['id' => 'heading-bab-1', 'text' => 'BAB I PENDAHULUAN', 'level' => 1, 'page' => 1],
        ];

        $html = $this->service->generateTocHtml($headings, [
            'heading' => 'DAFTAR ISI',
        ]);

        $this->assertStringContainsString('tabindex="0"', $html);
        $this->assertStringContainsString('contenteditable="false"', $html);
        $this->assertStringContainsString('<h1 class="toc-header heading-chapter text-center font-bold text-xl mb-6 uppercase tracking-wider text-gray-900 dark:text-white font-serif outline-none" contenteditable="true">DAFTAR ISI</h1>', $html);
        $this->assertStringContainsString('<div class="toc-entries space-y-1 font-serif" contenteditable="true">', $html);
        $this->assertStringContainsString('contenteditable="true">BAB I PENDAHULUAN</a>', $html);
        $this->assertStringContainsString('contenteditable="true">1</span>', $html);
    }
}
