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

        // Allow only standard rich text tags and MathML for LaTeX equations
        $allowedTags = '<h1><h2><h3><h4><h5><h6><p><br><hr><b><strong><i><em><u><s><strike><del><sub><sup>'.
            '<ul><ol><li><blockquote><code><pre><table><thead><tbody><tfoot><tr><th><td>'.
            '<a><img><span><div><figure><figcaption><section><header><footer>'.
            '<math><semantics><mrow><mi><mn><mo><mtext><mspace><mfrac><msqrt><mroot><msub><msup><msubsup><munder><mover><munderover><mtable><mtr><mtd><annotation>';

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
                $html[] = "<blockquote class=\"border-l-4 border-indigo-500 pl-4 py-1 italic my-3 text-gray-700\">{$quoteText}</blockquote>";

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

        // Preserve LaTeX equations
        $text = preg_replace_callback('/<(?:div|span)\b[^>]*\bdata-latex=(["\'])(.*?)\1[^>]*>.*?<\/(?:div|span)>/is', function ($matches) {
            $rawLatex = html_entity_decode($matches[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $isDisplay = str_contains($matches[0], 'display-equation') || str_contains($matches[0], 'data-display="true"');

            return $isDisplay ? "\n\n$$".$rawLatex."$$\n\n" : '$'.$rawLatex.'$';
        }, $text);

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
        // Preserve LaTeX equations as clean raw syntax
        $html = preg_replace_callback('/<(?:div|span)\b[^>]*\bdata-latex=(["\'])(.*?)\1[^>]*>.*?<\/(?:div|span)>/is', function ($matches) {
            $rawLatex = html_entity_decode($matches[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $isDisplay = str_contains($matches[0], 'display-equation') || str_contains($matches[0], 'data-display="true"');

            return $isDisplay ? "\n\n$$".$rawLatex."$$\n\n" : '$'.$rawLatex.'$';
        }, $html);

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
        // 1. First priority: Check for explicit .cover-title or .doc-title in content
        if (preg_match('/<(?:p|h1|h2|div)\b[^>]*class=["\'][^"\']*\b(?:cover-title|doc-title)\b[^"\']*["\'][^>]*>(.*?)<\/(?:p|h1|h2|div)>/is', $html, $matches)) {
            $coverTitle = trim(strip_tags($matches[1]));
            if (! empty($coverTitle) && strlen($coverTitle) >= 3 && ! $this->isCoverMetadataLine($coverTitle)) {
                return $coverTitle;
            }
        }

        // 2. Second priority: Look for a genuine H1 tag that is not a preliminary section or chapter
        if (preg_match_all('/<h1[^>]*>(.*?)<\/h1>/is', $html, $allH1s)) {
            foreach ($allH1s[1] as $rawH1) {
                $heading = trim(strip_tags($rawH1));
                if (empty($heading)) {
                    continue;
                }
                if ($this->isTableOfContentsLine($heading) || $this->isCoverMetadataLine($heading)) {
                    continue;
                }
                if (preg_match('/^(ABSTRAK|ABSTRACT|KATA\s+PENGANTAR|PRAKATA|LEMBAR\s+PENGESAHAN|HALAMAN\s+PENGESAHAN|LEMBAR\s+PERSETUJUAN|HALAMAN\s+PERSETUJUAN|PERNYATAAN\s+KEASLIAN|PERNYATAAN\s+ORISINALITAS|DAFTAR\s+ISI|DAFTAR\s+TABEL|DAFTAR\s+GAMBAR|DAFTAR\s+LAMPIRAN|DAFTAR\s+PUSTAKA|RIWAYAT\s+HIDUP|BIODATA\s+PENULIS|LAMPIRAN)\b/i', $heading)) {
                    continue;
                }
                if (preg_match('/^(BAB\s*(?:\d+|[IVXLCDM]+))\b/i', $heading)) {
                    continue;
                }

                return $heading;
            }
        }

        // 3. Try to get from filename without extension
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
    /**
     * Check if a line contains metadata, signature blocks, employee/student numbers,
     * or committee roles that must never be converted to headings or table of contents entries.
     */
    private function isMetadataOrSignatureLine(string $text): bool
    {
        if (empty($text)) {
            return false;
        }

        $clean = trim($text);

        // Student & employee identification numbers
        if (preg_match('/\b(NIM|NPM|NIS|NIK|NIP|NIDN|NRK)\b/i', $clean)) {
            return true;
        }

        // Academic and professional degrees / honorifics
        if (preg_match('/\b(S\.T\.|M\.T\.|S\.Kom\.|M\.Kom\.|Ph\.D\.|Dr\.|Prof\.|Ir\.|M\.Sc\.|B\.Sc\.|M\.Eng\.|S\.Pd\.|M\.Pd\.|S\.Si\.|M\.Si\.|S\.E\.|M\.M\.)\b/i', $clean)) {
            return true;
        }

        // Approval, signature, and committee titles
        if (preg_match('/^(OLEH|DISUSUN\s+OLEH|BY|PEMBIMBING|PENGUJI|DEWAN\s+PENGUJI|TIM\s+PENGUJI|KETUA|SEKRETARIS|ANGGOTA|DEKAN|DIREKTUR|WAKIL\s+DEKAN|WAKIL\s+DIREKTUR|KOORDINATOR|MENGETAHUI|MENYETUJUI|DISAHKAN|DOSEN|JURUSAN|PROGRAM\s+STUDI|FAKULTAS|POLITEKNIK|UNIVERSITAS|KEMENTERIAN|TANGGAL\s+LULUS|TANGGAL\s+SEMINAR)\b/i', $clean)) {
            return true;
        }

        // Signature lines with dots or underscores
        if (preg_match('/^[._\-\s]{4,}$/', $clean)) {
            return true;
        }

        return false;
    }

    private function isTableOfContentsLine(string $text): bool
    {
        if (empty($text)) {
            return false;
        }
        $clean = preg_replace('/\s+/', ' ', trim($text));
        if (empty($clean) || strlen($clean) < 4 || strlen($clean) > 140) {
            return false;
        }
        if ($this->isMetadataOrSignatureLine($clean)) {
            return false;
        }
        if (preg_match('/^(NIM|NPM|NIS|NIK|OLEH|DISUSUN|BY|PROGRAM\s+STUDI|JURUSAN|FAKULTAS|POLITEKNIK|UNIVERSITAS|DEPARTMENT|STUDY\s+PROGRAM|SEKOLAH\s+TINGGI|INSTITUT|SEBAGAI\s+SALAH\s+SATU\s+SYARAT|SUBMITTED\s+AS)\b/i', $clean)) {
            return false;
        }
        // Must match: Title + (at least 3 dot-leaders, bullet-dots, or tabs) + page number at end
        if (preg_match('/^(.{2,100}?)(?:[\s.·…]{3,}|\t+)([ivxlcdm\d]{1,5})$/i', $clean)) {
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

        return $this->isMetadataOrSignatureLine($text) || (bool) preg_match('/^(OLEH|DISUSUN|BY|NIM|NPM|NIS|NIK|PROGRAM\s+STUDI|JURUSAN|FAKULTAS|POLITEKNIK|UNIVERSITAS|DEPARTMENT|STUDY\s+PROGRAM|SEKOLAH\s+TINGGI|INSTITUT|SEBAGAI\s+SALAH\s+SATU\s+SYARAT|SUBMITTED\s+AS|KEMENTERIAN|\d{4})\b/i', trim($text));
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
        $isDocx = str_ends_with($checkFilename, '.docx') || str_ends_with($checkFilename, '.doc');
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

        // Protect compound elements (table, ul, ol, pre, blockquote, and existing special divs like page-break and latex-equation)
        // so that preg_split does not break their inner paragraphs, rows, or closing tags.
        $protectedBlocks = [];
        $counter = 0;
        $protectRegex = '/<(table|ul|ol|pre|blockquote)\b[^>]*>.*?<\/\1>/is';
        $protectedHtml = preg_replace_callback($protectRegex, function ($m) use (&$protectedBlocks, &$counter) {
            $key = '<div data-protected-block="'.$counter.'"></div>';
            $protectedBlocks[$counter] = [
                'tag' => strtolower($m[1]),
                'html' => $m[0],
            ];
            $counter++;

            return $key;
        }, $sanitized);

        $protectedHtml = preg_replace_callback('/<div\b[^>]*\b(?:page-break|latex-equation|table-of-contents)\b[^>]*>.*?<\/div>/is', function ($m) use (&$protectedBlocks, &$counter) {
            $key = '<div data-protected-block="'.$counter.'"></div>';
            $protectedBlocks[$counter] = [
                'tag' => 'div',
                'html' => $m[0],
            ];
            $counter++;

            return $key;
        }, $protectedHtml);

        // Split HTML by major block closing tags
        $tokens = preg_split('/(<\/(?:h[1-6]|p|div|blockquote|table|ul|ol|pre)>)/i', $protectedHtml, -1, PREG_SPLIT_DELIM_CAPTURE);
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

            // Check for protected compound block placeholder (table, ul, ol, pre, blockquote, page-break, etc.)
            if (preg_match('/<div\b[^>]*data-protected-block="(\d+)"[^>]*><\/div>/i', $fullTag, $pm)) {
                $blockId = (int) $pm[1];
                if (isset($protectedBlocks[$blockId])) {
                    $item = $protectedBlocks[$blockId];
                    $bHtml = $item['html'];
                    if ($item['tag'] === 'table') {
                        $isBorderless = str_contains($bHtml, 'border-none') || str_contains($bHtml, 'border="0"');
                        if ($isBorderless) {
                            $bHtml = preg_replace('/<table\b([^>]*)>/i', '<table $1 class="w-full border-collapse my-4 text-sm border-none">', $bHtml);
                        } else {
                            $bHtml = preg_replace('/<table\b([^>]*)>/i', '<table $1 class="w-full border-collapse my-4 text-sm border border-gray-300 dark:border-gray-700">', $bHtml);
                        }
                    } elseif ($item['tag'] === 'div' && str_contains($bHtml, 'page-break')) {
                        $bHtml = '<div class="page-break my-8 border-b-2 border-dashed border-gray-400 dark:border-gray-600 text-center py-2 select-none" data-page-break="true"><span class="bg-gray-100 dark:bg-gray-800 px-3 py-1 rounded-full text-xs text-gray-500 font-mono">Pemisah Halaman</span></div>';
                    }
                    $adaptedBlocks[] = $bHtml;
                    unset($protectedBlocks[$blockId]);

                    continue;
                }
            }

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
                        $adaptedBlocks[] = "<p class=\"cover-author-label text-center text-sm font-medium text-gray-700 my-2 leading-relaxed\">{$innerHtml}</p>";
                    } elseif (preg_match('/^(?:NIM\b|\d{7,12}$)/i', $cleanText)) {
                        $adaptedBlocks[] = "<p class=\"cover-author-nim text-center font-bold text-base text-gray-900 my-1 leading-relaxed\">{$innerHtml}</p>";
                    } elseif (preg_match('/^(PROGRAM\s+STUDI|JURUSAN|FAKULTAS|POLITEKNIK|UNIVERSITAS|DEPARTMENT|STUDY\s+PROGRAM)/i', $cleanText)) {
                        $adaptedBlocks[] = "<p class=\"cover-institution text-center font-bold text-sm uppercase text-gray-900 my-1 leading-relaxed\">{$innerHtml}</p>";
                    } elseif (preg_match('/^(Sebagai salah satu syarat|Submitted as a partial)/i', $cleanText)) {
                        $adaptedBlocks[] = "<p class=\"cover-subtitle text-center text-sm italic text-gray-700 my-3 max-w-xl mx-auto leading-relaxed\">{$innerHtml}</p>";
                    } elseif (preg_match('/^(SKRIPSI|THESIS|TESIS|DISERTASI|PROPOSAL|LAPORAN|TUGAS\s+AKHIR)$/i', $cleanText)) {
                        $adaptedBlocks[] = "<p class=\"cover-badge text-center font-bold text-xl uppercase tracking-widest text-gray-900 my-2\">{$innerHtml}</p>";
                    } else {
                        $adaptedBlocks[] = "<p class=\"cover-title text-center font-bold text-gray-900 leading-snug my-2\">{$innerHtml}</p>";
                    }
                } elseif (str_contains($fullTag, '<img')) {
                    $adaptedBlocks[] = preg_replace('/<img\b([^>]*)>/i', '<figure class="my-6 text-center"><img $1 class="cover-logo mx-auto max-h-40 object-contain" /></figure>', $fullTag);
                }

                continue;
            }

            // Outside Cover: Check for Table of Contents (Daftar Isi) dot-leader lines
            if ($this->isTableOfContentsLine($cleanText)) {
                if (preg_match('/^(.*?)(?:[\s.·…]{2,}|\t+)([ivxlcdm\d]+)$/i', $cleanText, $tm)) {
                    $itemTitle = trim($tm[1]);
                    $itemPage = trim($tm[2]);
                    $adaptedBlocks[] = "<div class=\"toc-entry flex items-baseline justify-between py-0.5 text-sm leading-snug font-serif text-gray-800\"><span class=\"toc-title truncate max-w-[80%]\">{$itemTitle}</span><span class=\"flex-1 mx-2 border-b border-dotted border-gray-400\"></span><span class=\"toc-page font-mono text-xs text-gray-600\">{$itemPage}</span></div>";

                    continue;
                }
            }

            // If it's already an H1, H2, H3, H4, H5, H6 tag, PRESERVE IT and record heading!
            if (preg_match('/^<h([1-6])\b([^>]*)>(.*?)<\/h\1>$/is', $fullTag, $m)) {
                if ($this->isCoverMetadataLine($cleanText)) {
                    $adaptedBlocks[] = "<p class=\"cover-institution text-center font-bold text-sm uppercase text-gray-900 my-1 leading-relaxed\">{$cleanText}</p>";

                    continue;
                }
                $lvl = (int) $m[1];
                $hAttrs = $m[2];
                $hId = 'heading-'.count($headings);
                $headings[] = ['id' => $hId, 'level' => $lvl, 'text' => $cleanText];
                $hClass = $lvl === 1 ? 'heading-chapter font-bold text-xl my-6 text-center uppercase' : ($lvl === 2 ? 'heading-section font-bold text-lg my-4' : 'heading-subsection font-semibold text-base my-3');
                $hStyle = '';
                if (preg_match('/style=(["\'])(.*?)\1/is', $hAttrs, $sm)) {
                    $hStyle = ' style="'.htmlspecialchars($sm[2], ENT_COMPAT, 'UTF-8').'"';
                }
                $adaptedBlocks[] = "<h{$lvl} id=\"{$hId}\" class=\"{$hClass} text-gray-900\"{$hStyle}>{$m[3]}</h{$lvl}>";

                continue;
            }

            // If it's a list (ul or ol), PRESERVE IT!
            if (str_starts_with($fullTag, '<ul') || str_starts_with($fullTag, '<ol')) {
                $adaptedBlocks[] = $fullTag;

                continue;
            }

            // If it's a table, check if it's a borderless table (signature / approval sheets)
            if (str_starts_with($fullTag, '<table')) {
                if (str_contains($fullTag, 'border-none') || str_contains($fullTag, 'border="0"')) {
                    $adaptedBlocks[] = preg_replace(
                        '/<table\b([^>]*)>/i',
                        '<table $1 class="w-full border-collapse my-4 text-sm border-none">',
                        $fullTag
                    );
                } else {
                    $adaptedBlocks[] = preg_replace(
                        '/<table\b([^>]*)>/i',
                        '<table $1 class="w-full border-collapse my-4 text-sm border border-gray-300 dark:border-gray-700">',
                        $fullTag
                    );
                }

                continue;
            }

            if ($cleanText === '' && ! str_contains($fullTag, '<img')) {
                continue;
            }

            // Formula / Math: Display LaTeX $$...$$ or \[...\]
            if (preg_match('/^(?:\$\$|\\\[)(.*?)(?:\$\$|\\\])$/s', $cleanText, $lm)) {
                $latexExpr = trim($lm[1]);
                if (! empty($latexExpr)) {
                    $latexExpr = preg_replace('/(?<!\\\\)%/', '\\%', $latexExpr);
                    $escapedLatex = htmlspecialchars($latexExpr, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    $adaptedBlocks[] = '<div class="latex-equation display-equation my-4 text-center cursor-pointer select-none" contenteditable="false" data-latex="'.$escapedLatex.'" data-display="true"><div class="latex-math-wrapper inline-block px-3 py-1.5 rounded transition">$$'.$escapedLatex.'$$</div></div>';

                    continue;
                }
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
                $hStyle = '';
                if (preg_match('/style=(["\'])(.*?)\1/is', $fullTag, $sm)) {
                    $hStyle = ' style="'.htmlspecialchars($sm[2], ENT_COMPAT, 'UTF-8').'"';
                }
                $adaptedBlocks[] = "<h1 id=\"{$hId}\" class=\"heading-chapter text-center font-bold text-xl my-6 text-gray-900 uppercase\"{$hStyle}>{$titleText}</h1>";

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
                $hStyle = '';
                if (preg_match('/style=(["\'])(.*?)\1/is', $fullTag, $sm)) {
                    $hStyle = ' style="'.htmlspecialchars($sm[2], ENT_COMPAT, 'UTF-8').'"';
                }
                $adaptedBlocks[] = "<h1 id=\"{$hId}\" class=\"heading-preliminary text-center font-bold text-xl my-6 text-gray-900 uppercase\"{$hStyle}>{$titleText}</h1>";

                continue;
            }

            // Subsections: 1.1 Latar Belakang, 2.1 Teori
            if (! $this->isTableOfContentsLine($cleanText) && preg_match('/^(\d+\.\d+)\s+(.+)$/', $cleanText)) {
                $titleText = trim($cleanText);
                $hId = 'heading-'.count($headings);
                $headings[] = ['id' => $hId, 'level' => 2, 'text' => $titleText];
                $hStyle = '';
                if (preg_match('/style=(["\'])(.*?)\1/is', $fullTag, $sm)) {
                    $hStyle = ' style="'.htmlspecialchars($sm[2], ENT_COMPAT, 'UTF-8').'"';
                }

                $adaptedBlocks[] = "<h2 id=\"{$hId}\" class=\"heading-section font-bold text-lg my-4 text-gray-900\"{$hStyle}>{$titleText}</h2>";

                continue;
            }

            // Sub-subsections: 1.1.1, 2.1.1
            if (! $this->isTableOfContentsLine($cleanText) && preg_match('/^(\d+\.\d+\.\d+)\s+(.+)$/', $cleanText)) {
                $titleText = trim($cleanText);
                $hId = 'heading-'.count($headings);
                $headings[] = ['id' => $hId, 'level' => 3, 'text' => $titleText];
                $hStyle = '';
                if (preg_match('/style=(["\'])(.*?)\1/is', $fullTag, $sm)) {
                    $hStyle = ' style="'.htmlspecialchars($sm[2], ENT_COMPAT, 'UTF-8').'"';
                }

                $adaptedBlocks[] = "<h3 id=\"{$hId}\" class=\"heading-subsection font-semibold text-base my-3 text-gray-800\"{$hStyle}>{$titleText}</h3>";

                continue;
            }

            // Standalone short uppercase lines (only for non-DOCX formats; DOCX headings are already specified by styles)
            if (
                ! $isDocx &&
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
                $hStyle = '';
                if (preg_match('/style=(["\'])(.*?)\1/is', $fullTag, $sm)) {
                    $hStyle = ' style="'.htmlspecialchars($sm[2], ENT_COMPAT, 'UTF-8').'"';
                }
                $adaptedBlocks[] = "<h2 id=\"{$hId}\" class=\"heading-section font-bold text-lg my-4 text-center text-gray-900 uppercase\"{$hStyle}>{$cleanText}</h2>";

                continue;
            }

            // Normal paragraphs
            if (! empty($cleanText)) {
                preg_match('/^<p\b([^>]*)>(.*?)<\/p>$/is', $fullTag, $pim);
                $existingAttrs = $pim[1] ?? '';
                $bodyHtml = $pim[2] ?? $cleanText;

                // Detect inline/display LaTeX in paragraphs
                if (str_contains($bodyHtml, '$$')) {
                    $bodyHtml = preg_replace_callback('/\$\$(.*?)\$\$/s', function ($m) {
                        $rawLatex = preg_replace('/(?<!\\\\)%/', '\\%', trim($m[1]));
                        $el = htmlspecialchars($rawLatex, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                        return '<div class="latex-equation display-equation my-3 text-center cursor-pointer select-none" contenteditable="false" data-latex="'.$el.'" data-display="true"><div class="latex-math-wrapper inline-block px-3 py-1.5 rounded transition">$$'.$el.'$$</div></div>';
                    }, $bodyHtml);
                }

                // Detect existing text alignment
                $hasCenter = str_contains($existingAttrs, 'text-center') || str_contains($existingAttrs, 'text-align: center');
                $hasRight = str_contains($existingAttrs, 'text-right') || str_contains($existingAttrs, 'text-align: right');
                $hasJustify = str_contains($existingAttrs, 'text-justify') || str_contains($existingAttrs, 'text-align: justify');
                $hasLeft = str_contains($existingAttrs, 'text-left') || str_contains($existingAttrs, 'text-align: left');

                $extraClasses = [];
                if (str_contains($existingAttrs, 'indent-first-line')) {
                    $extraClasses[] = 'indent-first-line';
                }
                if (str_contains($existingAttrs, 'indent-hanging')) {
                    $extraClasses[] = 'indent-hanging';
                }
                if (str_contains($existingAttrs, 'keep-with-next')) {
                    $extraClasses[] = 'keep-with-next';
                }
                if (str_contains($existingAttrs, 'keep-lines-together')) {
                    $extraClasses[] = 'keep-lines-together';
                }
                if (str_contains($existingAttrs, 'page-break-before')) {
                    $extraClasses[] = 'page-break-before';
                }
                $extraClassStr = ! empty($extraClasses) ? ' '.implode(' ', $extraClasses) : '';

                // Extract style attribute if any
                $styleMatch = '';
                if (preg_match('/style=(["\'])(.*?)\1/is', $existingAttrs, $sm)) {
                    $styleMatch = ' style="'.htmlspecialchars($sm[2], ENT_COMPAT, 'UTF-8').'"';
                }

                $isMonospace = str_contains($existingAttrs, 'font-mono') ||
                               str_contains($bodyHtml, 'Courier') ||
                               str_contains($bodyHtml, 'monospace') ||
                               str_contains($bodyHtml, 'Consolas') ||
                               str_contains($bodyHtml, '<code');

                if ($hasCenter) {
                    $adaptedBlocks[] = "<p class=\"text-center my-2 leading-relaxed text-gray-800{$extraClassStr}\"{$styleMatch}>{$bodyHtml}</p>";
                } elseif ($hasRight) {
                    $adaptedBlocks[] = "<p class=\"text-right my-2 leading-relaxed text-gray-800{$extraClassStr}\"{$styleMatch}>{$bodyHtml}</p>";
                } elseif ($hasJustify) {
                    $indentClass = ($isAcademic && strlen($cleanText) > 100 && empty($extraClasses) && ! $isMonospace) ? ' indent-8' : '';
                    $adaptedBlocks[] = "<p class=\"text-justify my-3 leading-relaxed text-gray-800{$indentClass}{$extraClassStr}\"{$styleMatch}>{$bodyHtml}</p>";
                } elseif ($hasLeft) {
                    $adaptedBlocks[] = "<p class=\"text-left my-2 leading-relaxed text-gray-800{$extraClassStr}\"{$styleMatch}>{$bodyHtml}</p>";
                } else {
                    if ($isAcademic && strlen($cleanText) > 100 && empty($extraClasses) && ! $isMonospace) {
                        $adaptedBlocks[] = "<p class=\"text-justify my-3 leading-relaxed text-gray-800 indent-8{$extraClassStr}\"{$styleMatch}>{$bodyHtml}</p>";
                    } else {
                        $adaptedBlocks[] = "<p class=\"my-2 leading-relaxed text-gray-800{$extraClassStr}\"{$styleMatch}>{$bodyHtml}</p>";
                    }
                }
            }
        }

        $adaptedHtml = implode("\n", $adaptedBlocks);

        // Restore any remaining protected blocks
        foreach ($protectedBlocks as $blockId => $item) {
            $placeholder = '<div data-protected-block="'.$blockId.'"></div>';
            $bHtml = $item['html'];
            if ($item['tag'] === 'table') {
                $isBorderless = str_contains($bHtml, 'border-none') || str_contains($bHtml, 'border="0"');
                if ($isBorderless) {
                    $bHtml = preg_replace('/<table\b([^>]*)>/i', '<table $1 class="w-full border-collapse my-4 text-sm border-none">', $bHtml);
                } else {
                    $bHtml = preg_replace('/<table\b([^>]*)>/i', '<table $1 class="w-full border-collapse my-4 text-sm border border-gray-300 dark:border-gray-700">', $bHtml);
                }
            }
            $adaptedHtml = str_replace($placeholder, $bHtml, $adaptedHtml);
        }

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
                $defaultFontFamily = $docxMeta['default_font_family'] ?? null;
                $pageSetup = $docxMeta['page_setup'] ?? null;
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
            'default_font_family' => $defaultFontFamily ?? null,
            'page_setup' => $pageSetup ?? null,
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
    public function exportDocument(string $title, string $html, string $format, ?string $headerText = null, ?string $footerText = null, array $layoutOptions = []): array
    {
        $safeTitle = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $title);
        if (empty($safeTitle)) {
            $safeTitle = 'dokumen';
        }

        $sanitizedHtml = $this->sanitizeHtml($html);

        $paperSize = strtolower($layoutOptions['paper_size'] ?? 'a4');
        $orientation = strtolower($layoutOptions['orientation'] ?? 'portrait');

        $dimensions = [
            'a4' => ['w' => '210mm', 'h' => '297mm'],
            'letter' => ['w' => '215.9mm', 'h' => '279.4mm'],
            'legal' => ['w' => '215.9mm', 'h' => '355.6mm'],
            'folio' => ['w' => '215mm', 'h' => '330mm'],
            'executive' => ['w' => '184.1mm', 'h' => '266.7mm'],
            'a3' => ['w' => '297mm', 'h' => '420mm'],
            'a5' => ['w' => '148mm', 'h' => '210mm'],
            'b5' => ['w' => '182mm', 'h' => '257mm'],
            'statement' => ['w' => '139.7mm', 'h' => '215.9mm'],
            'tabloid' => ['w' => '279.4mm', 'h' => '431.8mm'],
        ];

        $w = $dimensions[$paperSize]['w'] ?? (! empty($layoutOptions['width_mm']) ? "{$layoutOptions['width_mm']}mm" : '210mm');
        $h = $dimensions[$paperSize]['h'] ?? (! empty($layoutOptions['height_mm']) ? "{$layoutOptions['height_mm']}mm" : '297mm');

        if ($orientation === 'landscape') {
            $pageSizeStr = "{$h} {$w}";
            $msoOrientation = 'landscape';
        } else {
            $pageSizeStr = "{$w} {$h}";
            $msoOrientation = 'portrait';
        }

        $marginPreset = strtolower($layoutOptions['margin_preset'] ?? '');
        $marginsOpt = $layoutOptions['margins'] ?? null;
        if (! empty($marginsOpt) && is_array($marginsOpt)) {
            $top = isset($marginsOpt['top']) ? (is_numeric($marginsOpt['top']) ? "{$marginsOpt['top']}mm" : $marginsOpt['top']) : '40mm';
            $right = isset($marginsOpt['right']) ? (is_numeric($marginsOpt['right']) ? "{$marginsOpt['right']}mm" : $marginsOpt['right']) : '30mm';
            $bottom = isset($marginsOpt['bottom']) ? (is_numeric($marginsOpt['bottom']) ? "{$marginsOpt['bottom']}mm" : $marginsOpt['bottom']) : '30mm';
            $left = isset($marginsOpt['left']) ? (is_numeric($marginsOpt['left']) ? "{$marginsOpt['left']}mm" : $marginsOpt['left']) : '40mm';
            $marginStr = "{$top} {$right} {$bottom} {$left}";
        } elseif ($marginPreset === 'normal') {
            $marginStr = '25.4mm 25.4mm 25.4mm 25.4mm';
        } elseif ($marginPreset === 'narrow') {
            $marginStr = '12.7mm 12.7mm 12.7mm 12.7mm';
        } elseif ($marginPreset === 'moderate') {
            $marginStr = '25.4mm 19.1mm 25.4mm 19.1mm';
        } elseif ($marginPreset === 'wide') {
            $marginStr = '25.4mm 50.8mm 25.4mm 50.8mm';
        } elseif ($marginPreset === 'office2003') {
            $marginStr = '25.4mm 31.8mm 25.4mm 31.8mm';
        } else {
            $marginStr = '40mm 30mm 30mm 40mm';
        }

        $lineSpacing = $layoutOptions['line_spacing'] ?? '1.5';
        $spaceBefore = isset($layoutOptions['paragraph_spacing']['before']) ? (is_numeric($layoutOptions['paragraph_spacing']['before']) ? "{$layoutOptions['paragraph_spacing']['before']}pt" : $layoutOptions['paragraph_spacing']['before']) : '0pt';
        $spaceAfter = isset($layoutOptions['paragraph_spacing']['after']) ? (is_numeric($layoutOptions['paragraph_spacing']['after']) ? "{$layoutOptions['paragraph_spacing']['after']}pt" : $layoutOptions['paragraph_spacing']['after']) : '6pt';
        $indentLeft = isset($layoutOptions['paragraph_indent']['left']) ? (is_numeric($layoutOptions['paragraph_indent']['left']) ? "{$layoutOptions['paragraph_indent']['left']}cm" : $layoutOptions['paragraph_indent']['left']) : '0cm';
        $indentRight = isset($layoutOptions['paragraph_indent']['right']) ? (is_numeric($layoutOptions['paragraph_indent']['right']) ? "{$layoutOptions['paragraph_indent']['right']}cm" : $layoutOptions['paragraph_indent']['right']) : '0cm';

        $columns = $layoutOptions['columns'] ?? 1;
        $columnCountCss = '';
        if ($columns == 2 || $columns === '2') {
            $columnCountCss = 'column-count: 2; column-gap: 1.25cm; mso-column-count: 2;';
        } elseif ($columns == 3 || $columns === '3') {
            $columnCountCss = 'column-count: 3; column-gap: 1cm; mso-column-count: 3;';
        }

        $hyphenation = strtolower($layoutOptions['hyphenation'] ?? 'none');
        $hyphensCss = ($hyphenation === 'auto') ? 'hyphens: auto; -webkit-hyphens: auto; mso-hyphenate: auto;' : '';

        $pageColor = $layoutOptions['page_color'] ?? '#ffffff';
        $pageBgCss = ($pageColor && strtolower($pageColor) !== '#ffffff') ? "background-color: {$pageColor};" : '';

        $pageBorder = $layoutOptions['page_border'] ?? null;
        $borderCss = '';
        if (! empty($pageBorder) && is_array($pageBorder) && ! empty($pageBorder['style']) && $pageBorder['style'] !== 'none') {
            $borderStyle = $pageBorder['style'] ?? 'solid';
            $borderWidth = $pageBorder['width'] ?? '1pt';
            $borderColor = $pageBorder['color'] ?? '#000000';
            $borderMargin = $pageBorder['margin'] ?? '15mm';
            $borderCss = "border: {$borderWidth} {$borderStyle} {$borderColor}; padding: {$borderMargin};";
        }

        $watermark = $layoutOptions['watermark'] ?? null;
        $watermarkHtml = '';
        if (! empty($watermark) && is_array($watermark) && ! empty($watermark['text'])) {
            $wmText = htmlspecialchars($watermark['text'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $wmFont = htmlspecialchars($watermark['font'] ?? 'Calibri', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $wmColor = htmlspecialchars($watermark['color'] ?? '#94a3b8', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $wmOpacity = is_numeric($watermark['opacity'] ?? null) ? $watermark['opacity'] : '0.25';
            $wmOrientation = ($watermark['orientation'] ?? 'diagonal') === 'horizontal' ? '0deg' : '-45deg';
            $wmSize = (! empty($watermark['size']) && is_numeric($watermark['size'])) ? "{$watermark['size']}pt" : '72pt';

            $watermarkHtml = "<div class=\"watermark-overlay\" style=\"position: fixed; top: 40%; left: 10%; right: 10%; text-align: center; transform: rotate({$wmOrientation}); font-family: '{$wmFont}', sans-serif; font-size: {$wmSize}; color: {$wmColor}; opacity: {$wmOpacity}; font-weight: bold; pointer-events: none; z-index: -1; user-select: none; text-transform: uppercase;\">{$wmText}</div>";
        }

        $lineNumbers = strtolower($layoutOptions['line_numbers'] ?? 'none');
        $lineNumbersCss = '';
        if ($lineNumbers !== 'none' && ! empty($lineNumbers)) {
            $lineNumbersCss = "body, div.Section1 { counter-reset: line-counter; }\np:not(.suppress-line-numbers)::before { counter-increment: line-counter; content: counter(line-counter); display: inline-block; width: 2.5em; margin-left: -3em; color: #9ca3af; font-size: 9pt; text-align: right; user-select: none; padding-right: 0.5em; }";
        }

        $multiplePages = strtolower($layoutOptions['multiple_pages'] ?? 'normal');
        $mirrorMarginsCss = ($multiplePages === 'mirror' || $multiplePages === 'bookfold' || $marginPreset === 'mirrored') ? 'mso-mirror-margins: yes;' : '';
        if ($multiplePages === '2pages' && empty($columnCountCss)) {
            $columnCountCss = 'column-count: 2; column-gap: 1.25cm; mso-column-count: 2;';
        }

        $vertAlign = strtolower($layoutOptions['vertical_align'] ?? 'top');
        $vertAlignCss = '';
        if ($vertAlign === 'center') {
            $vertAlignCss = 'min-height: 70vh; display: flex; flex-direction: column; justify-content: center;';
        } elseif ($vertAlign === 'bottom') {
            $vertAlignCss = 'min-height: 70vh; display: flex; flex-direction: column; justify-content: flex-end;';
        } elseif ($vertAlign === 'justify') {
            $vertAlignCss = 'min-height: 70vh; display: flex; flex-direction: column; justify-content: space-between;';
        }

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
                    'layout' => $layoutOptions,
                    'layout_options' => $layoutOptions,
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
                $hasCustomHeader = ! empty($headerText) && trim($headerText) !== '';
                $hasCustomFooter = ! empty($footerText) && trim($footerText) !== '';

                $headerHtml = $hasCustomHeader ? htmlspecialchars(trim($headerText), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : '';
                $footerHtml = $hasCustomFooter ? htmlspecialchars(trim($footerText), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : '';

                $msoHeaderCss = $hasCustomHeader ? "mso-header-margin: 20mm;\n    mso-header: h1;" : '';
                $msoFooterCss = $hasCustomFooter ? "mso-footer-margin: 20mm;\n    mso-footer: f1;" : '';

                $msoHeaderBlock = $hasCustomHeader ? <<<HTML
    <div style="mso-element:header" id="h1">
        <p class="MsoHeader">
            <span style="float:left;">{$headerHtml}</span>
            <span style="clear:both; display:block;"></span>
        </p>
    </div>
HTML : '';

                $msoFooterBlock = $hasCustomFooter ? <<<HTML
    <div style="mso-element:footer" id="f1">
        <p class="MsoFooter">
            <span>{$footerHtml}</span>
        </p>
    </div>
HTML : '';

                $docxBody = preg_replace(
                    '/<div[^>]*class=["\'][^"\']*column-break[^"\']*["\'][^>]*>.*?<\/div>/is',
                    '<br clear="all" style="page-break-before:always;mso-break-type:column" />',
                    $sanitizedHtml
                );
                $docxBody = preg_replace(
                    '/<div[^>]*class=["\'][^"\']*section-break[^"\']*["\'][^>]*>.*?<\/div>/is',
                    '<br clear="all" style="page-break-before:always;mso-break-type:section-break" />',
                    $docxBody
                );

                $msoHeaderStyle = $hasCustomHeader ? <<<'CSS'
table.MsoHeaderTable {
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
CSS : '';

                $msoFooterStyle = $hasCustomFooter ? <<<'CSS'
table.MsoFooterTable {
    width: 100%;
    border-collapse: collapse;
}
p.MsoFooter, div.MsoFooter {
    margin: 0;
    font-size: 10pt;
    font-family: 'Times New Roman', serif;
    color: #666666;
    border-top: 1px solid #d1d5db;
    padding-top: 4pt;
}
CSS : '';

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
    size: {$pageSizeStr};
    margin: {$marginStr};
    mso-page-orientation: {$msoOrientation};
    {$mirrorMarginsCss}
    {$msoHeaderCss}
    {$msoFooterCss}
}
div.Section1 {
    page: Section1;
    {$columnCountCss}
    {$hyphensCss}
    {$borderCss}
    {$vertAlignCss}
}
{$lineNumbersCss}
{$msoHeaderStyle}
{$msoFooterStyle}
body {
    font-family: 'Times New Roman', 'Calibri', serif;
    font-size: 12pt;
    line-height: {$lineSpacing};
    color: #000000;
    {$pageBgCss}
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
    margin-top: {$spaceBefore};
    margin-bottom: {$spaceAfter};
    line-height: {$lineSpacing};
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
    {$watermarkHtml}
    {$msoHeaderBlock}

    {$docxBody}

    {$msoFooterBlock}
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
        @page { size: {$pageSizeStr}; margin: {$marginStr}; }
        body { font-family: 'Times New Roman', Times, serif; font-size: 12pt; line-height: {$lineSpacing}; max-width: {$w}; margin: 40px auto; padding: {$marginStr}; color: #111827; {$pageBgCss} {$borderCss} {$columnCountCss} {$hyphensCss} {$vertAlignCss} }
        {$lineNumbersCss}
        h1 { font-size: 16pt; font-weight: bold; text-align: center; margin-top: 24px; margin-bottom: 16px; text-transform: uppercase; }
        h2 { font-size: 13pt; font-weight: bold; margin-top: 20px; margin-bottom: 10px; }
        h3 { font-size: 12pt; font-weight: bold; margin-top: 16px; margin-bottom: 8px; }
        p { text-align: justify; margin-top: {$spaceBefore}; margin-bottom: {$spaceAfter}; line-height: {$lineSpacing}; }
        p.text-center { text-align: center; }
        table { border-collapse: collapse; width: 100%; margin: 16px 0; }
        th, td { border: 1px solid #374151; padding: 8px 12px; }
        th { background: #f3f4f6; font-weight: bold; text-align: center; }
        img { max-width: 100%; height: auto; display: block; margin: 16px auto; }
    </style>
</head>
<body>
    {$watermarkHtml}
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
