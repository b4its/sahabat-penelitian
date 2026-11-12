<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Illuminate\Support\Str;

class DocumentModelService
{
    public const SCHEMA_VERSION = 1;

    public function __construct(
        protected ?DocumentService $documentService = null,
    ) {
        $this->documentService = $documentService ?? new DocumentService;
    }

    /**
     * Generate a new empty canonical document model.
     */
    public function createEmptyDocument(string $title = 'Untitled Document', string $lang = 'id-ID'): array
    {
        $docId = (string) Str::uuid();

        return [
            'schemaVersion' => self::SCHEMA_VERSION,
            'id' => $docId,
            'title' => $title,
            'metadata' => [
                'creator' => 'VelScribe Wordcraft Studio',
                'language' => $lang,
                'createdAt' => now()->toIso8601String(),
                'updatedAt' => now()->toIso8601String(),
                'version' => 1,
            ],
            'pageSetup' => [
                'paperSize' => 'a4',
                'orientation' => 'portrait',
                'margins' => [
                    'top' => 25.4,
                    'bottom' => 25.4,
                    'left' => 25.4,
                    'right' => 25.4,
                ],
                'columns' => 1,
            ],
            'styles' => $this->getDefaultStyles(),
            'blocks' => [
                [
                    'id' => 'blk-'.Str::random(12),
                    'type' => 'paragraph',
                    'style' => 'Normal',
                    'align' => 'left',
                    'runs' => [
                        [
                            'text' => '',
                            'bold' => false,
                            'italic' => false,
                            'underline' => false,
                            'strike' => false,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Convert HTML into canonical Document model v1.
     */
    public function htmlToModel(string $html, string $title = 'Dokumen'): array
    {
        $sanitizedHtml = $this->documentService->sanitizeHtml($html);
        $docId = (string) Str::uuid();

        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);

        $wrapped = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>'.$sanitizedHtml.'</body></html>';
        $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $body = $dom->getElementsByTagName('body')->item(0);

        $blocks = [];
        if ($body) {
            foreach ($body->childNodes as $child) {
                $block = $this->parseNodeToBlock($child);
                if ($block !== null) {
                    $blocks[] = $block;
                }
            }
        }

        if (empty($blocks)) {
            $blocks[] = [
                'id' => 'blk-'.Str::random(12),
                'type' => 'paragraph',
                'style' => 'Normal',
                'align' => 'left',
                'runs' => [
                    ['text' => ''],
                ],
            ];
        }

        return [
            'schemaVersion' => self::SCHEMA_VERSION,
            'id' => $docId,
            'title' => $title,
            'metadata' => [
                'creator' => 'VelScribe Wordcraft Studio',
                'language' => 'id-ID',
                'createdAt' => now()->toIso8601String(),
                'updatedAt' => now()->toIso8601String(),
                'version' => 1,
                'wordCount' => $this->documentService->calculateStats($sanitizedHtml)['word_count'] ?? 0,
            ],
            'pageSetup' => [
                'paperSize' => 'a4',
                'orientation' => 'portrait',
                'margins' => [
                    'top' => 25.4,
                    'bottom' => 25.4,
                    'left' => 25.4,
                    'right' => 25.4,
                ],
                'columns' => 1,
            ],
            'styles' => $this->getDefaultStyles(),
            'blocks' => $blocks,
        ];
    }

    /**
     * Convert canonical Document model v1 into HTML.
     */
    public function modelToHtml(array $model): string
    {
        $html = '';
        $blocks = $model['blocks'] ?? [];

        foreach ($blocks as $block) {
            $html .= $this->renderBlockToHtml($block);
        }

        return trim($html);
    }

    /**
     * Migrate legacy HTML into canonical model and return full report with rollback.
     */
    public function migrateLegacyHtml(string $legacyHtml, string $title = 'Dokumen Migrasi'): array
    {
        $originalHash = sha1($legacyHtml);
        $statsOriginal = $this->documentService->calculateStats($legacyHtml);

        $model = $this->htmlToModel($legacyHtml, $title);
        $migratedHtml = $this->modelToHtml($model);
        $statsMigrated = $this->documentService->calculateStats($migratedHtml);

        $validation = $this->validateModel($model);

        return [
            'success' => $validation['valid'],
            'schemaVersion' => self::SCHEMA_VERSION,
            'documentId' => $model['id'],
            'model' => $model,
            'migratedHtml' => $migratedHtml,
            'validation' => $validation,
            'stats' => [
                'originalWords' => $statsOriginal['word_count'],
                'migratedWords' => $statsMigrated['word_count'],
                'preservedRatio' => $statsOriginal['word_count'] > 0
                    ? round(($statsMigrated['word_count'] / $statsOriginal['word_count']) * 100, 2)
                    : 100.0,
            ],
            'rollback' => [
                'originalHash' => $originalHash,
                'originalHtml' => $legacyHtml,
                'migratedAt' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * Validate a document model against schema v1 rules.
     */
    public function validateModel(array $model): array
    {
        $errors = [];

        if (! isset($model['schemaVersion']) || $model['schemaVersion'] !== self::SCHEMA_VERSION) {
            $errors[] = 'Skema model tidak kompatibel atau versi skema hilang.';
        }

        if (empty($model['id'])) {
            $errors[] = 'ID dokumen wajib ada dan tidak boleh kosong.';
        }

        if (! isset($model['blocks']) || ! is_array($model['blocks'])) {
            $errors[] = 'Model dokumen harus memiliki array blok konten.';
        } else {
            $seenIds = [];
            foreach ($model['blocks'] as $index => $block) {
                if (empty($block['id'])) {
                    $errors[] = "Blok pada indeks {$index} tidak memiliki ID stabil.";
                } elseif (isset($seenIds[$block['id']])) {
                    $errors[] = "ID blok ganda terdeteksi: {$block['id']}.";
                } else {
                    $seenIds[$block['id']] = true;
                }

                if (empty($block['type'])) {
                    $errors[] = "Blok pada indeks {$index} tidak memiliki tipe.";
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Parse a single DOMNode into a canonical block.
     */
    protected function parseNodeToBlock(DOMNode $node): ?array
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            $text = trim($node->textContent);
            if ($text === '') {
                return null;
            }

            return [
                'id' => 'blk-'.Str::random(12),
                'type' => 'paragraph',
                'style' => 'Normal',
                'align' => 'left',
                'runs' => [
                    ['text' => $text],
                ],
            ];
        }

        if (! ($node instanceof DOMElement)) {
            return null;
        }

        $tag = strtolower($node->tagName);
        $blockId = $node->getAttribute('id') ?: ($node->getAttribute('data-node-id') ?: 'blk-'.Str::random(12));

        if (preg_match('/^h([1-6])$/', $tag, $matches)) {
            $level = (int) $matches[1];

            return [
                'id' => $blockId,
                'type' => 'heading',
                'level' => $level,
                'style' => 'Heading '.$level,
                'align' => $this->detectAlignment($node),
                'runs' => $this->parseInlineRuns($node),
            ];
        }

        if ($tag === 'p') {
            return [
                'id' => $blockId,
                'type' => 'paragraph',
                'style' => $node->getAttribute('class') ?: 'Normal',
                'align' => $this->detectAlignment($node),
                'runs' => $this->parseInlineRuns($node),
            ];
        }

        if ($tag === 'blockquote') {
            return [
                'id' => $blockId,
                'type' => 'quote',
                'style' => 'Quote',
                'runs' => $this->parseInlineRuns($node),
            ];
        }

        if ($tag === 'pre') {
            return [
                'id' => $blockId,
                'type' => 'code_block',
                'style' => 'Code',
                'runs' => [
                    ['text' => $node->textContent],
                ],
            ];
        }

        if ($tag === 'hr') {
            return [
                'id' => $blockId,
                'type' => 'break',
                'breakType' => 'horizontal_rule',
            ];
        }

        if ($tag === 'ul' || $tag === 'ol') {
            $items = [];
            foreach ($node->childNodes as $child) {
                if ($child instanceof DOMElement && strtolower($child->tagName) === 'li') {
                    $itemId = $child->getAttribute('id') ?: 'item-'.Str::random(10);
                    $items[] = [
                        'id' => $itemId,
                        'runs' => $this->parseInlineRuns($child),
                    ];
                }
            }

            return [
                'id' => $blockId,
                'type' => 'list',
                'listType' => $tag === 'ol' ? 'numbered' : 'bullet',
                'items' => $items,
            ];
        }

        if ($tag === 'table') {
            return $this->parseTableElement($node, $blockId);
        }

        if ($tag === 'figure' || $tag === 'img') {
            return $this->parseFigureElement($node, $blockId);
        }

        // Generic wrapper/div
        if ($tag === 'div' || $tag === 'section') {
            $className = $node->getAttribute('class');
            if (str_contains($className, 'page-break')) {
                return [
                    'id' => $blockId,
                    'type' => 'break',
                    'breakType' => 'page',
                ];
            }
            if (str_contains($className, 'column-break')) {
                return [
                    'id' => $blockId,
                    'type' => 'break',
                    'breakType' => 'column',
                ];
            }
            if (str_contains($className, 'section-break')) {
                return [
                    'id' => $blockId,
                    'type' => 'break',
                    'breakType' => 'section',
                ];
            }

            // Treat as paragraph with inner runs
            $runs = $this->parseInlineRuns($node);
            if (! empty($runs)) {
                return [
                    'id' => $blockId,
                    'type' => 'paragraph',
                    'style' => 'Normal',
                    'align' => $this->detectAlignment($node),
                    'runs' => $runs,
                ];
            }
        }

        return null;
    }

    /**
     * Parse inline runs inside an element (text, bold, italic, comments, links).
     */
    protected function parseInlineRuns(DOMNode $node): array
    {
        $runs = [];

        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $text = $child->textContent;
                if ($text !== '') {
                    $runs[] = ['text' => $text];
                }
            } elseif ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);

                if ($tag === 'br') {
                    $runs[] = ['text' => "\n", 'break' => true];

                    continue;
                }

                $childRuns = $this->parseInlineRuns($child);
                if (empty($childRuns)) {
                    $text = $child->textContent;
                    if ($text !== '') {
                        $childRuns = [['text' => $text]];
                    }
                }

                foreach ($childRuns as $r) {
                    if ($tag === 'strong' || $tag === 'b') {
                        $r['bold'] = true;
                    }
                    if ($tag === 'em' || $tag === 'i') {
                        $r['italic'] = true;
                    }
                    if ($tag === 'u') {
                        $r['underline'] = true;
                    }
                    if ($tag === 's' || $tag === 'strike' || $tag === 'del') {
                        $r['strike'] = true;
                    }
                    if ($tag === 'code') {
                        $r['code'] = true;
                    }
                    if ($tag === 'sub') {
                        $r['subscript'] = true;
                    }
                    if ($tag === 'sup') {
                        $r['superscript'] = true;
                    }
                    if ($tag === 'a') {
                        $r['link'] = $child->getAttribute('href');
                    }

                    // Comments and revisions metadata
                    if ($child->hasAttribute('data-comment-id')) {
                        $r['commentId'] = $child->getAttribute('data-comment-id');
                    }
                    if ($child->hasAttribute('data-revision-id')) {
                        $r['revisionId'] = $child->getAttribute('data-revision-id');
                        $r['revisionType'] = $child->getAttribute('data-revision-type');
                    }

                    $runs[] = $r;
                }
            }
        }

        return $runs;
    }

    /**
     * Parse HTML table into canonical table block.
     */
    protected function parseTableElement(DOMElement $node, string $blockId): array
    {
        $rows = [];
        $trList = $node->getElementsByTagName('tr');

        foreach ($trList as $tr) {
            $cells = [];
            foreach ($tr->childNodes as $tc) {
                if ($tc instanceof DOMElement && in_array(strtolower($tc->tagName), ['td', 'th'])) {
                    $isHeader = strtolower($tc->tagName) === 'th';
                    $colSpan = (int) ($tc->getAttribute('colspan') ?: 1);
                    $rowSpan = (int) ($tc->getAttribute('rowspan') ?: 1);

                    $cells[] = [
                        'id' => 'cell-'.Str::random(10),
                        'isHeader' => $isHeader,
                        'colSpan' => max(1, $colSpan),
                        'rowSpan' => max(1, $rowSpan),
                        'runs' => $this->parseInlineRuns($tc),
                    ];
                }
            }
            $rows[] = [
                'id' => 'row-'.Str::random(10),
                'cells' => $cells,
            ];
        }

        return [
            'id' => $blockId,
            'type' => 'table',
            'style' => 'TableGrid',
            'rows' => $rows,
        ];
    }

    /**
     * Parse figure or img element into canonical figure block.
     */
    protected function parseFigureElement(DOMElement $node, string $blockId): array
    {
        $img = null;
        $caption = '';

        if (strtolower($node->tagName) === 'img') {
            $img = $node;
        } else {
            $imgList = $node->getElementsByTagName('img');
            if ($imgList->length > 0) {
                $img = $imgList->item(0);
            }
            $capList = $node->getElementsByTagName('figcaption');
            if ($capList->length > 0) {
                $caption = trim($capList->item(0)->textContent);
            }
        }

        $src = $img ? $img->getAttribute('src') : '';
        $alt = $img ? $img->getAttribute('alt') : '';
        $width = $img ? $img->getAttribute('width') : null;
        $height = $img ? $img->getAttribute('height') : null;

        return [
            'id' => $blockId,
            'type' => 'figure',
            'src' => $src,
            'alt' => $alt,
            'caption' => $caption,
            'width' => $width,
            'height' => $height,
            'alignment' => 'center',
            'wrapMode' => 'inline',
        ];
    }

    /**
     * Render single block to HTML.
     */
    protected function renderBlockToHtml(array $block): string
    {
        $type = $block['type'] ?? 'paragraph';
        $blockId = htmlspecialchars($block['id'] ?? 'blk-'.Str::random(8), ENT_QUOTES, 'UTF-8');

        switch ($type) {
            case 'heading':
                $level = max(1, min(6, (int) ($block['level'] ?? 1)));
                $align = $block['align'] ?? 'left';
                $styleAttr = $align !== 'left' ? " style=\"text-align: {$align};\"" : '';
                $inner = $this->renderRunsToHtml($block['runs'] ?? []);

                return "<h{$level} id=\"{$blockId}\"{$styleAttr}>{$inner}</h{$level}>\n";

            case 'paragraph':
                $align = $block['align'] ?? 'left';
                $styleClass = htmlspecialchars($block['style'] ?? '', ENT_QUOTES, 'UTF-8');
                $classAttr = $styleClass && $styleClass !== 'Normal' ? " class=\"{$styleClass}\"" : '';
                $styleAttr = $align !== 'left' ? " style=\"text-align: {$align};\"" : '';
                $inner = $this->renderRunsToHtml($block['runs'] ?? []);

                return "<p id=\"{$blockId}\"{$classAttr}{$styleAttr}>{$inner}</p>\n";

            case 'quote':
                $inner = $this->renderRunsToHtml($block['runs'] ?? []);

                return "<blockquote id=\"{$blockId}\"><p>{$inner}</p></blockquote>\n";

            case 'code_block':
                $inner = htmlspecialchars($block['runs'][0]['text'] ?? '', ENT_QUOTES, 'UTF-8');

                return "<pre id=\"{$blockId}\"><code>{$inner}</code></pre>\n";

            case 'break':
                $breakType = $block['breakType'] ?? 'page';
                if ($breakType === 'horizontal_rule') {
                    return "<hr id=\"{$blockId}\" />\n";
                }

                return "<div id=\"{$blockId}\" class=\"{$breakType}-break\"></div>\n";

            case 'list':
                $tag = ($block['listType'] ?? 'bullet') === 'numbered' ? 'ol' : 'ul';
                $itemsHtml = '';
                foreach ($block['items'] ?? [] as $item) {
                    $itemId = htmlspecialchars($item['id'] ?? 'item-'.Str::random(8), ENT_QUOTES, 'UTF-8');
                    $inner = $this->renderRunsToHtml($item['runs'] ?? []);
                    $itemsHtml .= "  <li id=\"{$itemId}\">{$inner}</li>\n";
                }

                return "<{$tag} id=\"{$blockId}\">\n{$itemsHtml}</{$tag}>\n";

            case 'table':
                $rowsHtml = '';
                foreach ($block['rows'] ?? [] as $row) {
                    $rowId = htmlspecialchars($row['id'] ?? 'row-'.Str::random(8), ENT_QUOTES, 'UTF-8');
                    $cellsHtml = '';
                    foreach ($row['cells'] ?? [] as $cell) {
                        $cellId = htmlspecialchars($cell['id'] ?? 'cell-'.Str::random(8), ENT_QUOTES, 'UTF-8');
                        $tag = ! empty($cell['isHeader']) ? 'th' : 'td';
                        $colSpan = (int) ($cell['colSpan'] ?? 1);
                        $rowSpan = (int) ($cell['rowSpan'] ?? 1);
                        $spanAttrs = '';
                        if ($colSpan > 1) {
                            $spanAttrs .= " colspan=\"{$colSpan}\"";
                        }
                        if ($rowSpan > 1) {
                            $spanAttrs .= " rowspan=\"{$rowSpan}\"";
                        }
                        $inner = $this->renderRunsToHtml($cell['runs'] ?? []);
                        $cellsHtml .= "    <{$tag} id=\"{$cellId}\"{$spanAttrs}>{$inner}</{$tag}>\n";
                    }
                    $rowsHtml .= "  <tr id=\"{$rowId}\">\n{$cellsHtml}  </tr>\n";
                }

                return "<table id=\"{$blockId}\" class=\"w-full\">\n{$rowsHtml}</table>\n";

            case 'figure':
                $src = htmlspecialchars($block['src'] ?? '', ENT_QUOTES, 'UTF-8');
                $alt = htmlspecialchars($block['alt'] ?? '', ENT_QUOTES, 'UTF-8');
                $caption = htmlspecialchars($block['caption'] ?? '', ENT_QUOTES, 'UTF-8');
                $imgTag = "<img src=\"{$src}\" alt=\"{$alt}\" />";
                if ($caption !== '') {
                    return "<figure id=\"{$blockId}\">{$imgTag}<figcaption>{$caption}</figcaption></figure>\n";
                }

                return "<figure id=\"{$blockId}\">{$imgTag}</figure>\n";

            default:
                return "<div id=\"{$blockId}\"></div>\n";
        }
    }

    /**
     * Render inline runs to safe HTML.
     */
    protected function renderRunsToHtml(array $runs): string
    {
        $out = '';
        foreach ($runs as $run) {
            $text = htmlspecialchars($run['text'] ?? '', ENT_QUOTES, 'UTF-8');

            if (! empty($run['bold'])) {
                $text = "<strong>{$text}</strong>";
            }
            if (! empty($run['italic'])) {
                $text = "<em>{$text}</em>";
            }
            if (! empty($run['underline'])) {
                $text = "<u>{$text}</u>";
            }
            if (! empty($run['strike'])) {
                $text = "<del>{$text}</del>";
            }
            if (! empty($run['code'])) {
                $text = "<code>{$text}</code>";
            }
            if (! empty($run['subscript'])) {
                $text = "<sub>{$text}</sub>";
            }
            if (! empty($run['superscript'])) {
                $text = "<sup>{$text}</sup>";
            }
            if (! empty($run['link'])) {
                $url = htmlspecialchars($run['link'], ENT_QUOTES, 'UTF-8');
                $text = "<a href=\"{$url}\">{$text}</a>";
            }
            if (! empty($run['commentId'])) {
                $cid = htmlspecialchars($run['commentId'], ENT_QUOTES, 'UTF-8');
                $text = "<span class=\"docx-comment-anchor\" data-comment-id=\"{$cid}\">{$text}</span>";
            }
            if (! empty($run['revisionId'])) {
                $rid = htmlspecialchars($run['revisionId'], ENT_QUOTES, 'UTF-8');
                $rtype = htmlspecialchars($run['revisionType'] ?? 'insert', ENT_QUOTES, 'UTF-8');
                $text = "<span class=\"docx-revision\" data-revision-id=\"{$rid}\" data-revision-type=\"{$rtype}\">{$text}</span>";
            }

            $out .= $text;
        }

        return $out;
    }

    protected function detectAlignment(DOMElement $element): string
    {
        $style = $element->getAttribute('style');
        if (preg_match('/text-align\s*:\s*(center|right|justify|left)/i', $style, $m)) {
            return strtolower($m[1]);
        }
        $class = $element->getAttribute('class');
        if (str_contains($class, 'text-center')) {
            return 'center';
        }
        if (str_contains($class, 'text-right')) {
            return 'right';
        }
        if (str_contains($class, 'text-justify')) {
            return 'justify';
        }

        return 'left';
    }

    protected function getDefaultStyles(): array
    {
        return [
            ['id' => 'Normal', 'name' => 'Normal', 'type' => 'paragraph', 'fontSize' => 12, 'fontFamily' => 'Calibri'],
            ['id' => 'Heading1', 'name' => 'Heading 1', 'type' => 'paragraph', 'fontSize' => 18, 'bold' => true],
            ['id' => 'Heading2', 'name' => 'Heading 2', 'type' => 'paragraph', 'fontSize' => 14, 'bold' => true],
            ['id' => 'Heading3', 'name' => 'Heading 3', 'type' => 'paragraph', 'fontSize' => 12, 'bold' => true],
            ['id' => 'Title', 'name' => 'Title', 'type' => 'paragraph', 'fontSize' => 24, 'bold' => true],
            ['id' => 'Quote', 'name' => 'Quote', 'type' => 'paragraph', 'italic' => true],
        ];
    }
}
