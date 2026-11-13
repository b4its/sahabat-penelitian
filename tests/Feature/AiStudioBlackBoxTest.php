<?php

namespace Tests\Feature;

use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use App\Models\AiProvider;
use Illuminate\Support\Str;
use Tests\TestCase;

class AiStudioBlackBoxTest extends TestCase
{
    /**
     * Black-box BB01: Panel layout sidebar renders AI Studio navigation links and badges.
     */
    public function test_black_box_sidebar_renders_ai_studio_navigation(): void
    {
        $response = $this->get('/panel/dashboard');
        $response->assertStatus(200);

        // Sidebar should contain AI Studio group and items
        $response->assertSee('AI Studio', false);
        $response->assertSee('Koneksi Provider', false);
        $response->assertSee('Chat Studio AI', false);
        $response->assertSee(route('panel.ai-studio.index', ['tab' => 'connections']), false);
        $response->assertSee(route('panel.ai-studio.index', ['tab' => 'chat']), false);
    }

    /**
     * Black-box BB02: AI Studio routes load successfully and redirect subroutes properly.
     */
    public function test_black_box_ai_studio_routes_load_and_redirect(): void
    {
        // Direct index
        $resIndex = $this->get('/panel/ai-studio');
        $resIndex->assertStatus(200);
        $resIndex->assertSee('AI Studio');
        $resIndex->assertSee('Koneksi Provider');
        $resIndex->assertSee('Chat AI Studio');

        // Connections redirect
        $resConn = $this->get('/panel/ai-studio/connections');
        $resConn->assertRedirect('/panel/ai-studio?tab=connections');

        // Chat redirect
        $resChat = $this->get('/panel/ai-studio/chat');
        $resChat->assertRedirect('/panel/ai-studio?tab=chat');
    }

    /**
     * Black-box BB03: Connection tab renders table, metric cards, and modal triggers.
     */
    public function test_black_box_connection_tab_elements_render(): void
    {
        $response = $this->get('/panel/ai-studio?tab=connections');
        $response->assertStatus(200);

        // Metrics
        $response->assertSee('Total Provider');
        $response->assertSee('Provider Aktif');
        $response->assertSee('Model Terhubung');

        // Table
        $response->assertSee('id="providers-table-body"', false);
        $response->assertSee('id="btn-add-provider"', false);
        $response->assertSee('Tambah Provider Baru', false);

        // Modal elements
        $response->assertSee('id="modal-provider"', false);
        $response->assertSee('id="form-provider"', false);
        $response->assertSee('id="input-provider-driver"', false);
        $response->assertSee('id="input-provider-name"', false);
        $response->assertSee('id="input-provider-models"', false);
    }

    /**
     * Black-box BB04: End-to-end add AI Provider via POST endpoint.
     */
    public function test_black_box_e2e_add_ai_provider(): void
    {
        $uniqueName = 'DeepSeek LLM Studio '.uniqid();
        $payload = [
            'name' => $uniqueName,
            'provider' => 'custom',
            'base_url' => 'https://api.deepseek.com/v1',
            'api_key' => 'sk-test-deepseek-key-9876',
            'models' => 'deepseek-chat, deepseek-reasoner',
            'default_model' => 'deepseek-chat',
            'is_active' => true,
        ];

        // 1. Via JSON request
        $jsonResponse = $this->postJson('/panel/ai-studio/providers', $payload);
        $jsonResponse->assertStatus(200);
        $jsonResponse->assertJson(['success' => true]);

        // Verify database persistence
        $this->assertDatabaseHas('ai_providers', [
            'name' => $uniqueName,
            'provider' => 'custom',
            'default_model' => 'deepseek-chat',
            'is_active' => 1,
        ]);

        $created = AiProvider::where('name', $uniqueName)->first();
        $this->assertEquals(['deepseek-chat', 'deepseek-reasoner'], $created->models);

        // 2. Via standard web form redirect
        $uniqueName2 = 'Groq Ultrafast '.uniqid();
        $webResponse = $this->post('/panel/ai-studio/providers', [
            'name' => $uniqueName2,
            'provider' => 'groq',
            'base_url' => 'https://api.groq.com/openai/v1',
            'api_key' => 'gsk-test-1234',
            'models' => 'llama-3.3-70b-versatile',
            'is_active' => true,
        ]);
        $webResponse->assertRedirect('/panel/ai-studio?tab=connections');
        $this->assertDatabaseHas('ai_providers', ['name' => $uniqueName2]);
    }

    /**
     * Black-box BB05: End-to-end update AI Provider.
     */
    public function test_black_box_e2e_update_ai_provider(): void
    {
        $provider = AiProvider::create([
            'name' => 'Original Gemini',
            'provider' => 'gemini',
            'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
            'api_key' => 'AIzaSy-original-key',
            'models' => ['gemini-1.5-flash'],
            'default_model' => 'gemini-1.5-flash',
            'is_active' => true,
        ]);

        $updatePayload = [
            'name' => 'Updated Gemini 2.0 Pro',
            'provider' => 'gemini',
            'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
            'api_key' => '', // Leave blank to retain existing key
            'models' => 'gemini-2.0-flash, gemini-1.5-pro',
            'default_model' => 'gemini-2.0-flash',
            'is_active' => true,
        ];

        $response = $this->postJson("/panel/ai-studio/providers/{$provider->uuid}/update", $updatePayload);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $provider->refresh();
        $this->assertEquals('Updated Gemini 2.0 Pro', $provider->name);
        $this->assertEquals(['gemini-2.0-flash', 'gemini-1.5-pro'], $provider->models);
        $this->assertEquals('gemini-2.0-flash', $provider->default_model);
        // Retains original key
        $this->assertEquals('AIzaSy-original-key', $provider->api_key);
    }

    /**
     * Black-box BB06: End-to-end toggle AI Provider active/inactive status.
     */
    public function test_black_box_e2e_toggle_ai_provider_status(): void
    {
        $provider = AiProvider::create([
            'name' => 'Toggleable Provider',
            'provider' => 'ollama',
            'models' => ['llama3.2'],
            'is_active' => true,
        ]);

        $response = $this->postJson("/panel/ai-studio/providers/{$provider->uuid}/toggle");
        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'is_active' => false]);

        $provider->refresh();
        $this->assertFalse($provider->is_active);

        // Toggle back to active
        $response2 = $this->postJson("/panel/ai-studio/providers/{$provider->uuid}/toggle");
        $response2->assertStatus(200);
        $response2->assertJson(['success' => true, 'is_active' => true]);

        $provider->refresh();
        $this->assertTrue($provider->is_active);
    }

    /**
     * Black-box BB07: End-to-end test AI Provider connection endpoint.
     */
    public function test_black_box_e2e_test_connection(): void
    {
        $provider = AiProvider::create([
            'name' => 'Testable Provider',
            'provider' => 'openai',
            'base_url' => 'https://api.openai.com/v1',
            'api_key' => 'sk-test-sample-key',
            'models' => ['gpt-4o'],
            'status' => 'ready',
        ]);

        $response = $this->postJson("/panel/ai-studio/providers/{$provider->uuid}/test");
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'latency_ms',
        ]);
        $this->assertTrue($response->json('success'));
    }

    /**
     * Black-box BB08: End-to-end delete AI Provider.
     */
    public function test_black_box_e2e_delete_ai_provider(): void
    {
        $provider = AiProvider::create([
            'name' => 'Deletable Provider',
            'provider' => 'custom',
            'models' => ['custom-1'],
        ]);

        $uuid = $provider->uuid;
        $response = $this->deleteJson("/panel/ai-studio/providers/{$uuid}");
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseMissing('ai_providers', ['uuid' => $uuid]);
    }

    /**
     * Black-box BB09: GET /panel/ai-studio/providers/list returns active providers with models.
     */
    public function test_black_box_get_active_providers_list_json(): void
    {
        $provider = AiProvider::create([
            'name' => 'Active List Provider',
            'provider' => 'openai',
            'models' => ['gpt-4o', 'gpt-4o-mini'],
            'default_model' => 'gpt-4o',
            'is_active' => true,
        ]);

        $response = $this->getJson('/panel/ai-studio/providers/list');
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $data = $response->json('providers');
        $this->assertNotEmpty($data);

        $found = collect($data)->firstWhere('uuid', $provider->uuid);
        $this->assertNotNull($found);
        $this->assertEquals(['gpt-4o', 'gpt-4o-mini'], $found['models']);
        $this->assertEquals('gpt-4o', $found['default_model']);
    }

    /**
     * Black-box BB10: Chat tab UI renders provider select, model select, and interactive input.
     */
    public function test_black_box_chat_tab_ui_renders_controls(): void
    {
        $response = $this->get('/panel/ai-studio?tab=chat');
        $response->assertStatus(200);

        // Dropdowns & Controls
        $response->assertSee('id="chat-provider-select"', false);
        $response->assertSee('id="chat-model-select"', false);
        $response->assertSee('id="chat-system-preset"', false);
        $response->assertSee('id="chat-messages-container"', false);
        $response->assertSee('id="chat-user-input"', false);
        $response->assertSee('id="btn-chat-send"', false);
        $response->assertSee('Sesi Baru', false);
    }

    /**
     * Black-box BB11: End-to-end chat execution sends prompt to selected provider & model.
     */
    public function test_black_box_e2e_send_chat_message_success(): void
    {
        $provider = AiProvider::create([
            'name' => 'Chat Production Provider',
            'provider' => 'openai',
            'api_key' => 'sk-test-demo',
            'models' => ['gpt-4o', 'gpt-4o-mini'],
            'default_model' => 'gpt-4o',
            'is_active' => true,
        ]);

        $userPrompt = 'Bagaimana merancang abstrak skripsi yang memenuhi standar IMRaD?';
        $payload = [
            'provider_uuid' => $provider->uuid,
            'model' => 'gpt-4o',
            'message' => $userPrompt,
            'system_prompt' => 'Anda adalah asisten akademik.',
        ];

        $response = $this->postJson('/panel/ai-studio/chat/send', $payload);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $responseData = $response->json();
        $this->assertNotEmpty($responseData['session_uuid']);
        $this->assertEquals('user', $responseData['user_message']['role']);
        $this->assertEquals($userPrompt, $responseData['user_message']['content']);

        $this->assertEquals('assistant', $responseData['assistant_message']['role']);
        $this->assertNotEmpty($responseData['assistant_message']['content']);
        $this->assertEquals('gpt-4o', $responseData['assistant_message']['model_used']);
        $this->assertEquals($provider->name, $responseData['assistant_message']['provider_name']);

        // Check database persistence
        $this->assertDatabaseHas('ai_chat_messages', [
            'role' => 'user',
            'content' => $userPrompt,
            'model_used' => 'gpt-4o',
        ]);

        $this->assertDatabaseHas('ai_chat_messages', [
            'role' => 'assistant',
            'model_used' => 'gpt-4o',
            'provider_name' => $provider->name,
        ]);
    }

    /**
     * Black-box BB12: Sending chat message with inactive provider fails with 422.
     */
    public function test_black_box_chat_with_inactive_provider_rejected(): void
    {
        $inactiveProvider = AiProvider::create([
            'name' => 'Disabled Provider',
            'provider' => 'openai',
            'models' => ['gpt-4o'],
            'is_active' => false,
        ]);

        $response = $this->postJson('/panel/ai-studio/chat/send', [
            'provider_uuid' => $inactiveProvider->uuid,
            'model' => 'gpt-4o',
            'message' => 'Tes pesan',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('non-aktif', $response->json('message'));
    }

    /**
     * Black-box BB13: Sending chat message with invalid/missing provider returns 404.
     */
    public function test_black_box_chat_with_missing_provider_returns_404(): void
    {
        $response = $this->postJson('/panel/ai-studio/chat/send', [
            'provider_uuid' => (string) Str::uuid(),
            'model' => 'gpt-4o',
            'message' => 'Pesan ke provider tidak ada',
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
        ]);
    }

    /**
     * Black-box BB14: End-to-end clear/reset chat session initializes fresh session.
     */
    public function test_black_box_e2e_clear_chat_session(): void
    {
        $session = AiChatSession::create([
            'title' => 'Sesi Obrolan Lama',
            'model' => 'gpt-4o',
        ]);

        AiChatMessage::create([
            'session_id' => $session->id,
            'role' => 'user',
            'content' => 'Pesan lama',
        ]);

        $response = $this->postJson('/panel/ai-studio/chat/clear', [
            'session_uuid' => $session->uuid,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertNotEmpty($response->json('session_uuid'));
        $this->assertNotEquals($session->uuid, $response->json('session_uuid'));

        // Old session removed
        $this->assertDatabaseMissing('ai_chat_sessions', ['id' => $session->id]);
    }

    /**
     * Black-box BB15: Form validation prevents invalid provider creation when required fields are missing.
     */
    public function test_black_box_provider_validation_rules(): void
    {
        // Missing name and provider driver
        $response = $this->postJson('/panel/ai-studio/providers', [
            'base_url' => 'https://api.openai.com/v1',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'provider']);
    }

    /**
     * Black-box BB16: Adding a provider without models saves empty models and renders "model belum didaftarkan" default badge.
     */
    public function test_black_box_add_provider_without_models_renders_model_belum_didaftarkan(): void
    {
        $uniqueName = 'Provider Tanpa Model '.uniqid();
        $response = $this->postJson('/panel/ai-studio/providers', [
            'name' => $uniqueName,
            'provider' => 'gemini',
            'api_key' => 'AIzaSy-test-key-12345',
            // models is deliberately omitted on creation
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Assert database record has empty models and null default_model
        $provider = AiProvider::where('name', $uniqueName)->first();
        $this->assertNotNull($provider);
        $this->assertEmpty($provider->models);
        $this->assertNull($provider->default_model);

        // Assert connections table renders "model belum didaftarkan"
        $tableResponse = $this->get('/panel/ai-studio?tab=connections');
        $tableResponse->assertStatus(200);
        $tableResponse->assertSee($uniqueName);
        $tableResponse->assertSee('model belum didaftarkan', false);
        $tableResponse->assertSee('empty-models-badge', false);
    }

    /**
     * Black-box BB17: Provider modal contains searchable multiple-choice models container hidden by default.
     */
    public function test_black_box_modal_structure_hides_models_by_default(): void
    {
        $response = $this->get('/panel/ai-studio?tab=connections');
        $response->assertStatus(200);

        // Container is hidden by default for "Tambah Provider Baru"
        $response->assertSee('id="container-provider-models" class="space-y-3 pt-3 border-t border-blue-100 dark:border-blue-900/40 hidden"', false);

        // Search input for multiple choice models
        $response->assertSee('id="model-search-input"', false);
        $response->assertSee('placeholder="Cari model dalam koneksi ini..."', false);

        // Scrollable multiple choice container
        $response->assertSee('id="model-multiselect-container"', false);

        // Selected models counter badge & chips container
        $response->assertSee('id="selected-models-count-badge"', false);
        $response->assertSee('id="container-selected-models-chips"', false);

        // Dynamic default model dropdown
        $response->assertSee('id="input-provider-default-model"', false);
    }

    /**
     * Black-box BB18: End-to-end update provider with multiple choice models selection.
     */
    public function test_black_box_e2e_edit_provider_models_via_multiple_choice(): void
    {
        $provider = AiProvider::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Provider Edit Test '.uniqid(),
            'provider' => 'openai',
            'base_url' => 'https://api.openai.com/v1',
            'api_key' => 'sk-test-secret',
            'models' => [],
            'default_model' => null,
            'is_active' => true,
            'status' => 'ready',
        ]);

        // Initially renders "model belum didaftarkan"
        $initResponse = $this->get('/panel/ai-studio?tab=connections');
        $initResponse->assertSee($provider->name);
        $initResponse->assertSee('model belum didaftarkan', false);

        // User edits provider and selects multiple models (e.g. gpt-4o, gpt-4o-mini, o3-mini)
        $updateResponse = $this->postJson("/panel/ai-studio/providers/{$provider->uuid}/update", [
            'name' => $provider->name.' Updated',
            'provider' => 'openai',
            'models' => ['gpt-4o', 'gpt-4o-mini', 'o3-mini'],
            'default_model' => 'gpt-4o',
        ]);

        $updateResponse->assertStatus(200);
        $updateResponse->assertJson(['success' => true]);

        // Provider now has models stored and default model selected
        $provider->refresh();
        $this->assertEquals(['gpt-4o', 'gpt-4o-mini', 'o3-mini'], $provider->models);
        $this->assertEquals('gpt-4o', $provider->default_model);

        // Table now displays selected model badges instead of "model belum didaftarkan"
        $afterResponse = $this->get('/panel/ai-studio?tab=connections');
        $afterResponse->assertSee('gpt-4o');
        $afterResponse->assertSee('gpt-4o-mini');
        $afterResponse->assertSee('o3-mini');
    }

    /**
     * Black-box BB16: Multiple choice UI renders checkboxes and bulk actions bar on connections tab.
     */
    public function test_black_box_multiple_choice_ui_renders_on_providers_table(): void
    {
        $unique = 'BulkProv_'.uniqid();
        $p1 = AiProvider::create([
            'name' => "Provider One {$unique}",
            'provider' => 'openai',
            'models' => ['gpt-4o'],
            'is_active' => true,
        ]);
        $p2 = AiProvider::create([
            'name' => "Provider Two {$unique}",
            'provider' => 'gemini',
            'models' => ['gemini-2.0-flash'],
            'is_active' => true,
        ]);

        $response = $this->get('/panel/ai-studio?tab=connections');
        $response->assertStatus(200);

        // 1. Select All Checkbox in table header
        $response->assertSee('id="select-all-providers"', false);
        $response->assertSee('Pilih semua provider pada halaman ini', false);

        // 2. Individual row checkboxes
        $response->assertSee("value=\"{$p1->uuid}\"", false);
        $response->assertSee("value=\"{$p2->uuid}\"", false);
        $response->assertSee('class="provider-checkbox', false);

        // 3. Bulk actions bar container
        $response->assertSee('id="bulk-actions-bar"', false);
        $response->assertSee('id="bulk-selected-count"', false);
        $response->assertSee('id="btn-clear-selection"', false);
        $response->assertSee('Batalkan Pilihan', false);

        // 4. Bulk Delete Button with red styling
        $response->assertSee('id="btn-bulk-destroy"', false);
        $response->assertSee('Hapus Terpilih', false);

        // 5. Hidden form containers
        $response->assertSee('id="form-bulk-destroy"', false);
        $response->assertSee('id="form-single-destroy"', false);
    }

    /**
     * Black-box BB17: Custom delete confirmation modal uses theme styling matching documents modal.
     */
    public function test_black_box_custom_delete_modal_on_provider_page(): void
    {
        $response = $this->get('/panel/ai-studio?tab=connections');
        $response->assertStatus(200);

        // Modal container with rose borders and shadows
        $response->assertSee('id="modal-custom-delete-alert"', false);
        $response->assertSee('border-2 border-rose-300 dark:border-rose-900/70', false);
        $response->assertSee('bg-rose-100 dark:bg-rose-950/80 text-rose-600 dark:text-rose-400', false);
        $response->assertSee('bg-rose-50/80 dark:bg-rose-950/40 border border-rose-200', false);
        $response->assertSee('bg-rose-600 hover:bg-rose-700', false);
        $response->assertSee('id="btn-confirm-delete-action"', false);
        $response->assertSee('Konfirmasi Hapus Provider AI', false);
        $response->assertSee('Tindakan ini permanen', false);

        // Single delete trigger calls openSingleDeleteModal
        $response->assertSee('openSingleDeleteModal', false);
    }

    /**
     * Black-box BB18: End-to-end bulk delete multiple providers via POST endpoint.
     */
    public function test_black_box_e2e_bulk_destroy_providers(): void
    {
        $tag = 'BulkDelete_'.uniqid();
        $p1 = AiProvider::create([
            'name' => "Batch AI 1 {$tag}",
            'provider' => 'openai',
            'models' => ['model-1'],
        ]);
        $p2 = AiProvider::create([
            'name' => "Batch AI 2 {$tag}",
            'provider' => 'gemini',
            'models' => ['model-2'],
        ]);
        $p3 = AiProvider::create([
            'name' => "Keep AI {$tag}",
            'provider' => 'anthropic',
            'models' => ['model-3'],
        ]);

        // 1. JSON Request Bulk Destroy
        $jsonResponse = $this->postJson('/panel/ai-studio/providers/bulk-destroy', [
            'uuids' => [$p1->uuid, $p2->uuid],
        ]);

        $jsonResponse->assertStatus(200);
        $jsonResponse->assertJson([
            'success' => true,
            'count' => 2,
        ]);

        $this->assertDatabaseMissing('ai_providers', ['id' => $p1->id]);
        $this->assertDatabaseMissing('ai_providers', ['id' => $p2->id]);
        $this->assertDatabaseHas('ai_providers', ['id' => $p3->id]);

        // 2. Form redirect mode with flash message
        $p4 = AiProvider::create([
            'name' => "Batch AI 4 {$tag}",
            'provider' => 'groq',
            'models' => ['model-4'],
        ]);

        $webResponse = $this->post('/panel/ai-studio/providers/bulk-destroy', [
            'uuids' => [$p4->uuid],
        ]);

        $webResponse->assertRedirect('/panel/ai-studio?tab=connections');
        $webResponse->assertSessionHas('status', '1 provider AI berhasil dihapus.');
        $this->assertDatabaseMissing('ai_providers', ['id' => $p4->id]);

        // 3. Validation failure when empty
        $invalidResponse = $this->postJson('/panel/ai-studio/providers/bulk-destroy', [
            'uuids' => [],
        ]);
        $invalidResponse->assertStatus(422);
    }

    /**
     * Black-box BB19: Chat tab renders searchable provider select with search input inside select dropdown.
     */
    public function test_black_box_searchable_provider_select_in_chat(): void
    {
        $provider = AiProvider::create([
            'name' => 'Searchable LLM '.uniqid(),
            'provider' => 'openai',
            'models' => ['gpt-4o'],
            'is_active' => true,
        ]);

        $response = $this->get('/panel/ai-studio?tab=chat');
        $response->assertStatus(200);

        // 1. Searchable wrapper and trigger
        $response->assertSee('id="chat-provider-searchable-wrapper"', false);
        $response->assertSee('id="chat-provider-search-trigger"', false);
        $response->assertSee('id="chat-provider-dropdown"', false);

        // 2. Search input inside select dropdown
        $response->assertSee('id="chat-provider-search-input"', false);
        $response->assertSee('placeholder="Cari AI provider..."', false);
        $response->assertSee('oninput="filterProviderOptions()"', false);

        // 3. Options list & items
        $response->assertSee('id="chat-provider-options-list"', false);
        $response->assertSee('class="provider-option-item', false);
        $response->assertSee($provider->name, false);
        $response->assertSee('id="chat-provider-empty-result"', false);
        $response->assertSee('Provider tidak ditemukan', false);

        // 4. Native select retained for full programmatic synchronization
        $response->assertSee('id="chat-provider-select"', false);
    }

    /**
     * Black-box BB20: Chat messages and AI responses are formatted and rendered as Markdown.
     */
    public function test_black_box_ai_studio_chat_response_renders_markdown(): void
    {
        $provider = AiProvider::create([
            'name' => 'Markdown Output Provider',
            'provider' => 'openai',
            'api_key' => 'sk-test-demo',
            'models' => ['gpt-4o'],
            'default_model' => 'gpt-4o',
            'is_active' => true,
        ]);

        $session = AiChatSession::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Sesi Markdown Test',
            'ai_provider_id' => $provider->id,
            'model' => 'gpt-4o',
        ]);

        // Pre-create an assistant message containing rich markdown
        $markdownContent = "## Telaah Metodologi Ilmiah\n\n"
            ."Berikut rekomendasi rancangan:\n"
            ."- **Uji Validitas**: Menggunakan Alpha Cronbach (> 0.70)\n"
            ."- *Teknik Sampling*: Stratified random sampling\n\n"
            ."> Format kutipan wajib mengacu standar APA 7th Edition.\n\n"
            .'Gunakan kode referensi `REF-2026` untuk sitasi otomatis.';

        AiChatMessage::create([
            'session_id' => $session->id,
            'role' => 'assistant',
            'content' => $markdownContent,
            'provider_name' => $provider->name,
            'model_used' => 'gpt-4o',
        ]);

        // 1. Blade View renders converted Markdown HTML
        $viewResponse = $this->get('/panel/ai-studio?tab=chat&session='.$session->uuid);
        $viewResponse->assertStatus(200);

        // Must contain ai-markdown-content container
        $viewResponse->assertSee('class="prose prose-xs dark:prose-invert max-w-none leading-relaxed ai-markdown-content"', false);
        // Heading rendered as <h2>
        $viewResponse->assertSee('<h2>Telaah Metodologi Ilmiah</h2>', false);
        // Bold and italic rendered
        $viewResponse->assertSee('<strong>Uji Validitas</strong>', false);
        $viewResponse->assertSee('<em>Teknik Sampling</em>', false);
        // Blockquote rendered
        $viewResponse->assertSee('<blockquote>', false);
        // Code tag rendered
        $viewResponse->assertSee('<code>REF-2026</code>', false);

        // 2. Chat Send API response includes formatted_content in assistant_message
        $apiResponse = $this->postJson('/panel/ai-studio/chat/send', [
            'provider_uuid' => $provider->uuid,
            'model' => 'gpt-4o',
            'message' => 'Jelaskan metode penelitian kuantitatif.',
            'session_uuid' => $session->uuid,
        ]);

        $apiResponse->assertStatus(200);
        $apiResponse->assertJsonStructure([
            'success',
            'assistant_message' => [
                'id',
                'role',
                'content',
                'formatted_content',
                'provider_name',
                'model_used',
            ],
        ]);
        $this->assertNotEmpty($apiResponse->json('assistant_message.formatted_content'));
        $this->assertStringContainsString('<', $apiResponse->json('assistant_message.formatted_content'));
    }
}
