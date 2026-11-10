<?php

namespace Tests\Unit;

use App\Services\CitationService;
use Tests\TestCase;

class CitationServiceWhiteBoxTest extends TestCase
{
    protected CitationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CitationService;
    }

    public function test_search_sources_returns_academic_results_with_fallbacks(): void
    {
        $results = $this->service->searchSources('TCS34725 ESP32');

        $this->assertIsArray($results);
        $this->assertNotEmpty($results);

        $first = $results[0];
        $this->assertArrayHasKey('id', $first);
        $this->assertArrayHasKey('title', $first);
        $this->assertArrayHasKey('author', $first);
        $this->assertArrayHasKey('year', $first);
        $this->assertArrayHasKey('preview_apa', $first);
        $this->assertArrayHasKey('preview_ieee', $first);
        $this->assertArrayHasKey('citation_intext_apa', $first);
        $this->assertArrayHasKey('citation_intext_ieee', $first);
    }

    public function test_search_sources_handles_doi_query(): void
    {
        $results = $this->service->searchSources('10.1016/j.procs.2023.01.002');

        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
        $this->assertNotEmpty($results[0]['title']);
    }

    public function test_search_sources_filters_by_type(): void
    {
        $books = $this->service->searchSources('Metode Penelitian', 'book');

        $this->assertIsArray($books);
        foreach ($books as $b) {
            $this->assertEquals('book', $b['type']);
        }
    }

    public function test_search_sources_returns_empty_on_blank_query(): void
    {
        $this->assertEmpty($this->service->searchSources(''));
        $this->assertEmpty($this->service->searchSources('   '));
    }

    public function test_format_citation_in_text_all_styles(): void
    {
        $source = [
            'id' => 'src-1',
            'title' => 'Software Engineering',
            'author' => 'Santoso, Budi & Pratama, Aditya',
            'year' => '2024',
        ];

        // APA: (Santoso & Pratama, 2024)
        $apa = $this->service->formatCitationInText($source, 'apa');
        $this->assertEquals('(Santoso & Pratama, 2024)', $apa);

        // IEEE: [1]
        $ieee = $this->service->formatCitationInText($source, 'ieee', 1);
        $this->assertEquals('[1]', $ieee);

        // Vancouver: (1)
        $vancouver = $this->service->formatCitationInText($source, 'vancouver', 1);
        $this->assertEquals('(1)', $vancouver);

        // Harvard: (Santoso & Pratama, 2024)
        $harvard = $this->service->formatCitationInText($source, 'harvard');
        $this->assertEquals('(Santoso & Pratama, 2024)', $harvard);

        // MLA: (Santoso & Pratama)
        $mla = $this->service->formatCitationInText($source, 'mla');
        $this->assertEquals('(Santoso & Pratama)', $mla);

        // Chicago: (Santoso & Pratama 2024)
        $chicago = $this->service->formatCitationInText($source, 'chicago');
        $this->assertEquals('(Santoso & Pratama 2024)', $chicago);
    }

    public function test_format_bibliography_entry_apa_7th(): void
    {
        $source = [
            'type' => 'journal',
            'author' => 'Santoso, Budi & Pratama, Aditya',
            'year' => '2024',
            'title' => 'Implementasi Sistem Sensor',
            'journal' => 'Jurnal Teknologi',
            'volume' => '8',
            'issue' => '2',
            'pages' => '112-120',
            'doi' => '10.29207/resti.v8i2.5678',
        ];

        $entry = $this->service->formatBibliographyEntry($source, 'apa');

        $this->assertStringContainsString('Santoso, Budi & Pratama, Aditya (2024).', $entry);
        $this->assertStringContainsString('Implementasi Sistem Sensor.', $entry);
        $this->assertStringContainsString('<em>Jurnal Teknologi</em>, <em>8</em>(2), 112-120.', $entry);
        $this->assertStringContainsString('https://doi.org/10.29207/resti.v8i2.5678', $entry);
    }

    public function test_format_bibliography_entry_ieee(): void
    {
        $source = [
            'type' => 'journal',
            'author' => 'B. Santoso and A. Pratama',
            'year' => '2024',
            'title' => 'Water Quality Control System',
            'journal' => 'IEEE Sensors Journal',
            'volume' => '24',
            'issue' => '3',
            'pages' => '100-110',
            'doi' => '10.1109/JSEN.2024.12345',
        ];

        $entry = $this->service->formatBibliographyEntry($source, 'ieee', 1);

        $this->assertStringStartsWith('[1] B. Santoso and A. Pratama, "Water Quality Control System,"', $entry);
        $this->assertStringContainsString('<em>IEEE Sensors Journal</em>, vol. 24, no. 3, pp. 100-110, 2024', $entry);
        $this->assertStringContainsString('doi: https://doi.org/10.1109/JSEN.2024.12345', $entry);
    }

    public function test_generate_bibliography_html_apa_hanging_indent_and_alphabetical(): void
    {
        $sources = [
            [
                'id' => 'src-sugiyono',
                'type' => 'book',
                'author' => 'Sugiyono',
                'year' => '2024',
                'title' => 'Metode Penelitian',
                'publisher' => 'Alfabeta',
            ],
            [
                'id' => 'src-alvin',
                'type' => 'thesis',
                'author' => 'Alvin, Grand Putra',
                'year' => '2026',
                'title' => 'Prototipe Sensor TCS34725',
                'publisher' => 'Politeknik Negeri Samarinda',
            ],
        ];

        $html = $this->service->generateBibliographyHtml($sources, 'apa', 'DAFTAR PUSTAKA');

        $this->assertStringContainsString('data-bibliography-block="true"', $html);
        $this->assertStringContainsString('DAFTAR PUSTAKA', $html);
        $this->assertStringContainsString('pl-8 -indent-8', $html);
        $this->assertStringContainsString('citation-entry', $html);
        $this->assertStringContainsString('bibliography-entry', $html);

        // Alvin should appear before Sugiyono (alphabetical sorting)
        $posAlvin = strpos($html, 'Alvin, Grand Putra');
        $posSugiyono = strpos($html, 'Sugiyono');
        $this->assertLessThan($posSugiyono, $posAlvin);
    }

    public function test_generate_bibliography_html_ieee_numbered_brackets(): void
    {
        $sources = [
            [
                'id' => 'src-1',
                'author' => 'Sugiyono',
                'year' => '2024',
                'title' => 'Metode Penelitian',
                'publisher' => 'Alfabeta',
            ],
            [
                'id' => 'src-2',
                'author' => 'Santoso, B.',
                'year' => '2024',
                'title' => 'Sensor ESP32',
                'publisher' => 'IEEE',
            ],
        ];

        $html = $this->service->generateBibliographyHtml($sources, 'ieee', 'REFERENSI');

        $this->assertStringContainsString('REFERENSI', $html);
        $this->assertStringContainsString('[1]', $html);
        $this->assertStringContainsString('[2]', $html);
        $this->assertStringContainsString('data-citation-style="ieee"', $html);
    }
}
