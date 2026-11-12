<?php

namespace App\Services;

use Illuminate\Support\Str;

class DocumentTransactionService
{
    /**
     * Invert a transaction operation to produce its exact undo operation.
     */
    public function invertOperation(array $op): array
    {
        $type = $op['type'] ?? '';
        $opId = 'inv-'.Str::random(10);
        $timestamp = now()->toIso8601String();

        switch ($type) {
            case 'insert_block':
                return [
                    'opId' => $opId,
                    'type' => 'delete_block',
                    'targetId' => $op['payload']['id'] ?? $op['targetId'] ?? '',
                    'index' => $op['index'] ?? 0,
                    'payload' => $op['payload'] ?? [],
                    'timestamp' => $timestamp,
                ];

            case 'delete_block':
                return [
                    'opId' => $opId,
                    'type' => 'insert_block',
                    'targetId' => $op['targetId'] ?? '',
                    'index' => $op['index'] ?? 0,
                    'payload' => $op['payload'] ?? [],
                    'timestamp' => $timestamp,
                ];

            case 'update_block':
                return [
                    'opId' => $opId,
                    'type' => 'update_block',
                    'targetId' => $op['targetId'] ?? '',
                    'payload' => $op['previous'] ?? [],
                    'previous' => $op['payload'] ?? [],
                    'timestamp' => $timestamp,
                ];

            case 'split_block':
                return [
                    'opId' => $opId,
                    'type' => 'merge_blocks',
                    'firstId' => $op['firstId'] ?? '',
                    'secondId' => $op['secondId'] ?? '',
                    'originalId' => $op['originalId'] ?? '',
                    'originalBlock' => $op['originalBlock'] ?? [],
                    'timestamp' => $timestamp,
                ];

            case 'merge_blocks':
                return [
                    'opId' => $opId,
                    'type' => 'split_block',
                    'targetId' => $op['originalId'] ?? '',
                    'firstBlock' => $op['firstBlock'] ?? [],
                    'secondBlock' => $op['secondBlock'] ?? [],
                    'timestamp' => $timestamp,
                ];

            case 'format_range':
                return [
                    'opId' => $opId,
                    'type' => 'format_range',
                    'targetId' => $op['targetId'] ?? '',
                    'range' => $op['range'] ?? [],
                    'format' => $op['previousFormat'] ?? [],
                    'previousFormat' => $op['format'] ?? [],
                    'timestamp' => $timestamp,
                ];

            default:
                return [
                    'opId' => $opId,
                    'type' => 'noop',
                    'timestamp' => $timestamp,
                ];
        }
    }

    /**
     * Apply an operation to a canonical document model.
     */
    public function applyOperation(array $model, array $op): array
    {
        $blocks = $model['blocks'] ?? [];
        $type = $op['type'] ?? '';

        switch ($type) {
            case 'insert_block':
                $index = isset($op['index']) ? (int) $op['index'] : count($blocks);
                $index = max(0, min(count($blocks), $index));
                array_splice($blocks, $index, 0, [$op['payload']]);
                break;

            case 'delete_block':
                $targetId = $op['targetId'] ?? '';
                $blocks = array_values(array_filter($blocks, fn ($b) => ($b['id'] ?? '') !== $targetId));
                break;

            case 'update_block':
                $targetId = $op['targetId'] ?? '';
                foreach ($blocks as &$b) {
                    if (($b['id'] ?? '') === $targetId) {
                        $b = array_merge($b, $op['payload'] ?? []);
                        break;
                    }
                }
                break;

            case 'merge_blocks':
                $firstId = $op['firstId'] ?? '';
                $secondId = $op['secondId'] ?? '';
                $firstIndex = null;
                $secondIndex = null;

                foreach ($blocks as $idx => $b) {
                    if (($b['id'] ?? '') === $firstId) {
                        $firstIndex = $idx;
                    }
                    if (($b['id'] ?? '') === $secondId) {
                        $secondIndex = $idx;
                    }
                }

                if ($firstIndex !== null && $secondIndex !== null) {
                    // Combine runs
                    $firstRuns = $blocks[$firstIndex]['runs'] ?? [];
                    $secondRuns = $blocks[$secondIndex]['runs'] ?? [];
                    $blocks[$firstIndex]['runs'] = array_merge($firstRuns, $secondRuns);
                    // Remove second block
                    array_splice($blocks, $secondIndex, 1);
                } elseif (! empty($op['originalBlock'])) {
                    // Restore original single block
                    $blocks = array_values(array_filter($blocks, fn ($b) => ! in_array($b['id'] ?? '', [$firstId, $secondId])));
                    $blocks[] = $op['originalBlock'];
                }
                break;
        }

        $model['blocks'] = $blocks;
        $model['metadata']['updatedAt'] = now()->toIso8601String();
        $model['metadata']['version'] = ($model['metadata']['version'] ?? 1) + 1;

        return $model;
    }

    /**
     * Search and replace with scope options without destroying formatting or markup.
     */
    public function searchAndReplace(string $html, string $find, string $replace, array $options = []): array
    {
        if ($find === '') {
            return [
                'success' => false,
                'count' => 0,
                'html' => $html,
                'message' => 'Teks pencarian tidak boleh kosong.',
            ];
        }

        $caseSensitive = ! empty($options['case_sensitive']);
        $wholeWord = ! empty($options['whole_word']);
        $useRegex = ! empty($options['use_regex']);
        $scope = $options['scope'] ?? 'all'; // all, headings, paragraphs, tables

        $pattern = $useRegex
            ? $find
            : preg_quote($find, '/');

        if ($wholeWord && ! $useRegex) {
            $pattern = '\b'.$pattern.'\b';
        }

        $flags = $caseSensitive ? 'u' : 'iu';
        $fullRegex = '/'.$pattern.'/'.$flags;

        // Perform safe replacement exclusively inside text nodes outside tags
        $count = 0;
        $replacedHtml = preg_replace_callback(
            '/(<[^>]+>)|([^<]+)/s',
            function ($matches) use ($fullRegex, $replace, &$count) {
                // If it's an HTML tag, leave it untouched
                if (! empty($matches[1])) {
                    return $matches[1];
                }

                // If it's text content, apply regex replacement
                $text = $matches[2];
                $replaced = preg_replace_callback($fullRegex, function ($m) use ($replace, &$count) {
                    $count++;

                    return $replace;
                }, $text);

                return $replaced;
            },
            $html
        );

        return [
            'success' => true,
            'find' => $find,
            'replace' => $replace,
            'count' => $count,
            'scope' => $scope,
            'html' => $replacedHtml,
        ];
    }

    /**
     * Audit accessibility rules on document model/HTML.
     */
    public function auditAccessibility(array $model): array
    {
        $issues = [];
        $blocks = $model['blocks'] ?? [];

        $lastHeadingLevel = 0;
        $imageCount = 0;
        $imagesMissingAlt = 0;
        $tablesWithoutHeader = 0;

        foreach ($blocks as $index => $b) {
            $type = $b['type'] ?? '';

            if ($type === 'heading') {
                $level = (int) ($b['level'] ?? 1);
                if ($lastHeadingLevel > 0 && $level > $lastHeadingLevel + 1) {
                    $issues[] = [
                        'type' => 'heading_order_skip',
                        'severity' => 'warning',
                        'blockId' => $b['id'] ?? '',
                        'message' => "Struktur heading melompat dari H{$lastHeadingLevel} ke H{$level} tanpa H".($lastHeadingLevel + 1).'.',
                    ];
                }
                $lastHeadingLevel = $level;
            }

            if ($type === 'figure') {
                $imageCount++;
                if (empty(trim($b['alt'] ?? ''))) {
                    $imagesMissingAlt++;
                    $issues[] = [
                        'type' => 'missing_image_alt',
                        'severity' => 'error',
                        'blockId' => $b['id'] ?? '',
                        'message' => 'Gambar tidak memiliki teks alternatif (ALT text) untuk pembaca layar.',
                    ];
                }
            }

            if ($type === 'table') {
                $hasHeader = false;
                foreach ($b['rows'] ?? [] as $row) {
                    foreach ($row['cells'] ?? [] as $cell) {
                        if (! empty($cell['isHeader'])) {
                            $hasHeader = true;
                            break 2;
                        }
                    }
                }
                if (! $hasHeader) {
                    $tablesWithoutHeader++;
                    $issues[] = [
                        'type' => 'table_missing_header',
                        'severity' => 'warning',
                        'blockId' => $b['id'] ?? '',
                        'message' => 'Tabel tidak memiliki baris tajuk (<th>) untuk navigasi aksesibilitas.',
                    ];
                }
            }
        }

        $passed = empty(array_filter($issues, fn ($i) => $i['severity'] === 'error'));

        return [
            'passed' => $passed,
            'score' => max(0, 100 - (count($issues) * 15)),
            'issuesCount' => count($issues),
            'issues' => $issues,
            'summary' => [
                'headingsAudited' => $lastHeadingLevel > 0,
                'imagesAudited' => $imageCount,
                'imagesMissingAlt' => $imagesMissingAlt,
                'tablesWithoutHeader' => $tablesWithoutHeader,
            ],
        ];
    }
}
