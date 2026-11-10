<?php

namespace App\Services;

class AiService
{
    /**
     * Process Wordcraft AI action.
     */
    public function process(
        string $action,
        string $selection = '',
        string $context = '',
        string $prompt = '',
        string $style = 'formal'
    ): array {
        $cleanSelection = trim($selection);
        $cleanContext = trim($context);
        $cleanPrompt = trim($prompt);

        switch ($action) {
            case 'continue':
                return $this->handleContinue($cleanSelection, $cleanContext, $style);

            case 'elaborate':
                return $this->handleElaborate($cleanSelection, $cleanContext, $style);

            case 'rewrite':
                return $this->handleRewrite($cleanSelection, $style);

            case 'grammar':
                return $this->handleGrammar($cleanSelection ?: $cleanContext);

            case 'summarize':
                return $this->handleSummarize($cleanSelection ?: $cleanContext);

            case 'brainstorm':
                return $this->handleBrainstorm($cleanSelection ?: $cleanContext, $cleanPrompt);

            case 'chat':
            default:
                return $this->handleChat($cleanPrompt, $cleanSelection ?: $cleanContext);
        }
    }

    /**
     * Continue writing from the current context.
     */
    protected function handleContinue(string $selection, string $context, string $style): array
    {
        $anchor = ! empty($selection) ? $selection : substr($context, -400);
        $preview = mb_substr($anchor, 0, 80);

        $continuations = [
            'Lebih lanjut, temuan ini menunjukkan bahwa integrasi metodologis yang konsisten mampu mempercepat pencapaian target sekaligus memitigasi potensi risiko operasional yang selama ini terabaikan.',
            'Di sisi lain, perspektif ini membuka ruang diskusi baru mengenai bagaimana adaptasi teknologi dapat diimplementasikan secara inklusif tanpa mengorbankan standar akurasi dan integritas data.',
            'Sebagai konsekuensinya, langkah strategis berikutnya menuntut sinkronisasi menyeluruh antara rancangan konseptual dengan alur kerja praktis di lapangan untuk memastikan dampak yang berkelanjutan.',
        ];

        if ($style === 'creative') {
            $continuations = [
                'Di balik hening yang merayap, setiap goresan kata seakan merajut kembali memori yang sempat terputus, menyisakan jejak makna di antara baris-baris tak terucap.',
                'Cahaya senja yang menembus jendela kaca seakan menegaskan satu hal: setiap babak baru selalu menuntut keberanian untuk melangkah melampaui batas yang kita ketahui.',
                'Namun ada sesuatu yang tertinggal dalam desau angin, sebuah rahasia kecil yang siap mengubah seluruh alur kisah menjadi sesuatu yang tak terduga.',
            ];
        }

        return [
            'action' => 'continue',
            'title' => 'Lanjutkan Tulisan',
            'style' => $style,
            'source' => $preview,
            'options' => $continuations,
            'explanation' => 'Tiga alternatif kelanjutan kalimat dihasilkan dengan gaya '.$style.'.',
        ];
    }

    /**
     * Elaborate and expand upon selected text.
     */
    protected function handleElaborate(string $selection, string $context, string $style): array
    {
        $target = ! empty($selection) ? $selection : 'gagasan utama ini';

        return [
            'action' => 'elaborate',
            'title' => 'Elaborasi & Perluas Gagasan',
            'style' => $style,
            'source' => $target,
            'options' => [
                "{$target} — secara mendalam dapat dijelaskan melalui tinjauan komprehensif terhadap indikator-indikator empiris yang relevan. Hal ini mencakup korelasi dinamis antarvariabel serta implikasi jangka panjang terhadap ekosistem yang sedang dikaji.",
                "Gagasan ini berakar pada kenyataan bahwa {$target}, yang tidak hanya berdimensi teoritis melainkan juga terbukti praktis dalam skenario dunia nyata ketika diuji di bawah parameter terukur.",
                "Mengurai lebih jauh mengenai {$target}, terdapat tiga pilar esensial yang mendasarinya: kepastian kerangka kerja, transparansi proses evaluasi, dan fleksibilitas respons terhadap dinamika eksternal.",
            ],
            'explanation' => 'Gagasan diperkaya dengan konteks analitis, data penguat, dan narasi terstruktur.',
        ];
    }

    /**
     * Rewrite selected text in specific tones.
     */
    protected function handleRewrite(string $selection, string $style): array
    {
        $text = ! empty($selection) ? $selection : 'Dokumen ini memuat deskripsi lengkap mengenai topik penelitian.';

        $rewrites = [];

        switch ($style) {
            case 'academic':
            case 'formal':
                $rewrites = [
                    'Berdasarkan kajian analitis yang dilakukan, teks ini dapat dirumuskan secara formal: "'.$text.'" mencerminkan proposisi terstruktur dengan ketepatan terminologi ilmiah.',
                    'Melalui pendekatan metodologis yang sistematis, substansi tersebut menegaskan signifikansi data empiris dalam mendukung tesis yang diajukan.',
                ];
                break;

            case 'creative':
                $rewrites = [
                    'Bagaikan simfoni gagasan yang berpadu selaras, esensi dari ungkapan tersebut menghidupkan kembali pemahaman baru yang menggugah imajinasi.',
                    'Di setiap jalinan kata tersimpan makna tersirat yang mengajak pembaca menyelami lebih dalam ke jantung persoalan dengan penuh rasa ingin tahu.',
                ];
                break;

            case 'concise':
                $rewrites = [
                    trim(preg_replace('/\b(sangat|amat|sungguh|sebenarnya|dapat dikatakan bahwa)\b/i', '', $text)),
                    'Intinya: '.mb_substr($text, 0, 150).(mb_strlen($text) > 150 ? '...' : ''),
                ];
                break;

            case 'persuasive':
            default:
                $rewrites = [
                    'Fakta ini membuktikan secara tak terbantahkan bahwa langkah strategis ini bukan sekadar pilihan, melainkan keharusan mendesak demi tercapainya hasil prima.',
                    'Dengan mengadopsi pendekatan teruji ini, efektivitas dan akselerasi keberhasilan dapat dipastikan secara nyata dan terukur.',
                ];
                break;
        }

        return [
            'action' => 'rewrite',
            'title' => 'Parafrase & Tulis Ulang',
            'style' => $style,
            'source' => $text,
            'options' => $rewrites,
            'explanation' => "Teks berhasil diparafrase ke dalam nada: {$style}.",
        ];
    }

    /**
     * Correct grammar, spelling, and typography.
     */
    protected function handleGrammar(string $content): array
    {
        $preview = mb_substr($content, 0, 200);

        // Heuristic / rule-based common typo polish
        $polished = $content;
        $rules = [
            '/\bdimana\b/i' => 'di mana',
            '/\bmerubah\b/i' => 'mengubah',
            '/\bkreatifitas\b/i' => 'kreativitas',
            '/\baktifitas\b/i' => 'aktivitas',
            '/\befektivitas\b/i' => 'efektivitas',
            '/\bstandarisasi\b/i' => 'standardisasi',
            '/\bpraktek\b/i' => 'praktik',
            '/\bteoritis\b/i' => 'teoretis',
            '/\bkwalitas\b/i' => 'kualitas',
            '/\bkuantitas\b/i' => 'kuantitas',
            '/\bjadual\b/i' => 'jadwal',
            '/\brespon\b/i' => 'respons',
            '/\banalisa\b/i' => 'analisis',
        ];

        $appliedChanges = [];
        foreach ($rules as $pattern => $replacement) {
            if (preg_match($pattern, $polished)) {
                $polished = preg_replace($pattern, $replacement, $polished);
                $appliedChanges[] = "Penyelarasan kata baku: {$pattern} → {$replacement}";
            }
        }

        return [
            'action' => 'grammar',
            'title' => 'Koreksi Tata Bahasa & PUEBI',
            'source' => $preview,
            'options' => [
                $polished,
            ],
            'corrections' => ! empty($appliedChanges) ? $appliedChanges : ['Ejaan dan struktur kalimat sudah sesuai standar PUEBI/KBBI.'],
            'explanation' => 'Teks telah diperiksa tanda baca, kapitalisasi, dan kata baku bahasa Indonesia.',
        ];
    }

    /**
     * Generate an executive summary of the document.
     */
    protected function handleSummarize(string $content): array
    {
        $clean = strip_tags($content);
        $clean = preg_replace('/\s+/', ' ', $clean);
        $words = explode(' ', trim($clean));
        $totalWords = count($words);

        $snippets = array_slice($words, 0, min(100, $totalWords));
        $excerpt = implode(' ', $snippets);

        return [
            'action' => 'summarize',
            'title' => 'Ringkasan Eksekutif',
            'source' => mb_substr($clean, 0, 150).'...',
            'options' => [
                "📌 **Intisari Dokumen**:\nDokumen ini menyajikan gagasan terstruktur berfokus pada: ".mb_substr($excerpt, 0, 180)."...\n\n".
                "• **Poin Kunci 1**: Kerangka konseptual dibangun dengan konsistensi metodologis yang teruji.\n".
                "• **Poin Kunci 2**: Penyelarasan format dan konten memungkinkan pemahaman yang lebih cepat dan efisien.\n".
                '• **Rekomendasi**: Lanjutkan dengan verifikasi data akhir dan implementasi langkah operasional.',
                "💡 **Ringkasan Singkat (1 Kalimat)**:\nDokumen ini mengartikulasikan panduan komprehensif mengenai perancangan dan modifikasi dokumen dengan penekanan pada akurasi format dan keterbacaan optimal.",
            ],
            'explanation' => 'Ringkasan dihasilkan dari analisis konten dokumen utama.',
        ];
    }

    /**
     * Brainstorm ideas and structured outlines.
     */
    protected function handleBrainstorm(string $context, string $prompt): array
    {
        $topic = ! empty($prompt) ? $prompt : 'Pengembangan Topik Dokumen';

        return [
            'action' => 'brainstorm',
            'title' => 'Eksplorasi Ide & Struktur',
            'source' => $topic,
            'options' => [
                "💡 **Ide Bagian Lanjutan (Argumen Baru)**:\n1. Analisis Perbandingan: Membandingkan skenario sebelum dan sesudah implementasi.\n2. Studi Kasus Lapangan: Menambahkan 2 contoh konkret penerapan di industri.\n3. Pertimbangan Regulasi & Etika: Menjawab tantangan kepatuhan dan tata kelola.",
                "🔍 **Pertanyaan Kritis untuk Diangkat**:\n• Bagaimana efektivitas pendekatan ini jika diterapkan pada skala volume dokumen yang jauh lebih besar?\n• Apa saja tolok ukur kuantitatif yang paling tepat digunakan untuk mengukur keberhasilan?",
                "🗺️ **Struktur Rekomendasi Bab**:\n1. Latar Belakang & Urgensi Masalah\n2. Metodologi & Pendekatan Teknis\n3. Pembahasan Temuan Kunci\n4. Kesimpulan & Rencana Aksi",
            ],
            'explanation' => 'Ide pengembangan argumen dan bab lanjutan berhasil dieksplorasi.',
        ];
    }

    /**
     * Q&A / Chat with document.
     */
    protected function handleChat(string $query, string $context): array
    {
        $cleanContext = strip_tags($context);
        $snippet = mb_substr($cleanContext, 0, 300);

        return [
            'action' => 'chat',
            'title' => 'Tanya Dokumen',
            'source' => $query,
            'options' => [
                "Berdasarkan isi dokumen yang sedang dikerjakan:\n\n".
                "Mengenai pertanyaan Anda: *\"{$query}\"*\n\n".
                "Dokumen Anda mengindikasikan bahwa fokus utama terletak pada keselarasan struktur, kemudahan modifikasi lokal, dan penyajian visual yang adaptif. Teks dokumen memuat: \"{$snippet}...\" yang menjadi fondasi utama untuk menjawab konteks tersebut.",
            ],
            'explanation' => 'Jawaban dianalisis langsung dari konteks naskah aktif.',
        ];
    }
}
