<?php

namespace App\Http\Controllers;

use App\Services\AiService;
use App\Services\CitationService;
use App\Services\DocumentService;
use App\Services\TocService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class DocumentController extends Controller
{
    public function __construct(
        protected DocumentService $documentService,
        protected AiService $aiService,
        protected CitationService $citationService,
        protected TocService $tocService,
    ) {}

    /**
     * Display the document modification editor view.
     */
    public function index(): View
    {
        $defaultTitle = 'Rencana Proyek Penelitian 2026.docx';

        $defaultContent = <<<'HTML'
<h1>Rencana Proyek Penelitian 2026</h1>
<p class="lead">Panduan komprehensif implementasi teknologi berbasis naskah dan penyelarasan format dokumen otomatis.</p>
<hr />
<h2>1. Pendahuluan & Latar Belakang</h2>
<p>Dalam era komputasi modern, pengelolaan berkas dokumen teks sering kali menghadapi tantangan fragmentasi format antar aplikasi perkantoran konvensional seperti <strong>Microsoft Word</strong>, <strong>Google Documents</strong>, dan platform kreatif berbasis AI seperti <strong>Wordcraft</strong>. Integrasi sistem ini bertujuan untuk menyatukan kenyamanan visual, presisi tata letak kertas A4, serta kecerdasan asistensi penulisan ke dalam satu ruang kerja tunggal.</p>
<p>Dokumen ini dimuat secara otomatis dari berkas lokal, di mana struktur paragraf, tabel, gaya tipografi, dan hierarki judul disesuaikan secara instan tanpa kehilangan integritas data.</p>

<h2>2. Metodologi & Matriks Pengujian</h2>
<p>Berikut adalah tabel ringkasan matriks kompatibilitas format yang didukung oleh editor:</p>
<table class="w-full">
    <thead>
        <tr>
            <th>Format File</th>
            <th>Ekstensi</th>
            <th>Penyesuaian Otomatis</th>
            <th>Status Uji</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Microsoft Word</strong></td>
            <td><code>.docx</code>, <code>.doc</code></td>
            <td>Struktur paragraf, heading, tabel & gambar</td>
            <td><span class="text-emerald-600 font-semibold">Tervalidasi</span></td>
        </tr>
        <tr>
            <td><strong>Markdown</strong></td>
            <td><code>.md</code>, <code>.markdown</code></td>
            <td>Sintaks heading, blok kutipan, format inline</td>
            <td><span class="text-emerald-600 font-semibold">Tervalidasi</span></td>
        </tr>
        <tr>
            <td><strong>HyperText Markup</strong></td>
            <td><code>.html</code>, <code>.htm</code></td>
            <td>Sanitasi keamanan XSS & preservasi styling</td>
            <td><span class="text-emerald-600 font-semibold">Tervalidasi</span></td>
        </tr>
        <tr>
            <td><strong>Plain Text</strong></td>
            <td><code>.txt</code></td>
            <td>Normalisasi jarak baris dan indentasi</td>
            <td><span class="text-emerald-600 font-semibold">Tervalidasi</span></td>
        </tr>
    </tbody>
</table>

<h2>3. Fitur Utama & Konsep Desain</h2>
<ul>
    <li><strong>Ribbon Toolbar Microsoft Word</strong>: Pengaturan font, ukuran, warna sorot, indentasi, alignment, hingga pembuatan tabel interaktif.</li>
    <li><strong>Kenyamanan Google Documents</strong>: Judul naskah inline-editable, menu bubble instan saat menyorot teks, dan navigasi outline otomatis.</li>
    <li><strong>Asisten AI Wordcraft</strong>: Fitur lanjut penulisan, perluas narasi, parafrase multi-gaya, koreksi ejaan PUEBI, serta tanya jawab terhadap naskah.</li>
</ul>

<blockquote>
    "Teknologi terbaik adalah teknologi yang menyederhanakan alur kerja manusia, menghapus friksi format, dan membuka potensi kreativitas tanpa batas."
</blockquote>

<h2>4. Kesimpulan & Rekomendasi</h2>
<p>Sistem ini memberikan fleksibilitas penuh bagi pengguna untuk memuat dokumen lokal dalam format apa pun, memodifikasinya dengan presisi tinggi, dan mengekspornya kembali dalam bentuk Word (.docx), PDF cetak, Markdown, maupun berkas mandiri lainnya.</p>
HTML;

        $stats = $this->documentService->calculateStats($defaultContent);

        return view('editor', [
            'initialTitle' => $defaultTitle,
            'initialContent' => $defaultContent,
            'initialStats' => $stats,
        ]);
    }

    /**
     * Import a local document (file upload or raw content) and adapt format.
     */
    public function import(Request $request): JsonResponse
    {
        if ($request->hasFile('file')) {
            $request->validate([
                'file' => ['required', 'file', 'max:20480'], // max 20MB
            ]);

            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $result = $this->documentService->parseFile($file, $originalName);

            return response()->json($result);
        }

        if ($request->filled('raw_content')) {
            $request->validate([
                'raw_content' => ['required', 'string'],
                'filename' => ['nullable', 'string', 'max:255'],
            ]);

            $filename = $request->input('filename', 'dokumen_impor.txt');
            $result = $this->documentService->parseFile($request->input('raw_content'), $filename);

            return response()->json($result);
        }

        return response()->json([
            'success' => false,
            'message' => 'Berkas atau konten tidak ditemukan untuk diimpor.',
        ], 422);
    }

    /**
     * Export document into specified format.
     */
    public function export(Request $request): Response|JsonResponse
    {
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'format' => ['required', 'string', 'in:docx,doc,html,htm,markdown,md,txt,text,json'],
            'header_text' => ['nullable', 'string', 'max:500'],
            'footer_text' => ['nullable', 'string', 'max:500'],
        ]);

        $title = $request->input('title');
        $content = $request->input('content');
        $format = $request->input('format');
        $headerText = $request->input('header_text');
        $footerText = $request->input('footer_text');

        $layoutOptions = [
            'paper_size' => $request->input('paper_size'),
            'width_mm' => $request->input('width_mm'),
            'height_mm' => $request->input('height_mm'),
            'orientation' => $request->input('orientation'),
            'margin_preset' => $request->input('margin_preset'),
            'margins' => $request->input('margins'),
            'line_spacing' => $request->input('line_spacing'),
            'paragraph_spacing' => $request->input('paragraph_spacing'),
            'paragraph_indent' => $request->input('paragraph_indent'),
            'vertical_align' => $request->input('vertical_align'),
        ];

        $exported = $this->documentService->exportDocument($title, $content, $format, $headerText, $footerText, $layoutOptions);

        return response($exported['content'], 200, [
            'Content-Type' => $exported['mime'],
            'Content-Disposition' => "attachment; filename=\"{$exported['filename']}\"",
            'X-Document-Title' => rawurlencode($title),
            'X-Document-Format' => $format,
        ]);
    }

    /**
     * Process Wordcraft AI Assistant request.
     */
    public function aiAssistant(Request $request): JsonResponse
    {
        $request->validate([
            'action' => ['required', 'string', 'in:continue,elaborate,rewrite,grammar,summarize,brainstorm,chat'],
            'selection' => ['nullable', 'string'],
            'context' => ['nullable', 'string'],
            'prompt' => ['nullable', 'string'],
            'style' => ['nullable', 'string', 'in:academic,formal,creative,concise,persuasive'],
        ]);

        $action = $request->input('action');
        $selection = $request->input('selection', '');
        $context = $request->input('context', '');
        $prompt = $request->input('prompt', '');
        $style = $request->input('style', 'formal');

        $result = $this->aiService->process($action, $selection, $context, $prompt, $style);

        return response()->json(array_merge([
            'success' => true,
        ], $result));
    }

    /**
     * Search academic and web sources for citations and bibliography.
     */
    public function searchSources(Request $request): JsonResponse
    {
        $request->validate([
            'query' => ['required', 'string', 'min:1', 'max:255'],
            'type' => ['nullable', 'string', 'in:all,journal,book,conference,website,thesis,report'],
        ]);

        $query = $request->input('query');
        $type = $request->input('type');

        $results = $this->citationService->searchSources($query, $type);

        return response()->json([
            'success' => true,
            'query' => $query,
            'count' => count($results),
            'results' => $results,
        ]);
    }

    /**
     * Format a complete bibliography HTML block.
     */
    public function formatBibliography(Request $request): JsonResponse
    {
        $request->validate([
            'sources' => ['required', 'array'],
            'sources.*.title' => ['required', 'string'],
            'style' => ['nullable', 'string', 'in:apa,ieee,harvard,mla,chicago,vancouver'],
            'heading' => ['nullable', 'string', 'max:100'],
        ]);

        $sources = $request->input('sources');
        $style = $request->input('style', 'apa');
        $heading = $request->input('heading', 'DAFTAR PUSTAKA');

        $html = $this->citationService->generateBibliographyHtml($sources, $style, $heading);

        return response()->json([
            'success' => true,
            'style' => $style,
            'heading' => $heading,
            'html' => $html,
        ]);
    }

    /**
     * Generate Microsoft Word-style Table of Contents (Daftar Isi) block.
     */
    public function generateToc(Request $request): JsonResponse
    {
        $request->validate([
            'headings' => ['nullable', 'array'],
            'content_html' => ['nullable', 'string'],
            'heading' => ['nullable', 'string', 'max:100'],
            'style' => ['nullable', 'string', 'in:template,classic,modern,formal,simple'],
            'leader' => ['nullable', 'string', 'in:dots,dashes,line,none'],
            'levels' => ['nullable', 'integer', 'min:1', 'max:5'],
            'showPageNumbers' => ['nullable', 'boolean'],
            'rightAlignPageNumbers' => ['nullable', 'boolean'],
            'templateType' => ['nullable', 'string', 'in:auto1,auto2,manual,custom'],
        ]);

        $headings = $request->input('headings');
        $contentHtml = $request->input('content_html', '');
        $maxLevels = (int) $request->input('levels', 3);

        if (empty($headings) && ! empty($contentHtml)) {
            $headings = $this->tocService->extractHeadingsFromHtml($contentHtml, $maxLevels);
        }

        $headings = $headings ?: [];
        $options = [
            'heading' => $request->input('heading', 'DAFTAR ISI'),
            'style' => $request->input('style', 'template'),
            'leader' => $request->input('leader', 'dots'),
            'levels' => $maxLevels,
            'showPageNumbers' => $request->boolean('showPageNumbers', true),
            'rightAlignPageNumbers' => $request->boolean('rightAlignPageNumbers', true),
            'templateType' => $request->input('templateType', 'auto2'),
        ];

        $html = $this->tocService->generateTocHtml($headings, $options);

        return response()->json([
            'success' => true,
            'count' => count($headings),
            'headings' => $headings,
            'options' => $options,
            'html' => $html,
        ]);
    }

    /**
     * Update an existing Table of Contents block (page numbers only or entire table).
     */
    public function updateToc(Request $request): JsonResponse
    {
        $request->validate([
            'existing_toc_html' => ['required', 'string'],
            'headings' => ['nullable', 'array'],
            'content_html' => ['nullable', 'string'],
            'update_type' => ['required', 'string', 'in:page_numbers,entire'],
            'options' => ['nullable', 'array'],
        ]);

        $existingHtml = $request->input('existing_toc_html');
        $updateType = $request->input('update_type');
        $headings = $request->input('headings');
        $contentHtml = $request->input('content_html', '');
        $options = $request->input('options', []);

        $maxLevels = (int) ($options['levels'] ?? 3);

        if (empty($headings) && ! empty($contentHtml)) {
            $headings = $this->tocService->extractHeadingsFromHtml($contentHtml, $maxLevels);
        }

        $headings = $headings ?: [];

        $html = $this->tocService->updateTocHtml($existingHtml, $headings, $updateType, $options);

        return response()->json([
            'success' => true,
            'update_type' => $updateType,
            'count' => count($headings),
            'html' => $html,
        ]);
    }
}
