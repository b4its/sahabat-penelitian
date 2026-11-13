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
     * Black-box BB15: Form validation prevents invalid provider creation.
     */
    public function test_black_box_provider_validation_rules(): void
    {
        // Missing name and models
        $response = $this->postJson('/panel/ai-studio/providers', [
            'provider' => 'openai',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'models']);
    }
}
