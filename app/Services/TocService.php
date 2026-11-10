<?php

namespace App\Services;

use Illuminate\Support\Str;

class TocService
{
    /**
     * Supported design format styles.
     */
    public const STYLES = ['template', 'classic', 'modern', 'formal', 'simple'];

    /**
     * Supported tab leader characters.
     */
    public const LEADERS = ['dots', 'dashes', 'line', 'none'];

    /**
     * Extract headings with hierarchical levels and estimated/detected pages from HTML.
     */
    public function extractHeadingsFromHtml(string $html, int $maxLevel = 3): array
    {
        $cleanHtml = trim($html);
        if (empty($cleanHtml)) {
            return [];
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $encoded = mb_encode_numericentity($cleanHtml, [0x80, 0x10FFFF, 0, 0x1FFFFF], 'UTF-8');
        $dom->loadHTML('<?xml encoding="UTF-8"><div>'.$encoded.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query('//h1 | //h2 | //h3 | //h4 | //h5 | //h6 | //p[contains(@class, "heading-chapter")] | //p[contains(@class, "font-bold")]');

        $headings = [];
        $pageTracker = 1;

        if ($nodes) {
            foreach ($nodes as $node) {
                // Calculate page if enclosed in page container
                $pageIndex = $this->resolveNodePage($node, $pageTracker);

                $text = trim($node->textContent);
                if (empty($text)) {
                    continue;
                }

                // Never include the TOC title itself in the TOC
                if (preg_match('/^(DAFTAR\s+ISI|TABLE\s+OF\s+CONTENTS|KONTEN|CONTENTS)\b/i', $text)) {
                    continue;
                }

                // Determine heading level
                $tag = strtolower($node->nodeName);
                $level = 1;
                if ($tag === 'h1' || preg_match('/^(BAB\s+[I|V|X|0-9]+|LEMBAR\s+PENGESAHAN|ABSTRAK|ABSTRACT|KATA\s+PENGANTAR|DAFTAR\s+TABEL|DAFTAR\s+GAMBAR|DAFTAR\s+PUSTAKA)\b/i', $text)) {
                    $level = 1;
                } elseif ($tag === 'h2' || preg_match('/^\d+\.\d+\s+[A-Z]/', $text)) {
                    $level = 2;
                } elseif ($tag === 'h3' || preg_match('/^\d+\.\d+\.\d+\s+[A-Z]/', $text)) {
                    $level = 3;
                } elseif ($tag === 'h4') {
                    $level = 4;
                } elseif ($tag === 'h5' || $tag === 'h6') {
                    $level = 5;
                }

                if ($level > $maxLevel) {
                    continue;
                }

                $id = '';
                if ($node instanceof \DOMElement && $node->hasAttribute('id')) {
                    $id = $node->getAttribute('id');
                }
                if (empty($id)) {
                    $id = 'heading-'.Str::slug(substr($text, 0, 30)).'-'.(count($headings) + 1);
                }

                $headings[] = [
                    'id' => $id,
                    'text' => $text,
                    'level' => $level,
                    'page' => $pageIndex,
                ];
            }
        }

        return $headings;
    }

    /**
     * Resolve the page number where a node is located.
     */
    protected function resolveNodePage(\DOMNode $node, int &$currentTracker): int
    {
        $curr = $node->parentNode;
        while ($curr) {
            if ($curr instanceof \DOMElement) {
                if ($curr->hasAttribute('data-page-index')) {
                    $val = (int) $curr->getAttribute('data-page-index');
                    if ($val > 0) {
                        return $val;
                    }
                }
            }
            $curr = $curr->parentNode;
        }

        return $currentTracker;
    }

    /**
     * Generate complete Microsoft Word-style Table of Contents HTML block.
     */
    public function generateTocHtml(array $headings, array $options = []): string
    {
        $headingTitle = strtoupper(trim($options['heading'] ?? 'DAFTAR ISI'));
        $style = strtolower(trim($options['style'] ?? 'template'));
        if (! in_array($style, self::STYLES)) {
            $style = 'template';
        }

        $leader = strtolower(trim($options['leader'] ?? 'dots'));
        if (! in_array($leader, self::LEADERS)) {
            $leader = 'dots';
        }

        $maxLevel = (int) ($options['levels'] ?? 3);
        $showPageNumbers = (bool) ($options['showPageNumbers'] ?? true);
        $rightAlignPages = (bool) ($options['rightAlignPageNumbers'] ?? true);
        $templateType = $options['templateType'] ?? 'auto2';

        if ($templateType === 'manual' && empty($headings)) {
            $headings = [
                ['id' => 'heading-bab-1', 'text' => 'BAB I PENDAHULUAN', 'level' => 1, 'page' => 1],
                ['id' => 'heading-sub-1-1', 'text' => '1.1 Latar Belakang Masalah', 'level' => 2, 'page' => 1],
                ['id' => 'heading-sub-1-2', 'text' => '1.2 Rumusan Masalah', 'level' => 2, 'page' => 3],
                ['id' => 'heading-bab-2', 'text' => 'BAB II TINJAUAN PUSTAKA', 'level' => 1, 'page' => 5],
                ['id' => 'heading-sub-2-1', 'text' => '2.1 Landasan Teori', 'level' => 2, 'page' => 5],
                ['id' => 'heading-sub-2-1-1', 'text' => '2.1.1 Arsitektur Sistem dan Mikrokontroler', 'level' => 3, 'page' => 7],
                ['id' => 'heading-bab-3', 'text' => 'BAB III METODE PENELITIAN', 'level' => 1, 'page' => 12],
                ['id' => 'heading-daftarpustaka', 'text' => 'DAFTAR PUSTAKA', 'level' => 1, 'page' => 25],
            ];
        }

        // Filter by max level
        $filtered = array_values(array_filter($headings, fn ($h) => ($h['level'] ?? 1) <= $maxLevel));

        // Generate Leader CSS style
        $leaderClass = match ($leader) {
            'dashes' => 'border-b border-dashed border-gray-400 dark:border-gray-500',
            'line' => 'border-b border-solid border-gray-400 dark:border-gray-500',
            'none' => 'border-none',
            default => 'border-b border-dotted border-gray-400 dark:border-gray-500',
        };

        $entriesHtml = '';
        foreach ($filtered as $item) {
            $lvl = (int) ($item['level'] ?? 1);
            $id = htmlspecialchars($item['id'] ?? '', ENT_QUOTES, 'UTF-8');
            $text = htmlspecialchars($item['text'] ?? '', ENT_QUOTES, 'UTF-8');
            $page = htmlspecialchars((string) ($item['page'] ?? 1), ENT_QUOTES, 'UTF-8');

            // Indentation & Typography per level and style
            $levelIndent = match ($lvl) {
                1 => 'pl-0 font-bold text-gray-900 dark:text-gray-100',
                2 => 'pl-6 font-medium text-gray-800 dark:text-gray-200 text-xs sm:text-sm',
                3 => 'pl-12 text-gray-700 dark:text-gray-300 text-xs',
                4 => 'pl-16 text-gray-600 dark:text-gray-400 text-xs',
                default => 'pl-20 text-gray-500 text-xs',
            };

            if ($style === 'classic') {
                $levelIndent .= ($lvl === 1 ? ' uppercase tracking-wide font-serif' : ' italic font-serif');
            } elseif ($style === 'modern') {
                $levelIndent .= ' font-sans';
            } elseif ($style === 'formal') {
                $levelIndent .= ' font-serif';
            }

            $pageHtml = '';
            if ($showPageNumbers) {
                $alignClass = $rightAlignPages ? 'font-mono text-xs font-semibold shrink-0 text-right min-w-[20px]' : 'font-mono text-xs font-semibold ml-2';
                $pageHtml = "<span class=\"toc-page {$alignClass} text-gray-800 dark:text-gray-200\">{$page}</span>";
            }

            $leaderHtml = '';
            if ($showPageNumbers && $rightAlignPages && $leader !== 'none') {
                $leaderHtml = "<span class=\"toc-leader flex-1 mx-2 {$leaderClass}\"></span>";
            } elseif ($showPageNumbers && ! $rightAlignPages) {
                $leaderHtml = '&nbsp;';
            }

            $entriesHtml .= <<<HTML
        <div class="toc-entry toc-item flex items-baseline justify-between py-1 {$levelIndent}" data-level="{$lvl}" data-target="{$id}">
            <a href="#{$id}" class="toc-link hover:text-indigo-600 dark:hover:text-indigo-400 hover:underline truncate max-w-[80%] select-none">{$text}</a>
            {$leaderHtml}
            {$pageHtml}
        </div>\n
HTML;
        }

        if (empty($entriesHtml)) {
            $entriesHtml = '        <p class="text-xs text-gray-400 italic text-center py-6">Belum ada judul bab atau subbab terdeteksi. Tambahkan Heading (Bab / Subbab) di naskah lalu klik "Perbarui Tabel".</p>'."\n";
        }

        $containerStyleClass = match ($style) {
            'modern' => 'bg-gray-50/70 dark:bg-gray-800/50 rounded-2xl border border-indigo-100 dark:border-indigo-900/40 p-6',
            'formal' => 'bg-white dark:bg-gray-900 border-2 border-double border-gray-300 dark:border-gray-700 p-8',
            'simple' => 'bg-transparent py-4 border-b border-gray-200 dark:border-gray-800',
            default => 'w-full my-6 relative group select-none',
        };

        return <<<HTML
<div class="table-of-contents {$containerStyleClass}" data-toc-block="true" data-toc-style="{$style}" data-toc-leader="{$leader}" data-toc-levels="{$maxLevel}" data-toc-shownum="{$showPageNumbers}" data-toc-rightalign="{$rightAlignPages}" contenteditable="false">
    <!-- Microsoft Word TOC Field Action Bar -->
    <div class="toc-toolbar absolute -top-4 right-0 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-2.5 py-1 rounded-md shadow-md flex items-center gap-2 text-[11px] opacity-0 group-hover:opacity-100 transition duration-150 z-20 no-print">
        <button type="button" class="btn-toc-update hover:text-indigo-400 dark:hover:text-indigo-600 font-semibold flex items-center gap-1 cursor-pointer" title="Perbarui Daftar Isi (Update Table)">
            <span>🔄 Perbarui Tabel...</span>
        </button>
        <span class="w-px h-3 bg-gray-700 dark:bg-gray-300"></span>
        <button type="button" class="btn-toc-customize hover:text-indigo-400 dark:hover:text-indigo-600 flex items-center gap-1 cursor-pointer" title="Kustomisasi Format Daftar Isi">
            <span>⚙️ Format</span>
        </button>
        <span class="w-px h-3 bg-gray-700 dark:bg-gray-300"></span>
        <button type="button" class="btn-toc-delete hover:text-red-400 text-red-300 dark:text-red-600 cursor-pointer" title="Hapus Daftar Isi dari Dokumen">
            <span>🗑️ Hapus</span>
        </button>
    </div>

    <!-- Title / Heading of TOC (Centered Academic Typography) -->
    <h1 class="toc-header heading-chapter text-center font-bold text-xl mb-6 uppercase tracking-wider text-gray-900 dark:text-white font-serif">{$headingTitle}</h1>

    <!-- Entries List -->
    <div class="toc-entries space-y-1 font-serif">
{$entriesHtml}    </div>
</div>
HTML;
    }

    /**
     * Update existing Table of Contents HTML block.
     * $updateType can be 'page_numbers' or 'entire'.
     */
    public function updateTocHtml(string $existingHtml, array $newHeadings, string $updateType = 'entire', array $options = []): string
    {
        // Extract options from existing HTML attributes if not provided
        if (preg_match('/data-toc-style="([^"]+)"/', $existingHtml, $m)) {
            $options['style'] = $options['style'] ?? $m[1];
        }
        if (preg_match('/data-toc-leader="([^"]+)"/', $existingHtml, $m)) {
            $options['leader'] = $options['leader'] ?? $m[1];
        }
        if (preg_match('/data-toc-levels="([^"]+)"/', $existingHtml, $m)) {
            $options['levels'] = $options['levels'] ?? (int) $m[1];
        }
        if (preg_match('/<h2[^>]*class="[^"]*toc-header[^"]*"[^>]*>(.*?)<\/h2>/is', $existingHtml, $m)) {
            $options['heading'] = $options['heading'] ?? trim(strip_tags($m[1]));
        }

        if ($updateType === 'page_numbers') {
            // Map heading id or text to new page
            $pageMap = [];
            foreach ($newHeadings as $h) {
                $id = $h['id'] ?? '';
                $txt = strtolower(trim($h['text'] ?? ''));
                if ($id) {
                    $pageMap[$id] = $h['page'] ?? 1;
                }
                if ($txt) {
                    $pageMap[$txt] = $h['page'] ?? 1;
                }
            }

            $dom = new \DOMDocument('1.0', 'UTF-8');
            libxml_use_internal_errors(true);
            $encoded = mb_encode_numericentity($existingHtml, [0x80, 0x10FFFF, 0, 0x1FFFFF], 'UTF-8');
            $dom->loadHTML('<?xml encoding="UTF-8"><div id="toc-root-wrapper">'.$encoded.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            libxml_clear_errors();

            $xpath = new \DOMXPath($dom);
            $items = $xpath->query('//div[contains(@class, "toc-item")]');
            if ($items) {
                foreach ($items as $item) {
                    if ($item instanceof \DOMElement) {
                        $targetId = $item->getAttribute('data-target');
                        $linkNode = $xpath->query('.//a[contains(@class, "toc-link")]', $item)->item(0);
                        $linkText = $linkNode ? strtolower(trim($linkNode->textContent)) : '';

                        $newPage = $pageMap[$targetId] ?? ($pageMap[$linkText] ?? null);
                        if ($newPage !== null) {
                            $pageNode = $xpath->query('.//*[contains(@class, "toc-page")]', $item)->item(0);
                            if ($pageNode) {
                                $pageNode->textContent = (string) $newPage;
                            }
                        }
                    }
                }
            }

            $root = $dom->getElementById('toc-root-wrapper');
            $out = '';
            if ($root) {
                foreach ($root->childNodes as $child) {
                    $out .= $dom->saveHTML($child);
                }
            }

            return trim($out) ?: $existingHtml;
        }

        // Entire table update: regenerate completely
        return $this->generateTocHtml($newHeadings, $options);
    }
}
