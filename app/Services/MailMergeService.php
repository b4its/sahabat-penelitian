<?php

namespace App\Services;

class MailMergeService
{
    /**
     * Extract merge tokens from template text or HTML.
     */
    public function extractTokens(string $template): array
    {
        $tokens = [];

        // Match {{token}} and «token»
        if (preg_match_all('/(?:\{\{([^}]+)\}\}|«([^»]+)»)/u', $template, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $token = trim(! empty($m[1]) ? $m[1] : $m[2]);
                if ($token !== '') {
                    $tokens[] = $token;
                }
            }
        }

        return array_values(array_unique($tokens));
    }

    /**
     * Parse CSV data into records.
     */
    public function parseCsv(string $csvContent): array
    {
        $lines = explode("\n", trim($csvContent));
        if (empty($lines)) {
            return [];
        }

        $headers = str_getcsv(array_shift($lines));
        $headers = array_map('trim', $headers);

        $records = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $row = str_getcsv($line);
            $record = [];
            foreach ($headers as $index => $header) {
                $record[$header] = isset($row[$index]) ? trim($row[$index]) : '';
            }
            $records[] = $record;
        }

        return $records;
    }

    /**
     * Merge template for a single recipient record (for live preview).
     */
    public function mergeSingle(string $template, array $record): string
    {
        return preg_replace_callback('/(?:\{\{([^}]+)\}\}|«([^»]+)»)/u', function ($m) use ($record) {
            $token = trim(! empty($m[1]) ? $m[1] : $m[2]);

            return isset($record[$token])
                ? htmlspecialchars($record[$token], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                : $m[0];
        }, $template);
    }

    /**
     * Batch merge template with data records with strict recipient isolation.
     */
    public function mergeBatch(string $template, array $records, int $maxBatch = 500): array
    {
        $records = array_slice($records, 0, $maxBatch);
        $mergedResults = [];

        foreach ($records as $index => $record) {
            $mergedContent = $this->mergeSingle($template, $record);
            $mergedResults[] = [
                'recipientIndex' => $index + 1,
                'record' => $record,
                'content' => $mergedContent,
            ];
        }

        return [
            'success' => true,
            'totalRecords' => count($records),
            'results' => $mergedResults,
        ];
    }

    /**
     * Get layout configuration for envelopes or labels.
     */
    public function getMergePresets(): array
    {
        return [
            'letter' => [
                'name' => 'Surat Standar (A4)',
                'paperSize' => 'a4',
                'orientation' => 'portrait',
            ],
            'envelope_dl' => [
                'name' => 'Amplop DL (110 x 220 mm)',
                'paperSize' => 'custom',
                'width_mm' => 220,
                'height_mm' => 110,
                'orientation' => 'landscape',
            ],
            'envelope_c5' => [
                'name' => 'Amplop C5 (162 x 229 mm)',
                'paperSize' => 'custom',
                'width_mm' => 229,
                'height_mm' => 162,
                'orientation' => 'landscape',
            ],
            'labels_3x8' => [
                'name' => 'Label Stiker 3x8 (24 per lembar)',
                'paperSize' => 'a4',
                'columns' => 3,
                'rows' => 8,
            ],
        ];
    }
}
