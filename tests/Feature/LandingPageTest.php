<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingPageTest extends TestCase
{
    /**
     * Test landing page loads successfully at root and /landing routes.
     */
    public function test_landing_page_loads_successfully_at_root_and_landing_routes(): void
    {
        $responseRoot = $this->get('/');
        $responseRoot->assertStatus(200);

        $responseLanding = $this->get('/landing');
        $responseLanding->assertStatus(200);
    }

    /**
     * Test landing page contains the official system title "Sahabat Penelitian".
     */
    public function test_landing_page_contains_system_title_sahabat_penelitian(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);

        $response->assertSee('Sahabat', false);
        $response->assertSee('Penelitian', false);
        $response->assertSee('Platform Riset & Naskah Ilmiah', false);
        $response->assertSee('Meneliti Lebih Dalam, Menulis Lebih Presisi', false);
    }

    /**
     * Test landing page contains dark and light theme switch controls.
     */
    public function test_landing_page_has_dark_and_light_theme_switch(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);

        // Theme toggle button and SVG icons
        $response->assertSee('id="theme-toggle"', false);
        $response->assertSee('id="theme-toggle-light-icon"', false);
        $response->assertSee('id="theme-toggle-dark-icon"', false);
        $response->assertSee('sahabat_theme', false);
        $response->assertSee('localStorage', false);
    }

    /**
     * Test landing page includes custom interactive animations.
     */
    public function test_landing_page_has_interactive_animations(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);

        // CSS Animation classes
        $response->assertSee('animate-float-slow', false);
        $response->assertSee('animate-pulse-glow', false);
        $response->assertSee('animate-float-reverse', false);
        $response->assertSee('@keyframes float-slow', false);
    }

    /**
     * Test landing page uses the primary blue color palette.
     */
    public function test_landing_page_uses_blue_base_color_palette(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);

        // Tailwind blue theme indicators
        $response->assertSee('bg-blue-600', false);
        $response->assertSee('text-blue-600', false);
        $response->assertSee('text-gradient-blue', false);
        $response->assertSee('border-blue-', false);
    }

    /**
     * Test landing page interactive components and links to editor.
     */
    public function test_landing_page_has_interactive_simulator_calculator_faq_and_editor_cta(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);

        // 1. Live Simulator
        $response->assertSee('id="demo-studio"', false);
        $response->assertSee('citation-btn', false);
        $response->assertSee('id="demo-inline-citation"', false);
        $response->assertSee('id="btn-toggle-demo-track"', false);

        // 2. Publication Readiness Calculator
        $response->assertSee('id="calculator"', false);
        $response->assertSee('readiness-check', false);
        $response->assertSee('id="readiness-percentage"', false);

        // 3. FAQ Accordion
        $response->assertSee('id="faq"', false);
        $response->assertSee('faq-toggle', false);
        $response->assertSee('faq-content', false);

        // 4. CTA to Editor
        $response->assertSee(url('/editor'), false);
    }

    /**
     * Test landing page uses non-colliding responsive grid and header structures across screen sizes.
     */
    public function test_landing_page_responsive_structures_prevent_collision(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);

        // Responsive workflow grid adapts to 1, 2, and 5 columns depending on device width
        $response->assertSee('grid-cols-1 sm:grid-cols-2 lg:grid-cols-5', false);

        // Header has responsive label hiding to prevent collision on narrow mobile screens (320px-375px)
        $response->assertSee('hidden sm:inline', false);
        $response->assertSee('inline sm:hidden', false);
        $response->assertSee('min-w-0', false);
    }
}
