<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class DocumentService
{
    /**
     * Detect document format from file name, MIME type, or content sniffing.
     */
    public function detectFormat(string $filename, ?string $mime = null, ?string $content = null): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($extension, ['docx', 'doc'])) {
            return 'docx';
        }

        if (in_array($extension, ['md', 'markdown'])) {
            return 'markdown';
        }

        if (in_array($extension, ['html', 'htm'])) {
            return 'html';
        }

        if (in_array($extension, ['txt', 'text'])) {
            return 'txt';
        }

        if ($extension === 'json') {
            return 'json';
        }

        // MIME-based fallback
        if ($mime) {
            if (str_contains($mime, 'wordprocessingml') || str_contains($mime, 'msword')) {
                return 'docx';
            }
            if (str_contains($mime, 'html')) {
                return 'html';
            }
            if (str_contains($mime, 'markdown')) {
                return 'markdown';
            }
            if (str_contains($mime, 'json')) {
                return 'json';
            }
            if (str_contains($mime, 'text/plain')) {
                return 'txt';
            }
        }

        // Content sniffing fallback
        if ($content !== null) {
            $trimmed = trim($content);
            if (str_starts_with($trimmed, '{') && str_ends_with($trimmed, '}')) {
                return 'json';
            }
            if (str_contains($trimmed, '<html') || str_contains($trimmed, '<div') || str_contains($trimmed, '<p>')) {
                return 'html';
            }
            if (str_starts_with($trimmed, '# ') || str_contains($trimmed, '## ')) {
                return 'markdown';
            }
        }

        return 'txt';
    }

    /**
     * Clean and sanitize HTML to prevent XSS while preserving rich document markup.
     */
    public function sanitizeHtml(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        // Strip dangerous tags completely including their inner content
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);
        $html = preg_replace('/<iframe\b[^>]*>(.*?)<\/iframe>/is', '', $html);
        $html = preg_replace('/<object\b[^>]*>(.*?)<\/object>/is', '', $html);
        $html = preg_replace('/<embed\b[^>]*>(.*?)<\/embed>/is', '', $html);
        $html = preg_replace('/<applet\b[^>]*>(.*?)<\/applet>/is', '', $html);

        // Strip inline event handlers like onclick, onerror, onload, etc.
        $html = preg_replace('/\s+on[a-zA-Z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);

        // Strip javascript: or vbscript: or data: (except safe image data) in href/src
        $html = preg_replace('/(href|src)\s*=\s*["\']\s*(javascript|vbscript):[^"\']*["\']/i', '$1="#"', $html);
        $html = preg_replace('/href\s*=\s*["\']\s*data:[^"\']*["\']/i', 'href="#"', $html);

        // Allow only standard rich text tags
        $allowedTags = '<h1><h2><h3><h4><h5><h6><p><br><hr><b><strong><i><em><u><s><strike><del><sub><sup>' .
            '<ul><ol><li><blockquote><code><pre><table><thead><tbody><tfoot><tr><th><td>' .
            '<a><img><span><div><figure><figcaption><section><header><footer>';

        $sanitized = strip_tags($html, $allowedTags);

        return trim($sanitized);
    }

    /**
     * Parse raw markdown string into semantic HTML.
     */
    public function parseMarkdown(string $markdown): string
    {
        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $markdown));
        $html = [];
        $inList = false;
        $listType = null;
        $inCodeBlock = false;
        $codeBuffer = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // Code blocks ```
            if (str_starts_with($trimmed, '```')) {
                if ($inCodeBlock) {
                    $html[] = '<pre class="bg-gray-100 dark:bg-gray-800 p-4 rounded-md font-mono text-sm overflow-x-auto my-3"><code>' .
                        htmlspecialchars(implode("\n", $codeBuffer), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .
                        '</code></pre>';
                    $codeBuffer = [];
                    $inCodeBlock = false;
                } else {
                    if ($inList) {
                        $html[] = ($listType === 'ul') ? '</ul>' : '</ol>';
                        $inList = false;
                    }
                    $inCodeBlock = true;
                }
                continue;
            }

            if ($inCodeBlock) {
                $codeBuffer[] = $line;
                continue;
            }

            // Empty line
            if ($trimmed === '') {
                if ($inList) {
                    $html[] = ($listType === 'ul') ? '</ul>' : '</ol>';
                    $inList = false;
                }
                continue;
            }

            // Headings
            if (preg_match('/^(#{1,6})\s+(.*)$/', $trimmed, $matches)) {
                if ($inList) {
                    $html[] = ($listType === 'ul') ? '</ul>' : '</ol>';
                    $inList = false;
                }
                $level = strlen($matches[1]);
                $headingText = $this->formatInlineMarkdown(htmlspecialchars($matches[2], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
                $html[] = "<h{$level}>{$headingText}</h{$level}>";
                continue;
            }

            // Horizontal Rule
            if (preg_match('/^(\*{3,}|-{3,}|_{3,})$/', $trimmed)) {
                if ($inList) {
                    $html[] = ($listType === 'ul') ? '</ul>' : '</ol>';
                    $inList = false;
                }
                $html[] = '<hr class="my-4 border-gray-300 dark:border-gray-700" />';
                continue;
            }

            // Blockquote
            if (str_starts_with($trimmed, '>')) {
                if ($inList) {
                    $html[] = ($listType === 'ul') ? '</ul>' : '</ol>';
                    $inList = false;
                }
                $quoteText = $this->formatInlineMarkdown(htmlspecialchars(trim(substr($trimmed, 1)), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
                $html[] = "<blockquote class=\"border-l-4 border-indigo-500 pl-4 py-1 italic my-3 text-gray-700 dark:text-gray-300\">{$quoteText}</blockquote>";
                continue;
            }

            // Bullet List (- or *)
            if (preg_match('/^[\*\-]\s+(.*)$/', $trimmed, $matches)) {
                if (!$inList || $listType !== 'ul') {
                    if ($inList) {
                        $html[] = '</ol>';
                    }
                    $html[] = '<ul class="list-disc pl-6 my-2 space-y-1">';
                    $inList = true;
                    $listType = 'ul';
                }
                $itemText = $this->formatInlineMarkdown(htmlspecialchars($matches[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
                $html[] = "<li>{$itemText}</li>";
                continue;
            }

            // Numbered List (1. )
            if (preg_match('/^\d+\.\s+(.*)$/', $trimmed, $matches)) {
                if (!$inList || $listType !== 'ol') {
                    if ($inList) {
                        $html[] = '</ul>';
                    }
                    $html[] = '<ol class="list-decimal pl-6 my-2 space-y-1">';
                    $inList = true;
                    $listType = 'ol';
                }
                $itemText = $this->formatInlineMarkdown(htmlspecialchars($matches[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
                $html[] = "<li>{$itemText}</li>";
                continue;
            }

            // Regular paragraph
            if ($inList) {
                $html[] = ($listType === 'ul') ? '</ul>' : '</ol>';
                $inList = false;
            }
            $pText = $this->formatInlineMarkdown(htmlspecialchars($trimmed, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
            $html[] = "<p>{$pText}</p>";
        }

        if ($inList) {
            $html[] = ($listType === 'ul') ? '</ul>' : '</ol>';
        }

        if ($inCodeBlock && !empty($codeBuffer)) {
            $html[] = '<pre class="bg-gray-100 dark:bg-gray-800 p-4 rounded-md font-mono text-sm overflow-x-auto my-3"><code>' .
                htmlspecialchars(implode("\n", $codeBuffer), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .
                '</code></pre>';
        }

        return implode("\n", $html);
    }

    /**
     * Format inline markdown (bold, italic, code, link, image).
     */
    private function formatInlineMarkdown(string $text): string
    {
        $text = preg_replace('/(\*\*|__)(.*?)\1/', '<strong>$2</strong>', $text);
        $text = preg_replace('/(\*|_)(.*?)\1/', '<em>$2</em>', $text);
        $text = preg_replace('/~~(.*?)~~/', '<del>$1</del>', $text);
        $text = preg_replace('/`([^`]+)`/', '<code class="bg-gray-100 dark:bg-gray-800 px-1 py-0.5 rounded text-sm font-mono text-pink-600">$1</code>', $text);
        $text = preg_replace('/!\[(.*?)\]\((.*?)\)/', '<img src="$2" alt="$1" class="max-w-full h-auto rounded my-2 inline-block" />', $text);
        $text = preg_replace('/\[(.*?)\]\((.*?)\)/', '<a href="$2" class="text-blue-600 dark:text-blue-400 underline" target="_blank" rel="noopener noreferrer">$1</a>', $text);

        return $text;
    }

    /**
     * Convert HTML content into structured Markdown.
     */
    public function convertToMarkdown(string $html): string
    {
        $text = $html;

        // Headings
        for ($i = 6; $i >= 1; $i--) {
            $hashes = str_repeat('#', $i);
            $text = preg_replace("/<h{$i}[^>]*>(.*?)<\/h{$i}>/is", "\n\n{$hashes} $1\n\n", $text);
        }

        // Blockquotes
        $text = preg_replace('/<blockquote[^>]*>(.*?)<\/blockquote>/is', "\n\n> $1\n\n", $text);

        // Code blocks
        $text = preg_replace('/<pre[^>]*><code[^>]*>(.*?)<\/code><\/pre>/is', "\n\n```\n$1\n```\n\n", $text);
        $text = preg_replace('/<code[^>]*>(.*?)<\/code>/is', '`$1`', $text);

        // Bold and Italic
        $text = preg_replace('/<(b|strong)[^>]*>(.*?)<\/\1>/is', '**$2**', $text);
        $text = preg_replace('/<(i|em)[^>]*>(.*?)<\/\1>/is', '*$2*', $text);
        $text = preg_replace('/<(s|strike|del)[^>]*>(.*?)<\/\1>/is', '~~$2~~', $text);

        // Links
        $text = preg_replace('/<a\b[^>]*href=["\']([^"\']*)["\'][^>]*>(.*?)<\/a>/is', '[$2]($1)', $text);

        // Images
        $text = preg_replace('/<img\b[^>]*src=["\']([^"\']*)["\'][^>]*alt=["\']([^"\']*)["\'][^>]*>/is', '![$2]($1)', $text);
        $text = preg_replace('/<img\b[^>]*src=["\']([^"\']*)["\'][^>]*>/is', '![]($1)', $text);

        // Lists
        $text = preg_replace('/<li[^>]*>(.*?)<\/li>/is', "\n- $1", $text);
        $text = preg_replace('/<[uo]l[^>]*>(.*?)<\/[uo]l>/is', "$1\n", $text);

        // Horizontal rules
        $text = preg_replace('/<hr[^>]*>/is', "\n\n---\n\n", $text);

        // Paragraphs and breaks
        $text = preg_replace('/<p[^>]*>(.*?)<\/p>/is', "\n\n$1\n\n", $text);
        $text = preg_replace('/<br\s*\/?>/is', "\n", $text);

        // Strip remaining HTML tags
        $text = strip_tags($text);

        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Clean up excessive blank lines
        $text = preg_replace("/\n{3,}/", "\n\n", $text);

        return trim($text);
    }

    /**
     * Convert HTML content into clean plain text.
     */
    public function convertToPlainText(string $html): string
    {
        $text = preg_replace('/<br\s*\/?>/i', "\n", $html);
        $text = preg_replace('/<\/p>/i', "\n\n", $text);
        $text = preg_replace('/<\/h[1-6]>/i', "\n\n", $text);
        $text = preg_replace('/<\/li>/i', "\n", $text);
        $text = preg_replace('/<\/tr>/i', "\n", $text);
        $text = preg_replace('/<td[^>]*>/i', "\t", $text);

        $plain = strip_tags($text);
        $plain = html_entity_decode($plain, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plain = preg_replace("/[ \t]+/", ' ', $plain);
        $plain = preg_replace("/\n{3,}/", "\n\n", $plain);

        return trim($plain);
    }

    /**
     * Calculate document statistics.
     */
    public function calculateStats(string $html): array
    {
        $plain = $this->convertToPlainText($html);

        $charCount = mb_strlen($plain, 'UTF-8');
        $charWithoutSpaces = mb_strlen(preg_replace('/\s+/', '', $plain), 'UTF-8');

        // Words
        $words = preg_split('/[\s\p{Z}\p{P}]+/u', $plain, -1, PREG_SPLIT_NO_EMPTY);
        $wordCount = is_array($words) ? count($words) : 0;

        // Paragraphs
        $paragraphs = preg_split('/\n\s*\n/', trim($plain), -1, PREG_SPLIT_NO_EMPTY);
        $paragraphCount = is_array($paragraphs) ? count($paragraphs) : 0;

        // Reading time: 200 words per minute average
        $readingTimeMinutes = max(1, (int) ceil($wordCount / 200));

        // Pages estimate: ~300 words per A4 page standard
        $pageEstimate = max(1, (int) ceil($wordCount / 300));

        return [
            'word_count' => $wordCount,
            'char_count' => $charCount,
            'char_without_spaces' => $charWithoutSpaces,
            'paragraph_count' => $paragraphCount,
            'reading_time_minutes' => $readingTimeMinutes,
            'reading_time' => $readingTimeMinutes . ' min baca',
            'page_estimate' => $pageEstimate,
        ];
    }

    /**
     * Extract title candidate from filename or HTML content.
     */
    public function extractTitle(string $filename, string $html): string
    {
        // Try to get from first H1 tag in content
        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $html, $matches)) {
            $heading = trim(strip_tags($matches[1]));
            if (!empty($heading)) {
                return $heading;
            }
        }

        // Try to get from filename without extension
        $basename = pathinfo($filename, PATHINFO_FILENAME);
        if (!empty($basename)) {
            $readable = preg_replace('/[-_]+/', ' ', $basename);
            return ucwords(trim($readable));
        }

        return 'Dokumen Tanpa Judul';
    }

    /**
     * Intelligently adapt document structure, promoting headings, centering cover page elements,
     * preserving existing heading/list elements, and applying academic formatting presets.
     */
    public function adaptDocumentStructure(string $html, string $filename = ''): array
    {
        $sanitized = $this->sanitizeHtml($html);
        $isAcademic = false;

        $checkText = strtolower($filename . ' ' . strip_tags($sanitized));
        if (
            str_contains($checkText, 'skripsi') ||
            str_contains($checkText, 'tesis') ||
            str_contains($checkText, 'disertasi') ||
            str_contains($checkText, 'politeknik') ||
            str_contains($checkText, 'universitas') ||
            str_contains($checkText, 'nim.') ||
            str_contains($checkText, 'bab i') ||
            str_contains($checkText, 'latar belakang')
        ) {
            $isAcademic = true;
        }

        // Split HTML by major block closing tags
        $tokens = preg_split('/(<\/(?:h[1-6]|p|div|blockquote|table|ul|ol|pre)>)/i', $sanitized, -1, PREG_SPLIT_DELIM_CAPTURE);
        $adaptedBlocks = [];
        $headings = [];
        $inCoverPage = $isAcademic;
        $coverParagraphCount = 0;

        for ($i = 0; $i < count($tokens); $i += 2) {
            $block = trim($tokens[$i]);
            $closer = $tokens[$i + 1] ?? '';
            if (empty($block) && empty($closer)) {
                continue;
            }

            $fullTag = $block . $closer;
            $innerText = trim(strip_tags($fullTag));

            // If it's already an H1, H2, H3, H4, H5, H6 tag, PRESERVE IT and record heading!
            if (preg_match('/^<h([1-6])\b([^>]*)>(.*?)<\/h\1>$/is', $fullTag, $m)) {
                $lvl = (int) $m[1];
                $hId = 'heading-' . count($headings);
                $headings[] = ['id' => $hId, 'level' => $lvl, 'text' => $innerText];
                $hClass = $lvl === 1 ? 'heading-chapter font-bold text-xl my-6' : ($lvl === 2 ? 'heading-section font-bold text-lg my-4' : 'heading-subsection font-semibold text-base my-3');
                $adaptedBlocks[] = "<h{$lvl} id=\"{$hId}\" class=\"{$hClass} text-gray-900 dark:text-gray-100\">{$m[3]}</h{$lvl}>";
                $inCoverPage = false;
                continue;
            }

            // If it's a list (ul or ol), PRESERVE IT!
            if (str_starts_with($fullTag, '<ul') || str_starts_with($fullTag, '<ol')) {
                $adaptedBlocks[] = $fullTag;
                $inCoverPage = false;
                continue;
            }

            // If it's a table, PRESERVE IT with borders!
            if (str_starts_with($fullTag, '<table')) {
                $adaptedBlocks[] = preg_replace(
                    '/<table\b([^>]*)>/i',
                    '<table $1 class="w-full border-collapse my-4 text-sm border border-gray-300 dark:border-gray-700">',
                    $fullTag
                );
                $inCoverPage = false;
                continue;
            }

            if ($innerText === '' && !str_contains($fullTag, '<img')) {
                continue;
            }

            // Check if we hit a chapter heading or preliminary section, which ends cover page immediately
            if ($inCoverPage && (
                preg_match('/^(BAB\s+[IVXLCDM]+|\bBAB\s+\d+)/i', $innerText) ||
                preg_match('/^(SKRIPSI|THESIS|ABSTRAK|ABSTRACT|KATA\s+PENGANTAR|PRAKATA|LEMBAR\s+PENGESAHAN|HALAMAN\s+PENGESAHAN|LEMBAR\s+PERSETUJUAN|HALAMAN\s+PERSETUJUAN|PERNYATAAN\s+KEASLIAN|PERNYATAAN\s+ORISINALITAS|DAFTAR\s+ISI|DAFTAR\s+TABEL|DAFTAR\s+GAMBAR|DAFTAR\s+PUSTAKA)(\s+.*)?$/i', $innerText)
            )) {
                $inCoverPage = false;
                $adaptedBlocks[] = '<div class="page-break my-8 border-b-2 border-dashed border-gray-400 dark:border-gray-600 text-center py-2 select-none" data-page-break="true"><span class="bg-gray-100 dark:bg-gray-800 px-3 py-1 rounded-full text-xs text-gray-500 font-mono">Pemisah Halaman (Akhir Sampul)</span></div>';
            }

            // Cover page processing for academic papers
            if ($inCoverPage) {
                $coverParagraphCount++;

                if (!empty($innerText)) {
                    $adaptedBlocks[] = "<p class=\"text-center font-bold text-gray-900 dark:text-gray-100 leading-snug my-2\">{$innerText}</p>";
                } elseif (str_contains($fullTag, '<img')) {
                    $adaptedBlocks[] = preg_replace('/<img\b([^>]*)>/i', '<figure class="my-6 text-center"><img $1 class="mx-auto max-h-44 object-contain" /></figure>', $fullTag);
                }

                if (preg_match('/\b(19|20)\d{2}\b/', $innerText) || $coverParagraphCount > 12) {
                    $inCoverPage = false;
                    $adaptedBlocks[] = '<div class="page-break my-8 border-b-2 border-dashed border-gray-400 dark:border-gray-600 text-center py-2 select-none" data-page-break="true"><span class="bg-gray-100 dark:bg-gray-800 px-3 py-1 rounded-full text-xs text-gray-500 font-mono">Pemisah Halaman (Akhir Sampul)</span></div>';
                }
                continue;
            }

            // Chapter Headings: BAB I, BAB II, etc. (Handles single line or two-line chapter title)
            if (preg_match('/^(BAB\s+[IVXLCDM]+|\bBAB\s+\d+)(.*)$/i', $innerText, $bm)) {
                $chapterPrefix = trim($bm[1]);
                $chapterSuffix = trim($bm[2]);

                // If suffix is empty, look ahead at the next token to see if it's the chapter title (e.g. PENDAHULUAN)
                if (empty($chapterSuffix) && isset($tokens[$i + 2])) {
                    $nextCandidate = trim(strip_tags($tokens[$i + 2]));
                    if (!empty($nextCandidate) && strlen($nextCandidate) < 100 && !str_starts_with($nextCandidate, '<') && !preg_match('/^\d+\.\d+/', $nextCandidate)) {
                        $titleText = $chapterPrefix . ' ' . $nextCandidate;
                        $i += 2; // consume next token
                    } else {
                        $titleText = $chapterPrefix;
                    }
                } else {
                    $titleText = trim($innerText);
                }

                $hId = 'heading-' . count($headings);
                $headings[] = ['id' => $hId, 'level' => 1, 'text' => $titleText];

                $adaptedBlocks[] = "<div class=\"page-break my-6 border-b border-dashed border-gray-300 dark:border-gray-700 text-center py-1 select-none\" data-page-break=\"true\"><span class=\"text-[10px] text-gray-400 font-mono\">Awal Bab Baru</span></div>";
                $adaptedBlocks[] = "<h1 id=\"{$hId}\" class=\"heading-chapter text-center font-bold text-xl my-6 text-gray-900 dark:text-gray-100\">{$titleText}</h1>";
                continue;
            }

            // Academic Preliminaries: ABSTRAK, DAFTAR ISI, KATA PENGANTAR, LEMBAR PENGESAHAN, etc.
            if (
                preg_match('/^(SKRIPSI|THESIS|ABSTRAK|ABSTRACT|KATA\s+PENGANTAR|PRAKATA|LEMBAR\s+PENGESAHAN|HALAMAN\s+PENGESAHAN|LEMBAR\s+PERSETUJUAN|HALAMAN\s+PERSETUJUAN|PERNYATAAN\s+KEASLIAN|PERNYATAAN\s+ORISINALITAS|DAFTAR\s+ISI|DAFTAR\s+TABEL|DAFTAR\s+GAMBAR|DAFTAR\s+LAMPIRAN|DAFTAR\s+PUSTAKA|RIWAYAT\s+HIDUP|BIODATA\s+PENULIS|LAMPIRAN)(\s+.*)?$/i', $innerText) &&
                strlen($innerText) <= 60 &&
                !preg_match('/[.!?]$/', $innerText)
            ) {
                $titleText = strtoupper(trim($innerText));
                $hId = 'heading-' . count($headings);
                $headings[] = ['id' => $hId, 'level' => 1, 'text' => $titleText];

                $adaptedBlocks[] = "<div class=\"page-break my-6 border-b border-dashed border-gray-300 dark:border-gray-700 text-center py-1 select-none\" data-page-break=\"true\"><span class=\"text-[10px] text-gray-400 font-mono\">Halaman Baru</span></div>";
                $adaptedBlocks[] = "<h1 id=\"{$hId}\" class=\"heading-preliminary text-center font-bold text-xl my-6 text-gray-900 dark:text-gray-100\">{$titleText}</h1>";
                continue;
            }

            // Subsections: 1.1 Latar Belakang, 2.1 Teori
            if (preg_match('/^(\d+\.\d+)\s+(.+)$/', $innerText)) {
                $titleText = trim($innerText);
                $hId = 'heading-' . count($headings);
                $headings[] = ['id' => $hId, 'level' => 2, 'text' => $titleText];

                $adaptedBlocks[] = "<h2 id=\"{$hId}\" class=\"heading-section font-bold text-lg my-4 text-gray-900 dark:text-gray-100\">{$titleText}</h2>";
                continue;
            }

            // Sub-subsections: 1.1.1, 2.1.1
            if (preg_match('/^(\d+\.\d+\.\d+)\s+(.+)$/', $innerText)) {
                $titleText = trim($innerText);
                $hId = 'heading-' . count($headings);
                $headings[] = ['id' => $hId, 'level' => 3, 'text' => $titleText];

                $adaptedBlocks[] = "<h3 id=\"{$hId}\" class=\"heading-subsection font-semibold text-base my-3 text-gray-800 dark:text-gray-200\">{$titleText}</h3>";
                continue;
            }

            // Normal paragraphs
            if (!empty($innerText)) {
                if ($isAcademic) {
                    $adaptedBlocks[] = "<p class=\"text-justify my-3 leading-relaxed text-gray-800 dark:text-gray-200 indent-8\">{$innerText}</p>";
                } else {
                    $adaptedBlocks[] = "<p class=\"my-2 leading-relaxed text-gray-800 dark:text-gray-200\">{$innerText}</p>";
                }
            }
        }

        $adaptedHtml = implode("\n", $adaptedBlocks);

        $presets = [
            'is_academic' => $isAcademic,
            'font_family' => $isAcademic ? 'Times New Roman, serif' : 'Instrument Sans, sans-serif',
            'font_size' => $isAcademic ? '12pt' : '11pt',
            'line_spacing' => '1.5',
            'margins' => $isAcademic ? 'skripsi' : 'normal',
            'margin_details' => $isAcademic
                ? ['top' => '4cm', 'left' => '4cm', 'right' => '3cm', 'bottom' => '3cm']
                : ['top' => '2.54cm', 'left' => '2.54cm', 'right' => '2.54cm', 'bottom' => '2.54cm'],
            'orientation' => 'portrait',
            'headings_found' => count($headings),
        ];

        return [
            'adapted_html' => $adaptedHtml,
            'headings' => $headings,
            'presets' => $presets,
        ];
    }

    /**
     * Parse uploaded file or raw string content and adapt document format.
     */
    public function parseFile(UploadedFile|string $file, ?string $originalName = null): array
    {
        if ($file instanceof UploadedFile) {
            $filename = $originalName ?? $file->getClientOriginalName();
            $mime = $file->getClientMimeType();
            $rawContent = file_get_contents($file->getRealPath());
        } else {
            $filename = $originalName ?? 'document.txt';
            $mime = null;
            $rawContent = $file;
        }

        $format = $this->detectFormat($filename, $mime, $rawContent);
        $contentHtml = '';

        switch ($format) {
            case 'markdown':
                $contentHtml = $this->parseMarkdown($rawContent);
                break;

            case 'html':
                $contentHtml = $this->sanitizeHtml($rawContent);
                break;

            case 'json':
                $decoded = json_decode($rawContent, true);
                if (is_array($decoded)) {
                    $contentHtml = $decoded['content'] ?? $decoded['html'] ?? '';
                    $titleCandidate = $decoded['title'] ?? null;
                    if (isset($titleCandidate)) {
                        $filename = $titleCandidate;
                    }
                } else {
                    $contentHtml = '<p>' . htmlspecialchars($rawContent, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>';
                }
                break;

            case 'docx':
                $contentHtml = $this->extractDocxText($file);
                break;

            case 'txt':
            default:
                $paragraphs = preg_split('/\n\s*\n/', trim($rawContent), -1, PREG_SPLIT_NO_EMPTY);
                if (!empty($paragraphs)) {
                    $contentHtml = implode("\n", array_map(function ($p) {
                        return '<p>' . nl2br(htmlspecialchars(trim($p), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) . '</p>';
                    }, $paragraphs));
                } else {
                    $contentHtml = '<p>' . nl2br(htmlspecialchars(trim($rawContent), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) . '</p>';
                }
                break;
        }

        // Apply intelligent document structure adaptation
        $adapted = $this->adaptDocumentStructure($contentHtml, $filename);
        $contentHtml = !empty($adapted['adapted_html']) ? $adapted['adapted_html'] : $contentHtml;

        $title = $this->extractTitle($filename, $contentHtml);
        $stats = $this->calculateStats($contentHtml);

        return [
            'success' => true,
            'title' => $title,
            'filename' => $filename,
            'format' => $format,
            'content_html' => $contentHtml,
            'headings' => $adapted['headings'],
            'stats' => $stats,
            'adapted_settings' => $adapted['presets'],
            'message' => $adapted['presets']['is_academic']
                ? "Format Skripsi/Akademik terdeteksi! Margin 4-4-3-3 cm, Font Times New Roman 12pt, Spasi 1.5, dan {$adapted['presets']['headings_found']} Bab/Subbab otomatis diselaraskan."
                : "Format {$format} berhasil dideteksi dan disesuaikan.",
        ];
    }

    /**
     * Fallback extractor for docx files on server-side when mammoth is not running in node.
     */
    private function extractDocxText(UploadedFile|string $file): string
    {
        $filePath = ($file instanceof UploadedFile) ? $file->getRealPath() : (is_string($file) && file_exists($file) ? $file : null);
        $raw = $filePath && file_exists($filePath) ? @file_get_contents($filePath) : (is_string($file) ? $file : '');

        if ($filePath && class_exists('\ZipArchive')) {
            $zip = new \ZipArchive();
            if ($zip->open($filePath) === true) {
                $xml = $zip->getFromName('word/document.xml');
                $zip->close();

                if ($xml !== false) {
                    $dom = new \DOMDocument();
                    @$dom->loadXML($xml, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
                    $xpath = new \DOMXPath($dom);
                    $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

                    $paragraphs = $xpath->query('//w:p');
                    $htmlParas = [];

                    foreach ($paragraphs as $p) {
                        $runs = $xpath->query('.//w:r', $p);
                        $paraText = '';
                        $isHeading = false;

                        $pStyle = $xpath->query('.//w:pStyle/@w:val', $p);
                        if ($pStyle->length > 0) {
                            $styleVal = strtolower($pStyle->item(0)->nodeValue);
                            if (str_contains($styleVal, 'heading1') || str_contains($styleVal, 'title')) {
                                $isHeading = 1;
                            } elseif (str_contains($styleVal, 'heading2')) {
                                $isHeading = 2;
                            } elseif (str_contains($styleVal, 'heading3')) {
                                $isHeading = 3;
                            }
                        }

                        foreach ($runs as $r) {
                            $textNode = $xpath->query('.//w:t', $r);
                            if ($textNode->length > 0) {
                                $t = $textNode->item(0)->nodeValue;
                                $isBold = $xpath->query('.//w:b', $r)->length > 0;
                                $isItalic = $xpath->query('.//w:i', $r)->length > 0;

                                $escaped = htmlspecialchars($t, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                                if ($isBold) {
                                    $escaped = "<strong>{$escaped}</strong>";
                                }
                                if ($isItalic) {
                                    $escaped = "<em>{$escaped}</em>";
                                }
                                $paraText .= $escaped;
                            }
                        }

                        $trimmed = trim($paraText);
                        if ($trimmed !== '') {
                            if ($isHeading === 1) {
                                $htmlParas[] = "<h1>{$trimmed}</h1>";
                            } elseif ($isHeading === 2) {
                                $htmlParas[] = "<h2>{$trimmed}</h2>";
                            } elseif ($isHeading === 3) {
                                $htmlParas[] = "<h3>{$trimmed}</h3>";
                            } else {
                                $htmlParas[] = "<p>{$trimmed}</p>";
                            }
                        }
                    }

                    if (!empty($htmlParas)) {
                        return implode("\n", $htmlParas);
                    }
                }
            }
        }

        // If not a valid zip archive (e.g. simulated docx or text/html formatted content)
        if (!empty($raw) && (str_contains($raw, '<p>') || str_contains($raw, '<div') || str_contains($raw, 'BAB '))) {
            return $raw;
        }

        return '<p>Dokumen Microsoft Word berhasil diimpor.</p>';
    }

    /**
     * Export document into requested format.
     */
    public function exportDocument(string $title, string $html, string $format): array
    {
        $safeTitle = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $title);
        if (empty($safeTitle)) {
            $safeTitle = 'dokumen';
        }

        $sanitizedHtml = $this->sanitizeHtml($html);

        switch (strtolower($format)) {
            case 'markdown':
            case 'md':
                $content = "# {$title}\n\n" . $this->convertToMarkdown($sanitizedHtml);
                return [
                    'content' => $content,
                    'mime' => 'text/markdown; charset=UTF-8',
                    'filename' => "{$safeTitle}.md",
                ];

            case 'txt':
            case 'text':
                $content = "{$title}\n" . str_repeat('=', mb_strlen($title)) . "\n\n" . $this->convertToPlainText($sanitizedHtml);
                return [
                    'content' => $content,
                    'mime' => 'text/plain; charset=UTF-8',
                    'filename' => "{$safeTitle}.txt",
                ];

            case 'json':
                $stats = $this->calculateStats($sanitizedHtml);
                $content = json_encode([
                    'title' => $title,
                    'content' => $sanitizedHtml,
                    'plain_text' => $this->convertToPlainText($sanitizedHtml),
                    'stats' => $stats,
                    'exported_at' => now()->toIso8601String(),
                    'app' => 'VelScribe Wordcraft Studio',
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                return [
                    'content' => $content,
                    'mime' => 'application/json; charset=UTF-8',
                    'filename' => "{$safeTitle}.json",
                ];

            case 'docx':
            case 'doc':
                $wordContent = <<<XML
<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
<head>
<meta charset='utf-8'>
<title>{$title}</title>
<style>
@page {
    size: 210mm 297mm;
    margin: 40mm 30mm 30mm 40mm;
}
body {
    font-family: 'Times New Roman', 'Calibri', serif;
    font-size: 12pt;
    line-height: 1.5;
    color: #000000;
}
h1 {
    font-size: 14pt;
    font-weight: bold;
    text-align: center;
    margin-top: 18pt;
    margin-bottom: 12pt;
    text-transform: uppercase;
}
h2 {
    font-size: 12pt;
    font-weight: bold;
    margin-top: 14pt;
    margin-bottom: 6pt;
}
h3 {
    font-size: 12pt;
    font-weight: bold;
    margin-top: 10pt;
    margin-bottom: 4pt;
}
p {
    margin-bottom: 6pt;
    text-align: justify;
    text-indent: 1.25cm;
}
p.text-center {
    text-align: center;
    text-indent: 0;
}
table {
    border-collapse: collapse;
    width: 100%;
    margin: 12pt 0;
}
th, td {
    border: 1px solid #000000;
    padding: 6pt 8pt;
}
th {
    background-color: #f2f2f2;
    font-weight: bold;
    text-align: center;
}
</style>
</head>
<body>
{$sanitizedHtml}
</body>
</html>
XML;
                return [
                    'content' => $wordContent,
                    'mime' => 'application/msword; charset=UTF-8',
                    'filename' => "{$safeTitle}.doc",
                ];

            case 'html':
            default:
                $fullHtml = <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 12pt; line-height: 1.6; max-width: 210mm; margin: 40px auto; padding: 40mm 30mm 30mm 40mm; color: #111827; }
        h1 { font-size: 16pt; font-weight: bold; text-align: center; margin-top: 24px; margin-bottom: 16px; text-transform: uppercase; }
        h2 { font-size: 13pt; font-weight: bold; margin-top: 20px; margin-bottom: 10px; }
        h3 { font-size: 12pt; font-weight: bold; margin-top: 16px; margin-bottom: 8px; }
        p { text-align: justify; margin-bottom: 10px; }
        p.text-center { text-align: center; }
        table { border-collapse: collapse; width: 100%; margin: 16px 0; }
        th, td { border: 1px solid #374151; padding: 8px 12px; }
        th { background: #f3f4f6; font-weight: bold; text-align: center; }
        img { max-width: 100%; height: auto; display: block; margin: 16px auto; }
    </style>
</head>
<body>
    {$sanitizedHtml}
</body>
</html>
HTML;
                return [
                    'content' => $fullHtml,
                    'mime' => 'text/html; charset=UTF-8',
                    'filename' => "{$safeTitle}.html",
                ];
        }
    }
}
