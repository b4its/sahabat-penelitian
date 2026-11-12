<?php

namespace App\Http\Controllers;

use App\Models\AuditEvent;
use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PanelController extends Controller
{
    /**
     * Display the main Panel Dashboard view with research metrics and recent activity.
     */
    public function dashboard(): View
    {
        // Ensure at least one starter document exists for demonstration if database is empty
        $this->ensureSeedDocumentExists();

        $totalDocuments = Document::count();
        $totalVersions = DocumentVersion::count();
        $protectedDocuments = Document::where('restricted_mode', '!=', 'none')
            ->orWhereNotNull('password_hash')
            ->count();

        $recentDocuments = Document::latest()->paginate(5, ['*'], 'recent_page')->withQueryString();
        $recentAudits = AuditEvent::latest()->take(6)->get();

        return view('panel.dashboard', [
            'totalDocuments' => $totalDocuments,
            'totalVersions' => $totalVersions,
            'protectedDocuments' => $protectedDocuments,
            'recentDocuments' => $recentDocuments,
            'recentAudits' => $recentAudits,
        ]);
    }

    /**
     * Display the Document Management view with search and filter capabilities.
     */
    public function documents(Request $request): View
    {
        $this->ensureSeedDocumentExists();

        $query = Document::query();

        if ($request->filled('q')) {
            $searchTerm = trim($request->input('q'));
            $query->where('title', 'like', "%{$searchTerm}%");
        }

        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'protected') {
                $query->where(function ($q) {
                    $q->where('restricted_mode', '!=', 'none')->orWhereNotNull('password_hash');
                });
            } elseif ($status === 'open') {
                $query->where('restricted_mode', 'none')->whereNull('password_hash');
            }
        }

        $documents = $query->latest()->paginate(5)->withQueryString();

        return view('panel.documents', [
            'documents' => $documents,
            'searchQuery' => $request->input('q', ''),
            'currentFilter' => $request->input('status', 'all'),
            'totalCount' => Document::count(),
        ]);
    }

    /**
     * Create a new research document and redirect to editor.
     */
    public function storeDocument(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'template' => ['nullable', 'string', 'in:journal,thesis,proposal,blank'],
            'restricted_mode' => ['nullable', 'string', 'in:none,read_only,comments_only'],
        ]);

        $title = trim($request->input('title'));
        $template = $request->input('template', 'journal');
        $restrictedMode = $request->input('restricted_mode', 'none');

        $initialContent = match ($template) {
            'thesis' => $this->getThesisTemplate($title),
            'proposal' => $this->getProposalTemplate($title),
            'blank' => "<p>Mulai menulis draf naskah {$title} di sini...</p>",
            default => $this->getJournalTemplate($title),
        };

        $document = Document::create([
            'uuid' => (string) Str::uuid(),
            'title' => $title,
            'content_html' => $initialContent,
            'restricted_mode' => $restrictedMode,
            'current_version' => 1,
        ]);

        // Create initial snapshot version
        DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => 1,
            'title' => $title,
            'content_html' => $initialContent,
            'author' => 'Dr. Peneliti (Penulis Utama)',
            'change_summary' => 'Inisiasi draf naskah awal via Panel Riset',
        ]);

        // Log audit event
        AuditEvent::create([
            'document_id' => $document->id,
            'event_type' => 'document_created',
            'actor' => 'Dr. Peneliti',
            'details' => ['template' => $template, 'title' => $title],
            'ip_address' => $request->ip(),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'document' => $document,
                'redirect_url' => url("/editor?doc={$document->uuid}"),
            ]);
        }

        return redirect("/editor?doc={$document->uuid}")->with('success', "Dokumen {$title} berhasil dibuat.");
    }

    /**
     * Duplicate an existing document.
     */
    public function cloneDocument(Request $request, string $uuid): RedirectResponse
    {
        $sourceDoc = Document::where('uuid', $uuid)->firstOrFail();

        $cloned = Document::create([
            'uuid' => (string) Str::uuid(),
            'title' => "(Salinan) {$sourceDoc->title}",
            'content_html' => $sourceDoc->content_html,
            'model_json' => $sourceDoc->model_json,
            'restricted_mode' => $sourceDoc->restricted_mode,
            'current_version' => 1,
        ]);

        // Copy version
        DocumentVersion::create([
            'document_id' => $cloned->id,
            'version_number' => 1,
            'title' => $cloned->title,
            'content_html' => $sourceDoc->content_html ?? '<p>Konten salinan dokumen.</p>',
            'author' => 'Sistem Panel',
            'change_summary' => "Duplikasi dari berkas {$sourceDoc->title}",
        ]);

        AuditEvent::create([
            'document_id' => $cloned->id,
            'event_type' => 'document_cloned',
            'actor' => 'Sistem Panel',
            'details' => ['source_uuid' => $sourceDoc->uuid],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('panel.documents')->with('success', "Naskah {$cloned->title} berhasil diduplikasi.");
    }

    /**
     * Delete a document from the system.
     */
    public function destroyDocument(Request $request, string $uuid): RedirectResponse
    {
        $doc = Document::where('uuid', $uuid)->firstOrFail();
        $title = $doc->title;
        $doc->delete();

        return redirect()->route('panel.documents')->with('success', "Dokumen {$title} telah berhasil dihapus.");
    }

    /**
     * Ensure at least one default document exists if database is empty.
     */
    protected function ensureSeedDocumentExists(): void
    {
        if (Document::count() === 0) {
            $starter = Document::create([
                'uuid' => (string) Str::uuid(),
                'title' => 'Rencana Proyek Penelitian 2026.docx',
                'content_html' => '<h1>Rencana Proyek Penelitian 2026</h1><p class="lead">Panduan komprehensif implementasi teknologi berbasis naskah dan penyelarasan format dokumen otomatis.</p><hr /><h2>1. Pendahuluan & Latar Belakang</h2><p>Pengelolaan berkas dokumen teks sering kali menghadapi tantangan fragmentasi format antar aplikasi konvensional. Sistem ini menyatukan kenyamanan visual, presisi tata letak kertas A4, serta kecerdasan asistensi penulisan ke dalam satu ruang kerja tunggal.</p>',
                'restricted_mode' => 'none',
                'current_version' => 1,
            ]);

            DocumentVersion::create([
                'document_id' => $starter->id,
                'version_number' => 1,
                'title' => $starter->title,
                'content_html' => $starter->content_html,
                'author' => 'Tim Pengembang Sahabat Penelitian',
                'change_summary' => 'Inisiasi dokumen dasar bawaan sistem',
            ]);

            AuditEvent::create([
                'document_id' => $starter->id,
                'event_type' => 'system_init',
                'actor' => 'Sistem',
                'details' => ['note' => 'Dokumen bawaan diinisiasi'],
            ]);
        }
    }

    /**
     * Templates for initial documents
     */
    protected function getJournalTemplate(string $title): string
    {
        return <<<HTML
<h1>{$title}</h1>
<p class="text-center italic text-sm">Penulis Utama¹, Penulis Pendamping²</p>
<hr />
<h2>Abstrak</h2>
<p>Tuliskan ringkasan penelitian yang mencakup latar belakang, tujuan, metode yang digunakan, hasil temuan kunci, serta kontribusi ilmiah dari karya tulis ini (maksimal 250 kata).</p>
<h2>1. Pendahuluan</h2>
<p>Uraikan latar belakang masalah, tinjauan literatur terkini, perumusan research gap, dan tujuan spesifik dari penelitian ini.</p>
<h2>2. Metodologi Penelitian</h2>
<p>Jelaskan tahapan eksperimen, pengumpulan data, instrumen, dan teknik analisis data yang diterapkan.</p>
<h2>3. Hasil dan Pembahasan</h2>
<p>Sajikan temuan empiris dalam bentuk narasi yang didukung tabel, grafik, dan perbandingan dengan studi terdahulu.</p>
<h2>4. Kesimpulan</h2>
<p>Sintesiskan temuan utama dan berikan rekomendasi untuk penelitian lanjutan.</p>
HTML;
    }

    protected function getThesisTemplate(string $title): string
    {
        return <<<HTML
<h1 class="text-center uppercase font-bold">{$title}</h1>
<p class="text-center font-semibold">SKRIPSI / TESIS PASCASARJANA</p>
<hr />
<h2>BAB I PENDAHULUAN</h2>
<h3>1.1 Latar Belakang Masalah</h3>
<p>Uraikan fenomena, data statistik awal, dan urgensi penelitian yang melatarbelakangi penulisan karya ilmiah ini.</p>
<h3>1.2 Rumusan Masalah</h3>
<p>Kemukakan pertanyaan penelitian secara terarah, padat, dan jelas.</p>
<h3>1.3 Tujuan Penelitian</h3>
<p>Target capaian yang hendak dibuktikan dalam penelitian ini.</p>
<h2>BAB II TINJAUAN PUSTAKA</h2>
<p>Kajian teori dan sintesis referensi dari jurnal terakreditasi bereputasi.</p>
HTML;
    }

    protected function getProposalTemplate(string $title): string
    {
        return <<<HTML
<h1>PROPOSAL PENELITIAN: {$title}</h1>
<hr />
<h2>1. Judul dan Bidang Fokus Riset</h2>
<p>Deskripsikan keselarasan riset dengan rencana induk riset nasional atau institusi.</p>
<h2>2. Peta Jalan Penelitian (Roadmap)</h2>
<p>Tahapan pelaksanaan riset tahun ke-1 hingga tahun ke-3 beserta luaran wajib (artikel jurnal, paten, prototipe).</p>
<h2>3. Jadwal dan Rencana Anggaran Biaya (RAB)</h2>
<p>Alokasi kebutuhan operasional, bahan habis pakai, dan pengujian laboratorium.</p>
HTML;
    }
}
