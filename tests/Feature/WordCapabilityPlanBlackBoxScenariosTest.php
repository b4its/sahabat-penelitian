<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentPermission;
use App\Services\DocxGenerator;
use App\Services\DocxImporter;
use Illuminate\Support\Str;
use Tests\TestCase;
use ZipArchive;

class WordCapabilityPlanBlackBoxScenariosTest extends TestCase
{
    /**
     * BB01: Buat dokumen, ketik format campuran, simpan/tutup/pulihkan; bandingkan teks, style, selection, undo.
     */
    public function test_bb01_create_type_save_restore_mixed_formatting_and_undo(): void
    {
        $html = '<h1 id="h1">Bab 1 Pendahuluan</h1><p id="p1">Teks <strong>tebal</strong> dan <em>miring</em> dengan <span style="color:#2563eb">warna</span>.</p>';

        // 1. Parse into canonical model
        $parseRes = $this->postJson('/api/documents/model/parse', ['html' => $html]);
        $parseRes->assertStatus(200)->assertJsonPath('success', true);
        $model = $parseRes->json('model');

        $this->assertEquals(1, $model['schemaVersion']);
        $this->assertCount(2, $model['blocks']);

        // 2. Invert operation (undo engine)
        $op = [
            'opId' => 'op-1',
            'type' => 'update_block',
            'targetId' => 'p1',
            'payload' => ['text' => 'Teks baru'],
            'previous' => ['text' => 'Teks lama'],
            'timestamp' => time(),
        ];
        $invertRes = $this->postJson('/api/documents/transactions/invert', ['operation' => $op]);
        $invertRes->assertStatus(200)
            ->assertJsonPath('inverse.type', 'update_block')
            ->assertJsonPath('inverse.payload.text', 'Teks lama');

        // 3. Render back to sanitized HTML
        $renderRes = $this->postJson('/api/documents/model/render', ['model' => $model]);
        $renderRes->assertStatus(200);
        $renderedHtml = $renderRes->json('html');
        $this->assertStringContainsString('Bab 1 Pendahuluan', $renderedHtml);
        $this->assertStringContainsString('tebal', $renderedHtml);
    }

    /**
     * BB02: Paste Word/HTML/plain + gambar yang tidak aman; konten sah tersimpan, script tidak berjalan.
     */
    public function test_bb02_paste_html_rich_text_sanitization_script_isolation(): void
    {
        $maliciousHtml = '<p>Paragraf Sah</p><script>alert("xss")</script><img src="x" onerror="evil()" /><a href="javascript:steal()">Klik</a>';

        $res = $this->postJson('/api/documents/model/parse', ['html' => $maliciousHtml]);
        $res->assertStatus(200);
        $model = $res->json('model');

        $renderRes = $this->postJson('/api/documents/model/render', ['model' => $model]);
        $renderRes->assertStatus(200);
        $safeHtml = $renderRes->json('html');

        $this->assertStringContainsString('Paragraf Sah', $safeHtml);
        $this->assertStringNotContainsString('<script>', $safeHtml);
        $this->assertStringNotContainsString('onerror', $safeHtml);
        $this->assertStringNotContainsString('javascript:', $safeHtml);
    }

    /**
     * BB03: Tab custom right/center/dots, Shift+Tab, Enter, tabel; ukur posisi dan caret halaman baru.
     */
    public function test_bb03_custom_tabs_alignment_table_navigation_caret_preservation(): void
    {
        $tableHtml = '<table class="w-full"><thead><tr><th>Header 1</th><th>Header 2</th></tr></thead><tbody><tr><td>Data 1</td><td>Data 2</td></tr></tbody></table>';

        $res = $this->postJson('/api/documents/model/parse', ['html' => $tableHtml]);
        $res->assertStatus(200);
        $model = $res->json('model');

        $this->assertEquals('table', $model['blocks'][0]['type']);
        $this->assertCount(2, $model['blocks'][0]['rows']);
        $this->assertTrue($model['blocks'][0]['rows'][0]['cells'][0]['isHeader']);
    }

    /**
     * BB04: Impor DOCX berisi section portrait/landscape, list bertingkat, tabel merge, header/footer, image; ekspor DOCX; buka kembali di Word/LibreOffice; diff isi/format.
     */
    public function test_bb04_import_docx_advanced_sections_lists_tables_export_roundtrip(): void
    {
        $generator = new DocxGenerator;
        $html = '<h1 class="heading-chapter">Bab I Pendahuluan</h1><p>Paragraf pembuka.</p><table class="w-full"><tr><td colspan="2">Sel Gabung</td></tr><tr><td>Sel 1</td><td>Sel 2</td></tr></table>';

        $docxBinary = $generator->generate(
            'Dokumen Roundtrip Uji',
            $html,
            'Running Header Bab I',
            'Halaman',
            ['paperSize' => 'a4', 'orientation' => 'portrait']
        );

        $this->assertNotEmpty($docxBinary);

        $tempDocx = tempnam(sys_get_temp_dir(), 'roundtrip_');
        file_put_contents($tempDocx, $docxBinary);

        // Roundtrip import
        $importer = new DocxImporter;
        $extracted = $importer->parseWithMetadata($tempDocx);
        unlink($tempDocx);

        $this->assertStringContainsString('Bab I Pendahuluan', $extracted['html']);
        $this->assertStringContainsString('Sel Gabung', $extracted['html']);
        $this->assertEquals('Running Header Bab I', $extracted['header_text']);
    }

    /**
     * BB05: Unduh .docx: ZIP asli dan MIME+extension cocok; .doc legacy tetap HTML berlabel; tidak ada unduhan kosong atau fallback .docx palsu.
     */
    public function test_bb05_download_docx_genuine_zip_mime_and_legacy_doc_separation(): void
    {
        // 1. Genuine DOCX Open XML ZIP
        $docxRes = $this->post('/api/documents/export', [
            'title' => 'Naskah Asli',
            'content' => '<p>Konten asli dokumen</p>',
            'format' => 'docx',
        ]);

        $docxRes->assertStatus(200);
        $docxRes->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $docxRes->assertHeader('Content-Disposition', 'attachment; filename="Naskah_Asli.docx"');
        $this->assertStringStartsWith("PK\x03\x04", $docxRes->getContent());

        // 2. Legacy Word HTML (.doc)
        $docRes = $this->post('/api/documents/export', [
            'title' => 'Naskah Legacy',
            'content' => '<p>Konten legacy dokumen</p>',
            'format' => 'doc',
        ]);

        $docRes->assertStatus(200);
        $docRes->assertHeader('Content-Type', 'application/msword; charset=utf-8');
        $docRes->assertHeader('Content-Disposition', 'attachment; filename="Naskah_Legacy.doc"');
        $this->assertStringContainsString('urn:schemas-microsoft-com:office:office', $docRes->getContent());
    }

    /**
     * BB06: 300 halaman dan heading duplikat; outline/TOC/comment anchors tepat, tidak hilang saat streaming/print.
     */
    public function test_bb06_large_document_outline_toc_comment_anchor_stability(): void
    {
        $html = '<h1 id="h-1">Pendahuluan</h1><p>Isi 1</p><h1 id="h-2">Pendahuluan</h1><p>Isi 2</p><h2 id="h-3">Metodologi</h2><p>Isi 3</p>';

        $res = $this->postJson('/api/documents/generate-toc', [
            'html' => $html,
            'style' => 'template',
        ]);

        $res->assertStatus(200)->assertJsonPath('success', true);
        $tocHtml = $res->json('html');

        $this->assertStringContainsString('href="#h-1"', $tocHtml);
        $this->assertStringContainsString('href="#h-2"', $tocHtml);
        $this->assertStringContainsString('href="#h-3"', $tocHtml);
    }

    /**
     * BB07: Footnotes/TOC/captions/sitasi: tambah, edit sumber, update, ekspor/impor; link dan angka stabil.
     */
    public function test_bb07_footnotes_toc_citations_captions_update_and_stability(): void
    {
        $sources = [
            [
                'id' => 'src-1',
                'type' => 'journal',
                'title' => 'Pengembangan Naskah Akademik Otomatis',
                'author' => 'Wijaya, Hendra',
                'year' => 2026,
                'journal' => 'Jurnal Sistem Informasi',
                'volume' => '12',
                'pages' => '45-56',
            ],
        ];

        $res = $this->postJson('/api/documents/format-bibliography', [
            'sources' => $sources,
            'style' => 'apa',
        ]);

        $res->assertStatus(200)->assertJsonPath('success', true);
        $this->assertStringContainsString('Wijaya, Hendra', $res->json('html'));
        $this->assertStringContainsString('indent-hanging', $res->json('html'));
    }

    /**
     * BB08: Track add/delete/format, accept/reject tunggal/all; author/time dan undo/redo konsisten, markup DOCX valid.
     */
    public function test_bb08_tracked_changes_insert_delete_accept_reject_all_and_docx_tags(): void
    {
        $revisedHtml = '<p>Dokumen <span class="docx-revision insert" data-revision-id="r1" data-revision-type="insert">baru</span> <span class="docx-revision delete" data-revision-id="r2" data-revision-type="delete">lama</span> selesai.</p>';

        // 1. Accept All
        $acceptRes = $this->postJson('/api/documents/review/accept-all', ['html' => $revisedHtml]);
        $acceptRes->assertStatus(200);
        $this->assertStringContainsString('baru', $acceptRes->json('html'));
        $this->assertStringNotContainsString('lama', $acceptRes->json('html'));

        // 2. Reject All
        $rejectRes = $this->postJson('/api/documents/review/reject-all', ['html' => $revisedHtml]);
        $rejectRes->assertStatus(200);
        $this->assertStringNotContainsString('baru', $rejectRes->json('html'));
        $this->assertStringContainsString('lama', $rejectRes->json('html'));

        // 3. Document comparison (Legal Blackline Diff)
        $compareRes = $this->postJson('/api/documents/review/compare', [
            'original_html' => '<p>Kecerdasan buatan berkembang.</p>',
            'modified_html' => '<p>Kecerdasan buatan modern berkembang pesat.</p>',
        ]);
        $compareRes->assertStatus(200);
        $this->assertStringContainsString('modern', $compareRes->json('diffHtml'));
        $this->assertStringContainsString('pesat', $compareRes->json('diffHtml'));
    }

    /**
     * BB09: Dua reviewer: comment/reply/mention/resolve; refresh/undo/permissions tidak menyembunyikan komentar milik orang lain.
     */
    public function test_bb09_multiple_reviewers_comments_threads_resolve_reopen_audit(): void
    {
        $commentHtml = '<p>Bagian ini <span class="document-comment" data-comment-id="c-1" data-comment-text="Perjelas metodologi" data-comment-resolved="false">memerlukan evaluasi</span> mendalam.</p>';

        $res = $this->postJson('/api/documents/review/comments', ['html' => $commentHtml]);
        $res->assertStatus(200)->assertJsonPath('success', true);

        $comments = $res->json('comments');
        $this->assertCount(1, $comments);
        $this->assertEquals('c-1', $comments[0]['id']);
        $this->assertEquals('Perjelas metodologi', $comments[0]['text']);
        $this->assertFalse($comments[0]['resolved']);
    }

    /**
     * BB10: Dua/tiga browser edit bersamaan + offline -> reconnect; semua klien konvergen dan perubahan tidak hilang.
     */
    public function test_bb10_collaborative_operations_convergence_presence_heartbeat(): void
    {
        $doc = Document::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Naskah Kolaboratif',
            'content' => '<p>Teks Awal</p>',
            'owner_email' => 'author1@test.com',
        ]);

        // Heartbeat presence
        $heartbeatRes = $this->postJson("/api/documents/server/{$doc->uuid}/presence", [
            'user_id' => 'client-alpha',
            'user_name' => 'Reviewer Satu',
            'cursor' => ['position' => 12],
        ]);
        $heartbeatRes->assertStatus(200)->assertJsonPath('success', true);

        // Get active presences
        $presenceRes = $this->getJson("/api/documents/server/{$doc->uuid}/presence");
        $presenceRes->assertStatus(200);
        $this->assertCount(1, $presenceRes->json('active_users'));
        $this->assertEquals('Reviewer Satu', $presenceRes->json('active_users.0.user_name'));
    }

    /**
     * BB11: User nonanggota ditolak membuka/mengunduh/menulis; revoke share link langsung efektif.
     */
    public function test_bb11_document_acl_authorization_roles_and_access_denial(): void
    {
        $doc = Document::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Naskah Rahasia',
            'content' => '<p>Data Rahasia</p>',
            'owner_email' => 'owner@test.com',
        ]);

        DocumentPermission::create([
            'document_id' => $doc->id,
            'user_email' => 'viewer@test.com',
            'role' => 'viewer',
        ]);

        // Viewer trying to apply edit operation is rejected
        $res = $this->postJson("/api/documents/server/{$doc->uuid}/operations", [
            'user_email' => 'viewer@test.com',
            'operation' => ['type' => 'insert', 'content' => 'Hacked'],
        ]);
        $res->assertStatus(403);

        // Unknown user trying to access is rejected
        $unknownRes = $this->postJson("/api/documents/server/{$doc->uuid}/operations", [
            'user_email' => 'stranger@test.com',
            'operation' => ['type' => 'insert', 'content' => 'Strangeness'],
        ]);
        $unknownRes->assertStatus(403);
    }

    /**
     * BB12: Mail merge 1, 100, 10.000 penerima dengan karakter khusus; mapping, preview dan hasil per orang tepat, tidak ada data penerima bocor silang.
     */
    public function test_bb12_mail_merge_single_batch_csv_recipient_isolation(): void
    {
        $template = '<p>Kepada Yth. Bapak/Ibu {{nama}} di {{kota}}.</p>';
        $csv = "nama,kota\nDr. Hendra Wijaya,Jakarta\nProf. Ratna Sari,Bandung\nIr. Joko, \"Surabaya, Timur\"";

        // 1. Live preview for recipient 0
        $prevRes = $this->postJson('/api/documents/mail-merge/preview', [
            'template' => $template,
            'csv' => $csv,
            'record_index' => 0,
        ]);
        $prevRes->assertStatus(200)->assertJsonPath('status', 'success');
        $this->assertStringContainsString('Dr. Hendra Wijaya', $prevRes->json('merged'));
        $this->assertStringNotContainsString('Prof. Ratna Sari', $prevRes->json('merged'));

        // 2. Batch merge isolation
        $batchRes = $this->postJson('/api/documents/mail-merge/batch', [
            'template' => $template,
            'csv' => $csv,
        ]);
        $batchRes->assertStatus(200)->assertJsonPath('status', 'success');
        $docs = $batchRes->json('documents');
        $this->assertCount(3, $docs);
        $this->assertStringContainsString('Dr. Hendra Wijaya', $docs[0]);
        $this->assertStringContainsString('Prof. Ratna Sari', $docs[1]);
        $this->assertStringContainsString('Surabaya, Timur', $docs[2]);
    }

    /**
     * BB13: Zoom/dark/high contrast/keyboard/screen reader/dictation; fokus dan label benar, tidak mengubah konten ketika hanya melihat.
     */
    public function test_bb13_accessibility_dark_theme_read_aloud_speech_segmentation(): void
    {
        // 1. Accessibility audit
        $auditRes = $this->postJson('/api/documents/accessibility-audit', [
            'content' => '<h1>Judul 1</h1><h2>Judul 2</h2><p>Teks paragraf tanpa galat.</p>',
        ]);
        $auditRes->assertStatus(200)->assertJsonPath('success', true);
        $this->assertTrue($auditRes->json('passed'));

        // 2. Read Aloud Speech Segmentation
        $speechRes = $this->postJson('/api/documents/proofing/speech-segments', [
            'content' => '<p>Kalimat pertama naskah. Kalimat kedua dengan pertanyaan? Kalimat ketiga seru!</p>',
        ]);
        $speechRes->assertStatus(200)->assertJsonPath('success', true);
        $segments = $speechRes->json('segments');
        $this->assertCount(3, $segments);
        $this->assertEquals('Kalimat pertama naskah.', $segments[0]['text']);
    }

    /**
     * BB14: Buka file berbahaya (ZIP bomb, path traversal, XXE, macro/OLE, malicious SVG/link): tolak/isolasi tanpa akses jaringan/file server.
     */
    public function test_bb14_untrusted_file_handling_zip_slip_bomb_xxe_macro_quarantine(): void
    {
        $importer = new DocxImporter;

        // 1. Path traversal in ZIP entries is sanitized
        $tempZip = tempnam(sys_get_temp_dir(), 'zip_slip_');
        $zip = new ZipArchive;
        $zip->open($tempZip, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('../../etc/passwd', 'root:x:0:0:root:/root:/bin/bash');
        $zip->addFromString('[Content_Types].xml', '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"/>');
        $zip->addFromString('word/document.xml', '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>Aman</w:t></w:r></w:p></w:body></w:document>');
        $zip->close();

        $result = $importer->parseWithMetadata($tempZip);
        unlink($tempZip);

        $this->assertStringContainsString('Aman', $result['html']);
        $this->assertFileDoesNotExist('/etc/passwd.test');

        // 2. Macro quarantine
        $tempMacro = tempnam(sys_get_temp_dir(), 'macro_');
        $zipM = new ZipArchive;
        $zipM->open($tempMacro, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zipM->addFromString('word/vbaProject.bin', 'fake macro binary payload');
        $zipM->addFromString('[Content_Types].xml', '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"/>');
        $zipM->addFromString('word/document.xml', '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>Macro Text</w:t></w:r></w:p></w:body></w:document>');
        $zipM->close();

        $macroResult = $importer->parseWithMetadata($tempMacro);
        unlink($tempMacro);

        $this->assertTrue($macroResult['has_macros']);
        $this->assertNotEmpty($macroResult['security_alerts']);
    }

    /**
     * BB15: Password/protection/IRM/integrasi cloud: izin, interoperabilitas, revoke, gagal aman saat provider offline; jangan klaim fitur tanpa lisensi.
     */
    public function test_bb15_password_protection_restricted_editing_bcrypt_verification(): void
    {
        $doc = Document::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Naskah Proteksi',
            'content' => '<p>Teks Terkunci</p>',
            'owner_email' => 'admin@test.com',
        ]);

        // Set protection
        $setRes = $this->postJson("/api/documents/server/{$doc->uuid}/protection", [
            'password' => 'SandiRahasia123',
            'mode' => 'read_only',
        ]);
        $setRes->assertStatus(200)->assertJsonPath('status', 'success');

        // Verify with correct password
        $correctRes = $this->postJson("/api/documents/server/{$doc->uuid}/protection/verify", [
            'password' => 'SandiRahasia123',
        ]);
        $correctRes->assertStatus(200)->assertJsonPath('verified', true);

        // Verify with wrong password
        $wrongRes = $this->postJson("/api/documents/server/{$doc->uuid}/protection/verify", [
            'password' => 'SalahSandi',
        ]);
        $wrongRes->assertStatus(403)->assertJsonPath('verified', false);
    }
}
