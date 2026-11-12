<?php

namespace Tests\Unit;

use App\Http\Controllers\PanelController;
use App\Models\AuditEvent;
use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PanelWhiteBoxTest extends TestCase
{
    protected PanelController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new PanelController;
    }

    /**
     * White-box: bulkCloneDocuments duplicates multiple documents and creates initial version snapshots.
     */
    public function test_white_box_bulk_clone_documents_success(): void
    {
        $doc1 = Document::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Naskah A '.uniqid(),
            'content_html' => '<p>Konten A</p>',
            'restricted_mode' => 'none',
        ]);

        $doc2 = Document::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Naskah B '.uniqid(),
            'content_html' => '<p>Konten B</p>',
            'restricted_mode' => 'read_only',
        ]);

        $request = Request::create('/panel/documents/bulk-clone', 'POST', [
            'uuids' => [$doc1->uuid, $doc2->uuid],
        ]);
        $request->setLaravelSession(session()->driver());

        $response = $this->controller->bulkCloneDocuments($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(route('panel.documents'), $response->getTargetUrl());

        $cloned1 = Document::where('title', "(Salinan) {$doc1->title}")->first();
        $cloned2 = Document::where('title', "(Salinan) {$doc2->title}")->first();

        $this->assertNotNull($cloned1);
        $this->assertNotNull($cloned2);
        $this->assertEquals($doc1->content_html, $cloned1->content_html);
        $this->assertEquals('none', $cloned1->restricted_mode);
        $this->assertEquals('read_only', $cloned2->restricted_mode);

        // Verify version records
        $v1 = DocumentVersion::where('document_id', $cloned1->id)->first();
        $this->assertNotNull($v1);
        $this->assertEquals(1, $v1->version_number);

        // Verify audit trail
        $audit = AuditEvent::where('document_id', $cloned1->id)
            ->where('event_type', 'document_cloned')
            ->first();
        $this->assertNotNull($audit);
    }

    /**
     * White-box: bulkCloneDocuments returns JSON response when requested by client.
     */
    public function test_white_box_bulk_clone_documents_returns_json(): void
    {
        $doc = Document::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Naskah JSON Clone '.uniqid(),
            'content_html' => '<p>Konten JSON</p>',
        ]);

        $request = Request::create('/panel/documents/bulk-clone', 'POST', [
            'uuids' => [$doc->uuid],
        ]);
        $request->headers->set('Accept', 'application/json');
        $request->setLaravelSession(session()->driver());

        $response = $this->controller->bulkCloneDocuments($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertEquals(1, $data['count']);
        $this->assertStringContainsString('1 naskah penelitian berhasil diduplikasi.', $data['message']);
    }

    /**
     * White-box: bulkCloneDocuments fails validation when uuids array is empty or missing.
     */
    public function test_white_box_bulk_clone_documents_validates_uuids_presence(): void
    {
        $this->expectException(ValidationException::class);

        $request = Request::create('/panel/documents/bulk-clone', 'POST', [
            'uuids' => [],
        ]);
        $request->setLaravelSession(session()->driver());

        $this->controller->bulkCloneDocuments($request);
    }

    /**
     * White-box: bulkCloneDocuments returns error redirect when no matching documents found.
     */
    public function test_white_box_bulk_clone_documents_handles_nonexistent_uuids(): void
    {
        $request = Request::create('/panel/documents/bulk-clone', 'POST', [
            'uuids' => [(string) Str::uuid(), (string) Str::uuid()],
        ]);
        $request->setLaravelSession(session()->driver());

        $response = $this->controller->bulkCloneDocuments($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertTrue(session()->has('error'));
        $this->assertStringContainsString('Tidak ada naskah valid yang dipilih', session('error'));
    }

    /**
     * White-box: bulkDestroyDocuments removes documents and version relations and logs audit.
     */
    public function test_white_box_bulk_destroy_documents_success(): void
    {
        $doc1 = Document::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Naskah Hapus 1 '.uniqid(),
            'content_html' => '<p>Konten 1</p>',
        ]);
        DocumentVersion::create([
            'document_id' => $doc1->id,
            'version_number' => 1,
            'title' => $doc1->title,
            'content_html' => $doc1->content_html,
            'author' => 'Peneliti',
        ]);

        $doc2 = Document::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Naskah Hapus 2 '.uniqid(),
            'content_html' => '<p>Konten 2</p>',
        ]);

        $request = Request::create('/panel/documents/bulk-destroy', 'POST', [
            'uuids' => [$doc1->uuid, $doc2->uuid],
        ]);
        $request->setLaravelSession(session()->driver());

        $response = $this->controller->bulkDestroyDocuments($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(route('panel.documents'), $response->getTargetUrl());

        $this->assertNull(Document::find($doc1->id));
        $this->assertNull(Document::find($doc2->id));
        $this->assertEquals(0, DocumentVersion::where('document_id', $doc1->id)->count());

        $audits = AuditEvent::where('event_type', 'document_deleted')->latest()->take(2)->get();
        $this->assertCount(2, $audits);
    }

    /**
     * White-box: bulkDestroyDocuments returns JSON when requested.
     */
    public function test_white_box_bulk_destroy_documents_returns_json(): void
    {
        $doc = Document::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Naskah JSON Destroy '.uniqid(),
            'content_html' => '<p>Konten JSON Destroy</p>',
        ]);

        $request = Request::create('/panel/documents/bulk-destroy', 'POST', [
            'uuids' => [$doc->uuid],
        ]);
        $request->headers->set('Accept', 'application/json');
        $request->setLaravelSession(session()->driver());

        $response = $this->controller->bulkDestroyDocuments($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertEquals(1, $data['count']);
    }

    /**
     * White-box: single destroyDocument supports JSON and records audit log.
     */
    public function test_white_box_single_destroy_document_supports_json(): void
    {
        $doc = Document::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Naskah Single Destroy '.uniqid(),
            'content_html' => '<p>Konten Single Destroy</p>',
        ]);

        $request = Request::create("/panel/documents/{$doc->uuid}", 'DELETE');
        $request->headers->set('Accept', 'application/json');
        $request->setLaravelSession(session()->driver());

        $response = $this->controller->destroyDocument($request, $doc->uuid);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertNull(Document::where('uuid', $doc->uuid)->first());
    }

    /**
     * White-box: updateProfile updates session store and logs audit event.
     */
    public function test_white_box_update_profile_updates_session(): void
    {
        $request = Request::create('/panel/profile', 'POST', [
            'name' => 'Prof. Dr. Maya Anggraini, Ph.D.',
            'role' => 'Kepala Laboratorium Riset',
            'email' => 'maya.anggraini@riset.id',
            'affiliation' => 'Laboratorium Komputasi Sains Terapan',
        ]);
        $request->setLaravelSession(session()->driver());

        $response = $this->controller->updateProfile($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('Prof. Dr. Maya Anggraini, Ph.D.', session('panel_user_name'));
        $this->assertEquals('Kepala Laboratorium Riset', session('panel_user_role'));
        $this->assertEquals('maya.anggraini@riset.id', session('panel_user_email'));
        $this->assertEquals('Laboratorium Komputasi Sains Terapan', session('panel_user_affiliation'));

        $audit = AuditEvent::where('event_type', 'profile_updated')
            ->where('actor', 'Prof. Dr. Maya Anggraini, Ph.D.')
            ->first();
        $this->assertNotNull($audit);
    }

    /**
     * White-box: updateProfile returns JSON response when requested.
     */
    public function test_white_box_update_profile_returns_json(): void
    {
        $request = Request::create('/panel/profile', 'POST', [
            'name' => 'Dr. Bambang Sutrisno',
            'role' => 'Reviewer Ahli',
            'email' => 'bambang@riset.id',
        ]);
        $request->headers->set('Accept', 'application/json');
        $request->setLaravelSession(session()->driver());

        $response = $this->controller->updateProfile($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertEquals('Dr. Bambang Sutrisno', $data['user']['name']);
        $this->assertEquals('Reviewer Ahli', $data['user']['role']);
    }

    /**
     * White-box: logout cleans session variables and records audit log.
     */
    public function test_white_box_logout_clears_session(): void
    {
        session([
            'panel_user_name' => 'Dr. Peneliti Logout',
            'panel_user_role' => 'Penulis',
            'panel_user_email' => 'logout@test.id',
            'panel_user_affiliation' => 'Pusat Riset',
        ]);

        $request = Request::create('/panel/logout', 'POST');
        $request->setLaravelSession(session()->driver());

        $response = $this->controller->logout($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(url('/'), $response->getTargetUrl());

        $this->assertFalse(session()->has('panel_user_name'));
        $this->assertFalse(session()->has('panel_user_role'));
        $this->assertFalse(session()->has('panel_user_email'));
        $this->assertFalse(session()->has('panel_user_affiliation'));

        $audit = AuditEvent::where('event_type', 'user_logged_out')
            ->where('actor', 'Dr. Peneliti Logout')
            ->first();
        $this->assertNotNull($audit);
    }
}
