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
        Document::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Naskah Publik Terbuka',
            'content_html' => '<p>Publik.</p>',
            'restricted_mode' => 'none',
        ]);

        Document::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Naskah Terkunci Rahasia',
            'content_html' => '<p>Rahasia.</p>',
            'restricted_mode' => 'read_only',
        ]);

        $responseProtected = $this->get('/panel/documents?status=protected');
        $responseProtected->assertStatus(200);
        $responseProtected->assertSee('Naskah Terkunci Rahasia', false);
        $responseProtected->assertDontSee('Naskah Publik Terbuka', false);

        $responseOpen = $this->get('/panel/documents?status=open');
        $responseOpen->assertStatus(200);
        $responseOpen->assertSee('Naskah Publik Terbuka', false);
        $responseOpen->assertDontSee('Naskah Terkunci Rahasia', false);
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
}
