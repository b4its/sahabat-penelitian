<?php

namespace Tests\Feature;

use App\Models\Document;
use Illuminate\Support\Str;
use Tests\TestCase;

class PanelBulkActionsAndProfileBlackBoxTest extends TestCase
{
    /**
     * Black-box BB01: Multiple choice UI elements render properly on documents page.
     */
    public function test_black_box_multiple_choice_ui_renders_on_documents_view(): void
    {
        $searchKey = 'BatchML'.uniqid();
        $doc1 = Document::create([
            'uuid' => (string) Str::uuid(),
            'title' => "Riset Pembelajaran Mesin Multi-Label {$searchKey}",
            'content_html' => '<p>Konten naskah 1.</p>',
        ]);

        $doc2 = Document::create([
            'uuid' => (string) Str::uuid(),
            'title' => "Studi Analisis Sentimen Pilkada {$searchKey}",
            'content_html' => '<p>Konten naskah 2.</p>',
        ]);

        $response = $this->get('/panel/documents?q='.$searchKey);
        $response->assertStatus(200);

        // 1. Checkbox "Select All" in table header
        $response->assertSee('id="select-all-documents"', false);
        $response->assertSee('Pilih semua naskah pada halaman ini', false);

        // 2. Individual row checkboxes
        $response->assertSee("value=\"{$doc1->uuid}\"", false);
        $response->assertSee("value=\"{$doc2->uuid}\"", false);
        $response->assertSee('class="doc-checkbox', false);

        // 3. Bulk actions bar container
        $response->assertSee('id="bulk-actions-bar"', false);
        $response->assertSee('id="bulk-selected-count"', false);
        $response->assertSee('id="btn-clear-selection"', false);
        $response->assertSee('Batalkan Pilihan', false);

        // 4. Two Bulk Options: Duplikat & Hapus
        $response->assertSee('id="btn-bulk-clone"', false);
        $response->assertSee('Duplikat Terpilih', false);
        $response->assertSee('id="btn-bulk-destroy"', false);
        $response->assertSee('Hapus Terpilih', false);

        // 5. Hidden form containers
        $response->assertSee('id="form-bulk-clone"', false);
        $response->assertSee('id="form-bulk-destroy"', false);
        $response->assertSee('id="form-single-destroy"', false);
    }

    /**
     * Black-box BB02: Custom Delete Alert Modal uses theme styling with red elements and avoids default JS confirm.
     */
    public function test_black_box_custom_delete_alert_modal_has_red_elements_and_no_native_confirm(): void
    {
        Document::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Naskah Uji Keamanan Format Dokumen',
            'content_html' => '<p>Konten naskah uji.</p>',
        ]);

        $response = $this->get('/panel/documents');
        $response->assertStatus(200);

        $html = $response->getContent();

        // 1. MUST NOT contain default browser confirm() in document delete triggers
        $this->assertStringNotContainsString('onsubmit="return confirm(', $html);

        // 2. Must contain custom themed delete modal with red accents ("ada unsur merahnya")
        $response->assertSee('id="modal-custom-delete-alert"', false);
        $response->assertSee('border-2 border-rose-300 dark:border-rose-900/70', false);
        $response->assertSee('bg-rose-100 dark:bg-rose-950/80 text-rose-600 dark:text-rose-400', false);
        $response->assertSee('bg-rose-50/80 dark:bg-rose-950/40 border border-rose-200', false);
        $response->assertSee('bg-rose-600 hover:bg-rose-700', false);
        $response->assertSee('id="btn-confirm-delete-action"', false);
        $response->assertSee('Konfirmasi Hapus Naskah', false);
        $response->assertSee('Tindakan ini permanen', false);

        // 3. Single row delete triggers openSingleDeleteModal
        $response->assertSee('openSingleDeleteModal', false);
    }

    /**
     * Black-box BB03: Panel header renders "Dr. Peneliti", profile dropdown, and centered edit profile modal.
     */
    public function test_black_box_panel_header_dropdown_and_edit_profile_modal(): void
    {
        $response = $this->get('/panel/documents');
        $response->assertStatus(200);

        // 1. Top right header displays researcher identity
        $response->assertSee('id="user-profile-menu-container"', false);
        $response->assertSee('id="user-profile-menu-button"', false);
        $response->assertSee('Dr. Peneliti', false);
        $response->assertSee('Penulis Utama', false);
        $response->assertSee('id="user-menu-chevron"', false);

        // 2. Profile Dropdown Menu with Edit Profile and Logout
        $response->assertSee('id="user-profile-dropdown"', false);
        $response->assertSee('id="btn-open-edit-profile"', false);
        $response->assertSee('onclick="openEditProfileModal()"', false);
        $response->assertSee('Edit Profil', false);
        $response->assertSee('id="btn-panel-logout"', false);
        $response->assertSee('Keluar (Logout)', false);
        $response->assertSee(route('panel.logout'), false);

        // 3. Centered Edit Profile Modal (popup ditengah)
        $response->assertSee('id="modal-edit-profile"', false);
        $response->assertSee('fixed inset-0 z-50 flex items-center justify-center', false);
        $response->assertSee('Sunting Profil Peneliti', false);
        $response->assertSee('Nama Lengkap &amp; Gelar Akademik:', false);
        $response->assertSee('id="profile-name"', false);
        $response->assertSee('id="profile-role"', false);
        $response->assertSee('id="profile-email"', false);
        $response->assertSee('id="profile-affiliation"', false);
        $response->assertSee(route('panel.profile.update'), false);
    }

    /**
     * Black-box BB04: End-to-end user scenario for Bulk Document Duplication (Duplikat).
     */
    public function test_black_box_e2e_bulk_clone_documents(): void
    {
        $doc1 = Document::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Naskah Riset Energi Baru Terbarukan '.uniqid(),
            'content_html' => '<p>Konten EBT 2026</p>',
        ]);
        $doc2 = Document::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Naskah Riset Material Semikonduktor '.uniqid(),
            'content_html' => '<p>Konten Semikonduktor</p>',
        ]);

        $response = $this->post('/panel/documents/bulk-clone', [
            'uuids' => [$doc1->uuid, $doc2->uuid],
        ]);

        $response->assertRedirect(route('panel.documents'));
        $response->assertSessionHas('success', '2 naskah penelitian berhasil diduplikasi.');

        $this->assertDatabaseHas('documents', [
            'title' => "(Salinan) {$doc1->title}",
        ]);
        $this->assertDatabaseHas('documents', [
            'title' => "(Salinan) {$doc2->title}",
        ]);
    }

    /**
     * Black-box BB05: End-to-end user scenario for Bulk Document Deletion (Hapus).
     */
    public function test_black_box_e2e_bulk_destroy_documents(): void
    {
        $doc1 = Document::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Naskah Uji Hapus Massal 1 '.uniqid(),
            'content_html' => '<p>Konten Hapus 1</p>',
        ]);
        $doc2 = Document::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Naskah Uji Hapus Massal 2 '.uniqid(),
            'content_html' => '<p>Konten Hapus 2</p>',
        ]);
        $docKeep = Document::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Naskah Yang Tidak Dihapus '.uniqid(),
            'content_html' => '<p>Konten Pertahankan</p>',
        ]);

        $response = $this->post('/panel/documents/bulk-destroy', [
            'uuids' => [$doc1->uuid, $doc2->uuid],
        ]);

        $response->assertRedirect(route('panel.documents'));
        $response->assertSessionHas('success', '2 naskah penelitian berhasil dihapus.');

        $this->assertDatabaseMissing('documents', ['uuid' => $doc1->uuid]);
        $this->assertDatabaseMissing('documents', ['uuid' => $doc2->uuid]);
        $this->assertDatabaseHas('documents', ['uuid' => $docKeep->uuid]);
    }

    /**
     * Black-box BB06: End-to-end user scenario for Profile Update and Header Reflection.
     */
    public function test_black_box_e2e_profile_update_and_header_reflection(): void
    {
        $updateResponse = $this->post('/panel/profile', [
            'name' => 'Prof. Dr. Ir. Budi Santoso, M.Sc.',
            'role' => 'Guru Besar & Peneliti Utama',
            'email' => 'budi.santoso@univ.ac.id',
            'affiliation' => 'Pusat Unggulan Riset AI & Robotika',
        ]);

        $updateResponse->assertSessionHas('success', 'Profil peneliti berhasil diperbarui.');

        // Re-visit panel and verify updated name is rendered in header and dropdown
        $viewResponse = $this->withSession([
            'panel_user_name' => 'Prof. Dr. Ir. Budi Santoso, M.Sc.',
            'panel_user_role' => 'Guru Besar & Peneliti Utama',
            'panel_user_email' => 'budi.santoso@univ.ac.id',
        ])->get('/panel/documents');

        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('Prof. Dr. Ir. Budi Santoso, M.Sc.', false);
        $viewResponse->assertSee('Guru Besar &amp; Peneliti Utama', false);
        $viewResponse->assertSee('PR', false); // Initial of Prof.
    }

    /**
     * Black-box BB07: End-to-end user scenario for Panel Logout.
     */
    public function test_black_box_e2e_panel_logout_clears_session_and_redirects(): void
    {
        $response = $this->withSession([
            'panel_user_name' => 'Dr. Peneliti Aktif',
            'panel_user_email' => 'peneliti@aktif.id',
        ])->post('/panel/logout');

        $response->assertRedirect('/');
        $response->assertSessionMissing('panel_user_name');
        $response->assertSessionMissing('panel_user_email');
        $response->assertSessionHas('success', 'Anda telah berhasil keluar dari Panel Riset.');
    }
}
