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
}
