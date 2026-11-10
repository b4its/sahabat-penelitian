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
        $allowedTags = '<h1><h2><h3><h4><h5><h6><p><br><hr><b><strong><i><em><u><s><strike><del><sub><sup>'.
            '<ul><ol><li><blockquote><code><pre><table><thead><tbody><tfoot><tr><th><td>'.
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
                    $html[] = '<pre class="bg-gray-100 dark:bg-gray-800 p-4 rounded-md font-mono text-sm overflow-x-auto my-3"><code>'.
                        htmlspecialchars(implode("\n", $codeBuffer), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').
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
                if (! $inList || $listType !== 'ul') {
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
                if (! $inList || $listType !== 'ol') {
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

        if ($inCodeBlock && ! empty($codeBuffer)) {
            $html[] = '<pre class="bg-gray-100 dark:bg-gray-800 p-4 rounded-md font-mono text-sm overflow-x-auto my-3"><code>'.
                htmlspecialchars(implode("\n", $codeBuffer), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').
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
            'reading_time' => $readingTimeMinutes.' min baca',
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
            if (! empty($heading)) {
                return $heading;
            }
        }

        // Try to get from filename without extension
        $basename = pathinfo($filename, PATHINFO_FILENAME);
        if (! empty($basename)) {
            $readable = preg_replace('/[-_]+/', ' ', $basename);

            return ucwords(trim($readable));
        }

        return 'Dokumen Tanpa Judul';
    }

    /**
     * Check if a given line belongs to a Table of Contents (Daftar Isi).
     */
    private function isTableOfContentsLine(string $text): bool
    {
        if (empty($text)) {
            return false;
        }
        $clean = trim($text);
        if (empty($clean)) {
            return false;
        }
        if (preg_match('/^(NIM|NPM|NIS|NIK|OLEH|DISUSUN|BY|PROGRAM\s+STUDI|JURUSAN|FAKULTAS|POLITEKNIK|UNIVERSITAS|DEPARTMENT|STUDY\s+PROGRAM|SEKOLAH\s+TINGGI|INSTITUT|SEBAGAI\s+SALAH\s+SATU\s+SYARAT|SUBMITTED\s+AS)\b/i', $clean)) {
            return false;
        }
        if (preg_match('/(\.{2,}|…|·{2,}|\.\s*\.\s*\.)/', $clean)) {
            return true;
        }
        if (preg_match('/[\s\t]{2,}(\d+|[ivxlcdm]+)$/i', $clean)) {
            return true;
        }

        return false;
    }

    /**
     * Check if text belongs to cover metadata that must never be a heading.
     */
    private function isCoverMetadataLine(string $text): bool
    {
        if (empty($text)) {
            return false;
        }

        return (bool) preg_match('/^(OLEH|DISUSUN|BY|NIM|NPM|NIS|NIK|PROGRAM\s+STUDI|JURUSAN|FAKULTAS|POLITEKNIK|UNIVERSITAS|DEPARTMENT|STUDY\s+PROGRAM|SEKOLAH\s+TINGGI|INSTITUT|SEBAGAI\s+SALAH\s+SATU\s+SYARAT|SUBMITTED\s+AS|KEMENTERIAN|\d{4})\b/i', trim($text));
    }

    /**
     * Intelligently adapt document structure, promoting headings, centering cover page elements,
     * preserving existing heading/list elements, and applying academic formatting presets.
     */
    public function adaptDocumentStructure(string $html, string $filename = ''): array
    {
        $sanitized = $this->sanitizeHtml($html);
        $isAcademic = false;

        $checkFilename = strtolower($filename);
        $checkBody = strtolower(strip_tags($sanitized));

        if (
            str_contains($checkFilename, 'skripsi') ||
            str_contains($checkFilename, 'tesis') ||
            str_contains($checkFilename, 'thesis') ||
            str_contains($checkFilename, 'disertasi') ||
            str_contains($checkFilename, 'tugas-akhir') ||
            str_contains($checkFilename, 'tugas_akhir')
        ) {
            $isAcademic = true;
        } elseif (
            preg_match('/^(?:.*?)(bab\s*(?:\d+|[ivxlcdm]+))/i', $checkBody) &&
            (
                str_contains($checkBody, 'skripsi') ||
                str_contains($checkBody, 'tesis') ||
                str_contains($checkBody, 'thesis') ||
                str_contains($checkBody, 'disertasi') ||
                str_contains($checkBody, 'lembar pengesahan') ||
                str_contains($checkBody, 'pernyataan keaslian') ||
                str_contains($checkBody, 'nim.')
            )
        ) {
            $isAcademic = true;
        }

        // Split HTML by major block closing tags
        $tokens = preg_split('/(<\/(?:h[1-6]|p|div|blockquote|table|ul|ol|pre)>)/i', $sanitized, -1, PREG_SPLIT_DELIM_CAPTURE);
        $adaptedBlocks = [];
        $headings = [];

        $inCoverPage = false;
        if ($isAcademic) {
            $firstFewText = substr($checkBody, 0, 1500);
            if (
                str_contains($firstFewText, 'skripsi') ||
                str_contains($firstFewText, 'tesis') ||
                str_contains($firstFewText, 'thesis') ||
                str_contains($firstFewText, 'disusun') ||
                str_contains($firstFewText, 'oleh :') ||
                str_contains($firstFewText, 'oleh:') ||
                str_contains($firstFewText, 'nim')
            ) {
                $inCoverPage = true;
            }
        }
        $coverParagraphCount = 0;
        $consecutiveEmpty = 0;

        for ($i = 0; $i < count($tokens); $i += 2) {
            $block = trim($tokens[$i]);
            $closer = $tokens[$i + 1] ?? '';
            if (empty($block) && empty($closer)) {
                continue;
            }

            $fullTag = $block.$closer;

            // 1. Explicit Page Break: Preserve it immediately and continue
            if (str_contains($fullTag, 'page-break') || str_contains($fullTag, 'data-page-break') || trim(strip_tags($fullTag)) === 'Pemisah Halaman' || trim(strip_tags($fullTag)) === 'Pemisah Halaman (Akhir Sampul)') {
                $adaptedBlocks[] = '<div class="page-break my-8 border-b-2 border-dashed border-gray-400 dark:border-gray-600 text-center py-2 select-none" data-page-break="true"><span class="bg-gray-100 dark:bg-gray-800 px-3 py-1 rounded-full text-xs text-gray-500 font-mono">Pemisah Halaman</span></div>';

                continue;
            }

            $cleanText = trim(strip_tags(preg_replace('/<br\s*\/?>/i', ' ', $fullTag)));
            $cleanText = preg_replace('/\s+/', ' ', $cleanText);

            // Collapse excessive empty paragraphs
            if ($cleanText === '' && ! str_contains($fullTag, '<img')) {
                $consecutiveEmpty++;
                if ($consecutiveEmpty > 1) {
                    continue;
                }
            } else {
                $consecutiveEmpty = 0;
            }

            $isChapter = (bool) preg_match('/^(BAB\s*(?:\d+|[IVXLCDM]+))\b/i', $cleanText);
            $isPreliminary = (bool) preg_match('/^(ABSTRAK|ABSTRACT|KATA\s+PENGANTAR|PRAKATA|LEMBAR\s+PENGESAHAN|HALAMAN\s+PENGESAHAN|LEMBAR\s+PERSETUJUAN|HALAMAN\s+PERSETUJUAN|PERNYATAAN\s+KEASLIAN|PERNYATAAN\s+ORISINALITAS|DAFTAR\s+ISI|DAFTAR\s+TABEL|DAFTAR\s+GAMBAR|DAFTAR\s+LAMPIRAN|DAFTAR\s+PUSTAKA|RIWAYAT\s+HIDUP|BIODATA\s+PENULIS|LAMPIRAN)(\s+.*)?$/i', $cleanText);

            // End cover page when next section begins
            if ($inCoverPage && ($isChapter || $isPreliminary)) {
                $inCoverPage = false;
            }

            // Cover page processing for academic papers
            if ($inCoverPage) {
                $coverParagraphCount++;

                if (! empty($cleanText)) {
                    preg_match('/^<p\b[^>]*>(.*?)<\/p>$/is', $fullTag, $im);
                    $innerHtml = $im[1] ?? $cleanText;

                    if (preg_match('/^(OLEH|DISUSUN\s+OLEH|BY)\s*:/i', $cleanText)) {
                        $adaptedBlocks[] = "<p class=\"cover-author-label text-center text-sm font-medium text-gray-700 dark:text-gray-300 my-2 leading-relaxed\">{$innerHtml}</p>";
                    } elseif (preg_match('/^NIM\b/i', $cleanText)) {
                        $adaptedBlocks[] = "<p class=\"cover-author-nim text-center font-bold text-base text-gray-900 dark:text-gray-100 my-1 leading-relaxed\">{$innerHtml}</p>";
                    } elseif (preg_match('/^(PROGRAM\s+STUDI|JURUSAN|FAKULTAS|POLITEKNIK|UNIVERSITAS|DEPARTMENT|STUDY\s+PROGRAM)/i', $cleanText)) {
                        $adaptedBlocks[] = "<p class=\"cover-institution text-center font-bold text-sm uppercase text-gray-900 dark:text-gray-100 my-1 leading-relaxed\">{$innerHtml}</p>";
                    } elseif (preg_match('/^(Sebagai salah satu syarat|Submitted as a partial)/i', $cleanText)) {
                        $adaptedBlocks[] = "<p class=\"cover-subtitle text-center text-sm italic text-gray-700 dark:text-gray-300 my-3 max-w-xl mx-auto leading-relaxed\">{$innerHtml}</p>";
                    } elseif (preg_match('/^(SKRIPSI|THESIS|TESIS|DISERTASI|PROPOSAL|LAPORAN|TUGAS\s+AKHIR)$/i', $cleanText)) {
                        $adaptedBlocks[] = "<p class=\"cover-badge text-center font-bold text-xl uppercase tracking-widest text-gray-900 dark:text-gray-100 my-2\">{$innerHtml}</p>";
                    } else {
                        $adaptedBlocks[] = "<p class=\"cover-title text-center font-bold text-gray-900 dark:text-gray-100 leading-snug my-2\">{$innerHtml}</p>";
                    }
                } elseif (str_contains($fullTag, '<img')) {
                    $adaptedBlocks[] = preg_replace('/<img\b([^>]*)>/i', '<figure class="my-6 text-center"><img $1 class="cover-logo mx-auto max-h-40 object-contain" /></figure>', $fullTag);
                }

                continue;
            }

            // Outside Cover: Check for Table of Contents (Daftar Isi) dot-leader lines
            if ($this->isTableOfContentsLine($cleanText)) {
                if (preg_match('/^(.*?)(?:[\s.·…]{2,})([ivxlcdm\d]+)$/i', $cleanText, $tm)) {
                    $itemTitle = trim($tm[1]);
                    $itemPage = trim($tm[2]);
                    $adaptedBlocks[] = "<div class=\"toc-entry flex items-baseline justify-between py-0.5 text-sm leading-snug font-serif text-gray-800 dark:text-gray-200\"><span class=\"toc-title truncate max-w-[80%]\">{$itemTitle}</span><span class=\"flex-1 mx-2 border-b border-dotted border-gray-400 dark:border-gray-600\"></span><span class=\"toc-page font-mono text-xs text-gray-600 dark:text-gray-400\">{$itemPage}</span></div>";
                } else {
                    $cleanTitle = trim(preg_replace('/[\s.·…]+$/', '', $cleanText));
                    $adaptedBlocks[] = "<div class=\"toc-entry flex items-baseline justify-between py-0.5 text-sm leading-snug font-serif text-gray-800 dark:text-gray-200\"><span class=\"toc-title truncate max-w-[80%]\">{$cleanTitle}</span><span class=\"flex-1 mx-2 border-b border-dotted border-gray-400 dark:border-gray-600\"></span></div>";
                }

                continue;
            }

            // If it's already an H1, H2, H3, H4, H5, H6 tag, PRESERVE IT and record heading!
            if (preg_match('/^<h([1-6])\b([^>]*)>(.*?)<\/h\1>$/is', $fullTag, $m)) {
                if ($this->isCoverMetadataLine($cleanText)) {
                    $adaptedBlocks[] = "<p class=\"cover-institution text-center font-bold text-sm uppercase text-gray-900 dark:text-gray-100 my-1 leading-relaxed\">{$cleanText}</p>";

                    continue;
                }
                $lvl = (int) $m[1];
                $hId = 'heading-'.count($headings);
                $headings[] = ['id' => $hId, 'level' => $lvl, 'text' => $cleanText];
                $hClass = $lvl === 1 ? 'heading-chapter font-bold text-xl my-6 text-center uppercase' : ($lvl === 2 ? 'heading-section font-bold text-lg my-4' : 'heading-subsection font-semibold text-base my-3');
                $adaptedBlocks[] = "<h{$lvl} id=\"{$hId}\" class=\"{$hClass} text-gray-900 dark:text-gray-100\">{$m[3]}</h{$lvl}>";

                continue;
            }

            // If it's a list (ul or ol), PRESERVE IT!
            if (str_starts_with($fullTag, '<ul') || str_starts_with($fullTag, '<ol')) {
                $adaptedBlocks[] = $fullTag;

                continue;
            }

            // If it's a table, PRESERVE IT with borders!
            if (str_starts_with($fullTag, '<table')) {
                $adaptedBlocks[] = preg_replace(
                    '/<table\b([^>]*)>/i',
                    '<table $1 class="w-full border-collapse my-4 text-sm border border-gray-300 dark:border-gray-700">',
                    $fullTag
                );

                continue;
            }

            if ($cleanText === '' && ! str_contains($fullTag, '<img')) {
                continue;
            }

            // Chapter Headings: BAB I, BAB II, etc. (Handles single line or two-line chapter title)
            if (! $this->isTableOfContentsLine($cleanText) && strlen($cleanText) <= 80 && ! preg_match('/[.!?]$/', $cleanText) && preg_match('/^(BAB\s*(?:\d+|[IVXLCDM]+))\s*(.*)$/i', $cleanText, $bm)) {
                $chapterPrefix = trim($bm[1]);
                $chapterSuffix = trim($bm[2]);

                // If suffix is empty, look ahead at the next token to see if it's the chapter title (e.g. PENDAHULUAN)
                if (empty($chapterSuffix) && isset($tokens[$i + 2])) {
                    $nextCandidate = trim(strip_tags(preg_replace('/<br\s*\/?>/i', ' ', $tokens[$i + 2])));
                    if (! empty($nextCandidate) && strlen($nextCandidate) < 100 && ! str_starts_with($nextCandidate, '<') && ! preg_match('/^\d+\.\d+/', $nextCandidate)) {
                        $titleText = $chapterPrefix.' '.$nextCandidate;
                        $i += 2; // consume next token
                    } else {
                        $titleText = $chapterPrefix;
                    }
                } else {
                    $titleText = trim($chapterPrefix.' '.$chapterSuffix);
                }

                $titleText = preg_replace('/^(BAB\s*(?:\d+|[IVXLCDM]+))\s*([A-Za-z])/i', '$1 $2', $titleText);

                $hId = 'heading-'.count($headings);
                $headings[] = ['id' => $hId, 'level' => 1, 'text' => $titleText];

                $lastBlock = end($adaptedBlocks);
                if ($lastBlock === false || ! str_contains($lastBlock, 'data-page-break')) {
                    $adaptedBlocks[] = '<div class="page-break my-6 border-b border-dashed border-gray-300 dark:border-gray-700 text-center py-1 select-none" data-page-break="true"><span class="text-[10px] text-gray-400 font-mono">Awal Bab Baru</span></div>';
                }
                $adaptedBlocks[] = "<h1 id=\"{$hId}\" class=\"heading-chapter text-center font-bold text-xl my-6 text-gray-900 dark:text-gray-100 uppercase\">{$titleText}</h1>";

                continue;
            }

            // Academic Preliminaries: ABSTRAK, DAFTAR ISI, KATA PENGANTAR, LEMBAR PENGESAHAN, etc.
            if (
                ! $this->isTableOfContentsLine($cleanText) &&
                $isPreliminary &&
                strlen($cleanText) <= 60 &&
                ! preg_match('/[.!?]$/', $cleanText)
            ) {
                $titleText = strtoupper(trim($cleanText));
                $hId = 'heading-'.count($headings);
                $headings[] = ['id' => $hId, 'level' => 1, 'text' => $titleText];

                $lastBlock = end($adaptedBlocks);
                if ($lastBlock === false || ! str_contains($lastBlock, 'data-page-break')) {
                    $adaptedBlocks[] = '<div class="page-break my-6 border-b border-dashed border-gray-300 dark:border-gray-700 text-center py-1 select-none" data-page-break="true"><span class="text-[10px] text-gray-400 font-mono">Halaman Baru</span></div>';
                }
                $adaptedBlocks[] = "<h1 id=\"{$hId}\" class=\"heading-preliminary text-center font-bold text-xl my-6 text-gray-900 dark:text-gray-100 uppercase\">{$titleText}</h1>";

                continue;
            }

            // Subsections: 1.1 Latar Belakang, 2.1 Teori
            if (! $this->isTableOfContentsLine($cleanText) && preg_match('/^(\d+\.\d+)\s+(.+)$/', $cleanText)) {
                $titleText = trim($cleanText);
                $hId = 'heading-'.count($headings);
                $headings[] = ['id' => $hId, 'level' => 2, 'text' => $titleText];

                $adaptedBlocks[] = "<h2 id=\"{$hId}\" class=\"heading-section font-bold text-lg my-4 text-gray-900 dark:text-gray-100\">{$titleText}</h2>";

                continue;
            }

            // Sub-subsections: 1.1.1, 2.1.1
            if (! $this->isTableOfContentsLine($cleanText) && preg_match('/^(\d+\.\d+\.\d+)\s+(.+)$/', $cleanText)) {
                $titleText = trim($cleanText);
                $hId = 'heading-'.count($headings);
                $headings[] = ['id' => $hId, 'level' => 3, 'text' => $titleText];

                $adaptedBlocks[] = "<h3 id=\"{$hId}\" class=\"heading-subsection font-semibold text-base my-3 text-gray-800 dark:text-gray-200\">{$titleText}</h3>";

                continue;
            }

            // Standalone short uppercase lines
            if (
                ! $inCoverPage &&
                ! $this->isTableOfContentsLine($cleanText) &&
                ! $this->isCoverMetadataLine($cleanText) &&
                strlen($cleanText) > 5 &&
                strlen($cleanText) < 60 &&
                $cleanText === strtoupper($cleanText) &&
                preg_match('/[a-zA-Z]{3,}/', $cleanText) &&
                ! preg_match('/^[\d\s.,:%–=+\/-]+$/', $cleanText) &&
                ! preg_match('/[.,;!?]$/', $cleanText) &&
                ! str_contains($fullTag, '<img')
            ) {
                $hId = 'heading-'.count($headings);
                $headings[] = ['id' => $hId, 'level' => 2, 'text' => $cleanText];
                $adaptedBlocks[] = "<h2 id=\"{$hId}\" class=\"heading-section font-bold text-lg my-4 text-center text-gray-900 dark:text-gray-100 uppercase\">{$cleanText}</h2>";

                continue;
            }

            // Normal paragraphs
            if (! empty($cleanText)) {
                preg_match('/^<p\b([^>]*)>(.*?)<\/p>$/is', $fullTag, $pim);
                $existingAttrs = $pim[1] ?? '';
                $bodyHtml = $pim[2] ?? $cleanText;

                // Detect existing text alignment
                $hasCenter = str_contains($existingAttrs, 'text-center') || str_contains($existingAttrs, 'text-align: center');
                $hasRight = str_contains($existingAttrs, 'text-right') || str_contains($existingAttrs, 'text-align: right');
                $hasJustify = str_contains($existingAttrs, 'text-justify') || str_contains($existingAttrs, 'text-align: justify');
                $hasLeft = str_contains($existingAttrs, 'text-left') || str_contains($existingAttrs, 'text-align: left');

                // Extract style attribute if any
                $styleMatch = '';
                if (preg_match('/style=["\']([^"\']*)["\']/i', $existingAttrs, $sm)) {
                    $styleMatch = ' style="'.htmlspecialchars($sm[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'"';
                }

                if ($hasCenter) {
                    $adaptedBlocks[] = "<p class=\"text-center my-2 leading-relaxed text-gray-800 dark:text-gray-200\"{$styleMatch}>{$bodyHtml}</p>";
                } elseif ($hasRight) {
                    $adaptedBlocks[] = "<p class=\"text-right my-2 leading-relaxed text-gray-800 dark:text-gray-200\"{$styleMatch}>{$bodyHtml}</p>";
                } elseif ($hasJustify) {
                    $indentClass = ($isAcademic && strlen($cleanText) > 100) ? ' indent-8' : '';
                    $adaptedBlocks[] = "<p class=\"text-justify my-3 leading-relaxed text-gray-800 dark:text-gray-200{$indentClass}\"{$styleMatch}>{$bodyHtml}</p>";
                } elseif ($hasLeft) {
                    $adaptedBlocks[] = "<p class=\"text-left my-2 leading-relaxed text-gray-800 dark:text-gray-200\"{$styleMatch}>{$bodyHtml}</p>";
                } else {
                    if ($isAcademic && strlen($cleanText) > 100) {
                        $adaptedBlocks[] = "<p class=\"text-justify my-3 leading-relaxed text-gray-800 dark:text-gray-200 indent-8\"{$styleMatch}>{$bodyHtml}</p>";
                    } else {
                        $adaptedBlocks[] = "<p class=\"my-2 leading-relaxed text-gray-800 dark:text-gray-200\"{$styleMatch}>{$bodyHtml}</p>";
                    }
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
                    $contentHtml = '<p>'.htmlspecialchars($rawContent, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</p>';
                }
                break;

            case 'docx':
                $docxImporter = new DocxImporter;
                $docxMeta = $docxImporter->parseWithMetadata($file);
                $contentHtml = $docxMeta['html'];
                $extractedHeader = $docxMeta['header_text'];
                $extractedFooter = $docxMeta['footer_text'];
                $differentFirstPage = $docxMeta['different_first_page'] ?? false;
                break;

            case 'txt':
            default:
                $paragraphs = preg_split('/\n\s*\n/', trim($rawContent), -1, PREG_SPLIT_NO_EMPTY);
                if (! empty($paragraphs)) {
                    $contentHtml = implode("\n", array_map(function ($p) {
                        return '<p>'.nl2br(htmlspecialchars(trim($p), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')).'</p>';
                    }, $paragraphs));
                } else {
                    $contentHtml = '<p>'.nl2br(htmlspecialchars(trim($rawContent), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')).'</p>';
                }
                break;
        }

        // Apply intelligent document structure adaptation
        $adapted = $this->adaptDocumentStructure($contentHtml, $filename);
        $contentHtml = ! empty($adapted['adapted_html']) ? $adapted['adapted_html'] : $contentHtml;

        $title = $this->extractTitle($filename, $contentHtml);
        $stats = $this->calculateStats($contentHtml);

        return [
            'success' => true,
            'title' => $title,
            'filename' => $filename,
            'format' => $format,
            'content_html' => $contentHtml,
            'headings' => $adapted['headings'],
            'header_text' => $extractedHeader ?? '',
            'footer_text' => $extractedFooter ?? '',
            'different_first_page' => $differentFirstPage ?? false,
            'stats' => $stats,
            'adapted_settings' => $adapted['presets'],
            'message' => $adapted['presets']['is_academic']
                ? "Format Skripsi/Akademik terdeteksi! Margin 4-4-3-3 cm, Font Times New Roman 12pt, Spasi 1.5, dan {$adapted['presets']['headings_found']} Bab/Subbab otomatis diselaraskan."
                : "Format {$format} berhasil dideteksi dan disesuaikan.",
        ];
    }

    /**
     * High-fidelity OpenXML docx extractor preserving tables, lists, alignments, typography, and images.
     */
    private function extractDocxText(UploadedFile|string $file): string
    {
        return (new DocxImporter)->parse($file);
    }

    /**
     * Export document into requested format.
     */
    public function exportDocument(string $title, string $html, string $format, ?string $headerText = null, ?string $footerText = null): array
    {
        $safeTitle = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $title);
        if (empty($safeTitle)) {
            $safeTitle = 'dokumen';
        }

        $sanitizedHtml = $this->sanitizeHtml($html);

        switch (strtolower($format)) {
            case 'markdown':
            case 'md':
                $content = "# {$title}\n\n".$this->convertToMarkdown($sanitizedHtml);

                return [
                    'content' => $content,
                    'mime' => 'text/markdown; charset=UTF-8',
                    'filename' => "{$safeTitle}.md",
                ];

            case 'txt':
            case 'text':
                $content = "{$title}\n".str_repeat('=', mb_strlen($title))."\n\n".$this->convertToPlainText($sanitizedHtml);

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
                    'header_text' => $headerText ?? '',
                    'footer_text' => $footerText ?? '',
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
                $headerHtml = ! empty($headerText) ? htmlspecialchars($headerText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $footerHtml = ! empty($footerText) ? htmlspecialchars($footerText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : 'Dokumen VelScribe Wordcraft';

                $wordContent = <<<XML
<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
<head>
<meta charset='utf-8'>
<title>{$title}</title>
<!--[if gte mso 9]>
<xml>
 <w:WordDocument>
  <w:View>Print</w:View>
  <w:Zoom>100</w:Zoom>
 </w:WordDocument>
</xml>
<![endif]-->
<style>
@page Section1 {
    size: 210mm 297mm;
    margin: 40mm 30mm 30mm 40mm;
    mso-header-margin: 20mm;
    mso-footer-margin: 20mm;
    mso-header: h1;
    mso-footer: f1;
}
div.Section1 {
    page: Section1;
}
table.MsoHeaderTable, table.MsoFooterTable {
    width: 100%;
    border-collapse: collapse;
}
p.MsoHeader, div.MsoHeader {
    margin: 0;
    font-size: 10pt;
    font-family: 'Times New Roman', serif;
    color: #666666;
    border-bottom: 1px solid #d1d5db;
    padding-bottom: 4pt;
}
p.MsoFooter, div.MsoFooter {
    margin: 0;
    font-size: 10pt;
    font-family: 'Times New Roman', serif;
    color: #666666;
    border-top: 1px solid #d1d5db;
    padding-top: 4pt;
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
<div class="Section1">
    <div style="mso-element:header" id="h1">
        <p class="MsoHeader">
            <span style="float:left;">{$headerHtml}</span>
            <span style="float:right; font-style:italic;">VelScribe Wordcraft</span>
            <span style="clear:both; display:block;"></span>
        </p>
    </div>

    {$sanitizedHtml}

    <div style="mso-element:footer" id="f1">
        <p class="MsoFooter">
            <span style="float:left;">{$footerHtml}</span>
            <span style="float:right;">Halaman <span style="mso-field-code: PAGE "></span> dari <span style="mso-field-code: NUMPAGES "></span></span>
            <span style="clear:both; display:block;"></span>
        </p>
    </div>
</div>
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
