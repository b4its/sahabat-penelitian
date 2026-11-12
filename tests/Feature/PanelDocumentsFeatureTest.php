<?php

namespace Tests\Feature;

use App\Models\Document;
use Illuminate\Support\Str;
use Tests\TestCase;

class PanelDocumentsFeatureTest extends TestCase
{
    /**
     * Test documents list view loads successfully.
     */
    public function test_documents_view_loads_successfully(): void
    {
        $response = $this->get('/panel/documents');
        $response->assertStatus(200);

        $response->assertSee('Manajemen Dokumen &amp; Naskah', false);
        $response->assertSee('Buat Naskah Baru', false);
        $response->assertSee('Cari berdasarkan judul naskah...', false);
    }

    /**
     * Test search documents filters results accurately.
     */
    public function test_documents_search_filters_by_title(): void
    {
        Document::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Pengembangan Algoritma Kriptografi Quantum',
            'content_html' => '<p>Konten kuantum.</p>',
        ]);

        Document::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Studi Etnografi Masyarakat Adat Nusantara',
            'content_html' => '<p>Konten etnografi.</p>',
        ]);

        // Search for 'Kriptografi'
        $responseMatch = $this->get('/panel/documents?q=Kriptografi');
        $responseMatch->assertStatus(200);
        $responseMatch->assertSee('Pengembangan Algoritma Kriptografi Quantum', false);
        $responseMatch->assertDontSee('Studi Etnografi Masyarakat Adat Nusantara', false);

        // Search for 'Etnografi'
        $responseOther = $this->get('/panel/documents?q=Etnografi');
        $responseOther->assertStatus(200);
        $responseOther->assertSee('Studi Etnografi Masyarakat Adat Nusantara', false);
        $responseOther->assertDontSee('Pengembangan Algoritma Kriptografi Quantum', false);
    }

    /**
     * Test filter documents by access status.
     */
    public function test_documents_filter_by_status(): void
    {
        $titleOpen = 'Naskah Publik Terbuka '.uniqid();
        $titleProtected = 'Naskah Terkunci Rahasia '.uniqid();

        Document::create([
            'uuid' => (string) Str::uuid(),
            'title' => $titleOpen,
            'content_html' => '<p>Publik.</p>',
            'restricted_mode' => 'none',
        ]);

        Document::create([
            'uuid' => (string) Str::uuid(),
            'title' => $titleProtected,
            'content_html' => '<p>Rahasia.</p>',
            'restricted_mode' => 'read_only',
        ]);

        $responseProtected = $this->get('/panel/documents?status=protected&q='.urlencode($titleProtected));
        $responseProtected->assertStatus(200);
        $responseProtected->assertSee($titleProtected, false);
        $responseProtected->assertDontSee($titleOpen, false);

        $responseOpen = $this->get('/panel/documents?status=open&q='.urlencode($titleOpen));
        $responseOpen->assertStatus(200);
        $responseOpen->assertSee($titleOpen, false);
        $responseOpen->assertDontSee($titleProtected, false);
    }

    /**
     * Test creating a new document via panel stores to database and redirects to editor.
     */
    public function test_store_document_from_panel_creates_record_and_version(): void
    {
        $uniqueTitle = 'Analisis Sentimen Opini Publik Pemilu '.uniqid();

        $response = $this->post('/panel/documents', [
            'title' => $uniqueTitle,
            'template' => 'journal',
            'restricted_mode' => 'none',
        ]);

        $doc = Document::where('title', $uniqueTitle)->latest()->first();
        $this->assertNotNull($doc);
        $this->assertEquals(1, $doc->current_version);
        $this->assertStringContainsString('Abstrak', $doc->content_html);

        $response->assertRedirect("/editor?doc={$doc->uuid}");
    }

    /**
     * Test creating document via JSON API returns json payload and redirect url.
     */
    public function test_store_document_via_json_returns_json_response(): void
    {
        $response = $this->postJson('/panel/documents', [
            'title' => 'Perancangan Sistem Rekomendasi E-Commerce',
            'template' => 'thesis',
            'restricted_mode' => 'none',
        ]);

        $response->assertStatus(200)->assertJsonPath('success', true);
        $uuid = $response->json('document.uuid');
        $this->assertNotEmpty($uuid);
        $this->assertStringContainsString("/editor?doc={$uuid}", $response->json('redirect_url'));
    }

    /**
     * Test cloning an existing document creates duplicate with copy prefix.
     */
    public function test_clone_document_creates_duplicate_with_version(): void
    {
        $doc = Document::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Naskah Asli Penelitian',
            'content_html' => '<p>Teks naskah asli penting.</p>',
        ]);

        $response = $this->post("/panel/documents/{$doc->uuid}/clone");
        $response->assertRedirect(route('panel.documents'));

        $cloned = Document::where('title', '(Salinan) Naskah Asli Penelitian')->first();
        $this->assertNotNull($cloned);
        $this->assertNotEquals($doc->uuid, $cloned->uuid);
        $this->assertEquals('<p>Teks naskah asli penting.</p>', $cloned->content_html);
    }

    /**
     * Test destroying a document removes it from database.
     */
    public function test_destroy_document_deletes_record(): void
    {
        $doc = Document::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Naskah Untuk Dihapus',
            'content_html' => '<p>Hapus saya.</p>',
        ]);

        $response = $this->delete("/panel/documents/{$doc->uuid}");
        $response->assertRedirect(route('panel.documents'));

        $this->assertNull(Document::where('uuid', $doc->uuid)->first());
    }

    /**
     * Test editor loads specific document content when doc uuid query is passed.
     */
    public function test_editor_loads_document_content_via_doc_query_parameter(): void
    {
        $doc = Document::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Dokumen Khusus Dari Panel.docx',
            'content_html' => '<h1>Dokumen Khusus Dari Panel</h1><p>Paragraf unik yang harus dimuat ke canvas editor.</p>',
        ]);

        $response = $this->get("/editor?doc={$doc->uuid}");
        $response->assertStatus(200);
        $response->assertSee('Dokumen Khusus Dari Panel.docx', false);
        $response->assertSee('Paragraf unik yang harus dimuat ke canvas editor.', false);
    }

    /**
     * Test documents list applies pagination with 5 items per page when data exceeds 5.
     */
    public function test_documents_pagination_activates_when_count_exceeds_five(): void
    {
        // Seed 8 distinct documents
        for ($i = 1; $i <= 8; $i++) {
            Document::create([
                'uuid' => (string) Str::uuid(),
                'title' => "Naskah Penelitian Batch Serial Ke-{$i}",
                'content_html' => "<p>Isi naskah serial {$i}.</p>",
            ]);
        }

        // 1. First Page (Should display 5 items and pagination bar)
        $responsePage1 = $this->get('/panel/documents');
        $responsePage1->assertStatus(200);

        // Pagination summary text
        $responsePage1->assertSee('Menampilkan', false);
        $responsePage1->assertSee('1', false);
        $responsePage1->assertSee('5', false);
        $responsePage1->assertSee('page=2', false);

        // 2. Second Page
        $responsePage2 = $this->get('/panel/documents?page=2');
        $responsePage2->assertStatus(200);
        $responsePage2->assertSee('Menampilkan', false);
        $responsePage2->assertSee('6', false);
    }

    /**
     * Test documents pagination maintains search query string across pages.
     */
    public function test_documents_pagination_preserves_search_query_parameters(): void
    {
        // Seed 7 documents matching "Kecerdasan Buatan"
        for ($i = 1; $i <= 7; $i++) {
            Document::create([
                'uuid' => (string) Str::uuid(),
                'title' => "Kecerdasan Buatan Terapan Volume {$i}",
                'content_html' => "<p>Isi AI volume {$i}.</p>",
            ]);
        }

        $response = $this->get('/panel/documents?q=Kecerdasan&page=1');
        $response->assertStatus(200);
        $response->assertSee('q=Kecerdasan', false);
        $response->assertSee('page=2', false);
    }

    /**
     * Test documents table and pagination containers enforce responsive overflow containment.
     */
    public function test_documents_table_and_pagination_use_responsive_overflow_containment(): void
    {
        $response = $this->get('/panel/documents');
        $response->assertStatus(200);

        // Responsive table scrolling wrapper prevents table collapse or squashed columns
        $response->assertSee('overflow-x-auto w-full', false);
        $response->assertSee('min-w-[640px]', false);

        // Pagination container supports flex wrapping across mobile and desktop
        $response->assertSee('flex flex-col sm:flex-row items-center justify-between gap-4', false);
    }
}
