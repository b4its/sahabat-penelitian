<?php

namespace Tests\Feature;

use App\Models\Document;
use Tests\TestCase;

class PanelDashboardFeatureTest extends TestCase
{
    /**
     * Test panel dashboard route loads successfully and renders layout.
     */
    public function test_panel_dashboard_view_loads_successfully_at_routes(): void
    {
        $responseHome = $this->get('/panel');
        $responseHome->assertStatus(200);

        $responseDashboard = $this->get('/panel/dashboard');
        $responseDashboard->assertStatus(200);
    }

    /**
     * Test panel contains core brand, title and academic header.
     */
    public function test_panel_renders_brand_identity_and_academic_header(): void
    {
        $response = $this->get('/panel/dashboard');
        $response->assertStatus(200);

        $response->assertSee('Sahabat', false);
        $response->assertSee('Penelitian', false);
        $response->assertSee('Panel Riset', false);
        $response->assertSee('Selamat Datang di Panel Sahabat Penelitian', false);
    }

    /**
     * Test panel layout has collapsible and responsive sidebar elements.
     */
    public function test_panel_has_collapsible_and_responsive_sidebar(): void
    {
        $response = $this->get('/panel/dashboard');
        $response->assertStatus(200);

        // Sidebar container and elements
        $response->assertSee('id="panel-sidebar"', false);
        $response->assertSee('sidebar-nav-item', false);
        $response->assertSee('sidebar-text-label', false);

        // Desktop collapse toggle button
        $response->assertSee('id="btn-toggle-desktop-sidebar"', false);
        $response->assertSee('id="sidebar-toggle-icon"', false);
        $response->assertSee('Kecilkan Menu', false);

        // Mobile drawer controls and backdrop
        $response->assertSee('id="btn-open-mobile-sidebar"', false);
        $response->assertSee('id="btn-close-mobile-sidebar"', false);
        $response->assertSee('id="sidebar-mobile-backdrop"', false);
    }

    /**
     * Test panel layout has dark and light theme switch with persistence.
     */
    public function test_panel_has_dark_and_light_theme_switch(): void
    {
        $response = $this->get('/panel/dashboard');
        $response->assertStatus(200);

        $response->assertSee('id="theme-toggle-btn"', false);
        $response->assertSee('id="theme-icon-sun"', false);
        $response->assertSee('id="theme-icon-moon"', false);
        $response->assertSee('sahabat_theme', false);
        $response->assertSee('localStorage', false);
    }

    /**
     * Test panel uses primary blue color palette and non-colliding responsive structure.
     */
    public function test_panel_uses_blue_theme_palette_and_non_colliding_grid(): void
    {
        $response = $this->get('/panel/dashboard');
        $response->assertStatus(200);

        // Blue accent classes
        $response->assertSee('bg-blue-600', false);
        $response->assertSee('text-blue-600', false);
        $response->assertSee('border-blue-', false);

        // Responsive non-colliding layout
        $response->assertSee('overflow-hidden', false);
        $response->assertSee('max-w-7xl', false);
        $response->assertSee('flex-1 flex flex-col min-w-0', false);
    }

    /**
     * Test panel dashboard renders KPIs and recent document list.
     */
    public function test_panel_dashboard_renders_metrics_and_recent_documents(): void
    {
        Document::create([
            'title' => 'Riset Bioinformatika Terapan 2026',
            'content_html' => '<p>Konten riset genetika.</p>',
            'restricted_mode' => 'none',
        ]);

        $response = $this->get('/panel/dashboard');
        $response->assertStatus(200);

        $response->assertSee('Total Naskah', false);
        $response->assertSee('Snapshot Versi', false);
        $response->assertSee('Proteksi Dokumen', false);
        $response->assertSee('Integritas Format', false);
        $response->assertSee('Riset Bioinformatika Terapan 2026', false);
    }

    /**
     * Test panel dashboard paginates recent documents when total exceeds 5.
     */
    public function test_panel_dashboard_paginates_recent_documents_when_count_exceeds_five(): void
    {
        for ($i = 1; $i <= 7; $i++) {
            Document::create([
                'title' => "Draf Laporan Publikasi Seri {$i}",
                'content_html' => "<p>Isi laporan {$i}.</p>",
                'restricted_mode' => 'none',
            ]);
        }

        $responsePage1 = $this->get('/panel/dashboard');
        $responsePage1->assertStatus(200);
        $responsePage1->assertSee('recent_page=2', false);
        $responsePage1->assertSee('Halaman', false);

        $responsePage2 = $this->get('/panel/dashboard?recent_page=2');
        $responsePage2->assertStatus(200);
        $responsePage2->assertSee('Halaman', false);
    }

    /**
     * Test panel dashboard ensures audit logs card does not leak document pagination and prevents visual collision.
     */
    public function test_panel_dashboard_prevents_pagination_collision_in_audit_card(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            Document::create([
                'title' => "Dokumen Uji Tabrakan {$i}",
                'content_html' => "<p>Isi dokumen {$i}.</p>",
                'restricted_mode' => 'none',
            ]);
        }

        $response = $this->get('/panel/dashboard');
        $response->assertStatus(200);

        // Naskah Terbaru card must have pagination container with responsive overflow containment
        $response->assertSee('overflow-x-auto max-w-full', false);

        // Audit Trail card must NOT have duplicate document pagination links
        $html = $response->getContent();
        $auditSectionStart = strpos($html, 'Aktivitas Riset Terkini');
        $this->assertNotFalse($auditSectionStart);
        $auditSection = substr($html, $auditSectionStart);

        // Verify the audit section does not contain recent_page or duplicate pagination
        $this->assertStringNotContainsString('recent_page=2', $auditSection);
    }

    /**
     * Test responsive layout classes across panel components prevent container clipping.
     */
    public function test_panel_responsive_classes_prevent_content_collision(): void
    {
        $response = $this->get('/panel/dashboard');
        $response->assertStatus(200);

        // Grid columns have min-w-0 to prevent flex/grid blowout
        $response->assertSee('lg:col-span-8 space-y-6 min-w-0', false);
        $response->assertSee('lg:col-span-4 space-y-6 min-w-0', false);

        // Modal has max-h-[90vh] and overflow-y-auto for small/landscape devices
        $response->assertSee('max-h-[90vh] overflow-y-auto', false);
    }
}
