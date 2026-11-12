<?php

namespace App\Services;

class ProofingService
{
    /**
     * Common Indonesian typo and PUEBI rules.
     */
    protected array $puebiRules = [
        'diantara' => 'di antara',
        'dimana' => 'di mana',
        'disana' => 'di sana',
        'disini' => 'di sini',
        'kesana' => 'ke sana',
        'kemana' => 'ke mana',
        'merubah' => 'mengubah',
        'praktek' => 'praktik',
        'analisa' => 'analisis',
        'hipotesa' => 'hipotesis',
        'jadual' => 'jadwal',
        'kreatifitas' => 'kreativitas',
        'aktifitas' => 'aktivitas',
        'efektifitas' => 'efektivitas',
        'obyek' => 'objek',
        'subyek' => 'subjek',
        'teoritis' => 'teoretis',
        'resiko' => 'risiko',
        'hirarki' => 'hierarki',
        'azas' => 'asas',
        'debet' => 'debit',
        'kuitansi' => 'kuitansi',
        'kwitansi' => 'kuitansi',
        'mensosialisasikan' => 'menyosialisasikan',
        'mentargetkan' => 'menargetkan',
    ];

    /**
     * Check text for spelling and PUEBI errors.
     */
    public function checkProofing(string $html): array
    {
        $plain = strip_tags($html);
        $words = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($plain), -1, PREG_SPLIT_NO_EMPTY);

        $suggestions = [];
        $foundIssues = 0;

        foreach ($words as $word) {
            if (isset($this->puebiRules[$word])) {
                $foundIssues++;
                $suggestions[] = [
                    'original' => $word,
                    'suggestion' => $this->puebiRules[$word],
                    'type' => 'puebi_standard',
                    'explanation' => "Kata baku menurut KBBI/PUEBI adalah '{$this->puebiRules[$word]}'.",
                ];
            }
        }

        // Calculate readability stats
        $readability = $this->calculateReadability($plain);

        return [
            'success' => true,
            'issuesCount' => $foundIssues,
            'suggestions' => array_values(array_unique($suggestions, SORT_REGULAR)),
            'readability' => $readability,
        ];
    }

    /**
     * Segment text for Read Aloud (text-to-speech) cleanly without tags.
     */
    public function segmentForSpeech(string $html): array
    {
        $plain = strip_tags($html);
        // Split into sentences
        $sentences = preg_split('/(?<=[.?!])\s+/u', trim($plain), -1, PREG_SPLIT_NO_EMPTY);

        $segments = [];
        foreach ($sentences as $index => $sentence) {
            $sentence = trim($sentence);
            if ($sentence !== '') {
                $segments[] = [
                    'index' => $index + 1,
                    'text' => $sentence,
                    'estimatedSeconds' => max(1, (int) round(mb_strlen($sentence) / 15)),
                ];
            }
        }

        return [
            'success' => true,
            'totalSegments' => count($segments),
            'segments' => $segments,
        ];
    }

    /**
     * Calculate readability metrics.
     */
    protected function calculateReadability(string $text): array
    {
        $totalChars = mb_strlen(preg_replace('/\s+/', '', $text));
        $words = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        $totalWords = count($words);

        $sentences = preg_split('/[.?!]+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        $totalSentences = max(1, count($sentences));

        $avgWordsPerSentence = $totalWords > 0 ? round($totalWords / $totalSentences, 1) : 0;
        $avgCharsPerWord = $totalWords > 0 ? round($totalChars / $totalWords, 1) : 0;

        // Reading ease score (adapted Flesch for Indonesian)
        $score = max(0, min(100, (int) round(206.835 - (1.015 * $avgWordsPerSentence) - (8.46 * $avgCharsPerWord))));

        $level = 'Mudah Dipahami';
        if ($score < 40) {
            $level = 'Sangat Sulit (Akademik Tinggi)';
        } elseif ($score < 60) {
            $level = 'Cukup Kompleks (Formal)';
        } elseif ($score < 80) {
            $level = 'Standar';
        }

        return [
            'readingEaseScore' => $score,
            'readingLevel' => $level,
            'wordCount' => $totalWords,
            'sentenceCount' => $totalSentences,
            'avgWordsPerSentence' => $avgWordsPerSentence,
            'avgCharsPerWord' => $avgCharsPerWord,
        ];
    }
}
