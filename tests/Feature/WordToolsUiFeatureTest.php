<?php

namespace Tests\Feature;

use Tests\TestCase;

class WordToolsUiFeatureTest extends TestCase
{
    /**
     * Test that the editor view properly renders all advanced Microsoft Word suite UI elements.
     */
    public function test_editor_view_renders_advanced_word_suite_ui_elements(): void
    {
        $response = $this->get('/editor');

        $response->assertStatus(200);

        // Track Changes ribbon controls
        $response->assertSee('id="btn-toggle-track-changes"', false);
        $response->assertSee('id="track-changes-indicator"', false);
        $response->assertSee('id="btn-review-accept-all"', false);
        $response->assertSee('id="btn-review-reject-all"', false);
        $response->assertSee('id="btn-review-compare-modal"', false);

        // Accessibility audit ribbon control
        $response->assertSee('id="btn-review-accessibility"', false);

        // Server and Business ribbon controls in File tab
        $response->assertSee('data-action="open-version-history-modal"', false);
        $response->assertSee('data-action="open-protection-modal"', false);
        $response->assertSee('data-action="open-mail-merge-modal"', false);

        // Protection badge
        $response->assertSee('id="document-protection-badge"', false);

        // Modals
        $response->assertSee('id="modal-compare-documents"', false);
        $response->assertSee('id="modal-accessibility-audit"', false);
        $response->assertSee('id="modal-mail-merge"', false);
        $response->assertSee('id="modal-version-history"', false);
        $response->assertSee('id="modal-document-protection"', false);
    }

    /**
     * Test that mail merge presets endpoint returns expected presets for the UI.
     */
    public function test_mail_merge_presets_endpoint_accessible(): void
    {
        $response = $this->getJson('/api/documents/mail-merge/presets');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'presets' => [
                    'letter',
                    'envelope_dl',
                    'envelope_c5',
                    'labels_3x8',
                ],
            ]);
    }

    /**
     * Test accessibility audit endpoint integration with UI contract.
     */
    public function test_accessibility_audit_returns_actionable_report(): void
    {
        $htmlWithIssues = '<h1>Judul 1</h1><h3>Subjudul Lewati H2</h3><img src="test.jpg"><table class="w-full"><tr><td>Data</td></tr></table>';

        $response = $this->postJson('/api/documents/accessibility-audit', [
            'content' => $htmlWithIssues,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('passed', false);
    }
}
