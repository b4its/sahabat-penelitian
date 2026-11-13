<?php

namespace Tests\Unit;

use App\Http\Controllers\AiStudioController;
use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use App\Models\AiProvider;
use App\Services\AiStudioService;
use Illuminate\Support\Str;
use Tests\TestCase;

class AiStudioWhiteBoxTest extends TestCase
{
    protected AiStudioService $aiStudioService;

    protected AiStudioController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->aiStudioService = new AiStudioService;
        $this->controller = new AiStudioController($this->aiStudioService);
    }

    /**
     * White-box WB01: AiProvider automatically generates UUID upon creation.
     */
    public function test_white_box_ai_provider_auto_generates_uuid(): void
    {
        $provider = AiProvider::create([
            'name' => 'Test Provider '.uniqid(),
            'provider' => 'openai',
            'base_url' => 'https://api.openai.com/v1',
            'api_key' => 'sk-test-sample-key',
            'models' => ['gpt-4o', 'gpt-4o-mini'],
            'default_model' => 'gpt-4o',
        ]);

        $this->assertNotEmpty($provider->uuid);
        $this->assertTrue(Str::isUuid($provider->uuid));
    }

    /**
     * White-box WB02: AiProvider encrypts API key and hides it in array/JSON serialization.
     */
    public function test_white_box_ai_provider_encrypts_and_hides_api_key(): void
    {
        $secretKey = 'sk-secret-confidential-key-12345';
        $provider = AiProvider::create([
            'name' => 'Secure Provider',
            'provider' => 'anthropic',
            'api_key' => $secretKey,
            'models' => ['claude-3-5-sonnet-20241022'],
        ]);

        // Key is decryptable via attribute getter
        $this->assertEquals($secretKey, $provider->api_key);

        // Raw database column is encrypted, not plain text
        $rawInDb = \DB::table('ai_providers')->where('id', $provider->id)->value('api_key');
        $this->assertNotEquals($secretKey, $rawInDb);

        // Key is hidden from serialization
        $serialized = $provider->toArray();
        $this->assertArrayNotHasKey('api_key', $serialized);
    }

    /**
     * White-box WB03: Masked API key accessor handles null, short, and long keys correctly.
     */
    public function test_white_box_masked_api_key_accessor(): void
    {
        $localProvider = new AiProvider([
            'provider' => 'ollama',
            'api_key' => null,
        ]);
        $this->assertEquals('(Tanpa API Key / Lokal)', $localProvider->masked_api_key);

        $shortKeyProvider = new AiProvider([
            'provider' => 'custom',
            'api_key' => '12345',
        ]);
        $this->assertEquals('•••••', $shortKeyProvider->masked_api_key);

        $standardKeyProvider = new AiProvider([
            'provider' => 'openai',
            'api_key' => 'sk-proj-1234567890abcdef',
        ]);
        $masked = $standardKeyProvider->masked_api_key;
        $this->assertStringStartsWith('sk-p', $masked);
        $this->assertStringEndsWith('cdef', $masked);
        $this->assertStringContainsString('••••••••', $masked);
    }

    /**
     * White-box WB04: Provider label and badge color return accurate styles for all drivers.
     */
    public function test_white_box_provider_label_and_badge_colors(): void
    {
        $drivers = [
            'openai' => ['OpenAI', 'emerald'],
            'gemini' => ['Google Gemini', 'blue'],
            'anthropic' => ['Anthropic Claude', 'amber'],
            'groq' => ['Groq Cloud', 'orange'],
            'openrouter' => ['OpenRouter', 'purple'],
            'ollama' => ['Ollama (Local)', 'slate'],
            'custom' => ['Custom OpenAI-Compatible', 'cyan'],
        ];

        foreach ($drivers as $driver => [$expectedLabel, $expectedColorKeyword]) {
            $provider = new AiProvider(['provider' => $driver]);
            $this->assertEquals($expectedLabel, $provider->provider_label);
            $this->assertStringContainsString($expectedColorKeyword, $provider->badge_color);
        }
    }

    /**
     * White-box WB05: Active scope filters active vs inactive providers.
     */
    public function test_white_box_scope_active_filters_correctly(): void
    {
        AiProvider::query()->delete();

        $active = AiProvider::create([
            'name' => 'Active Provider',
            'provider' => 'openai',
            'models' => ['gpt-4o'],
            'is_active' => true,
        ]);

        $inactive = AiProvider::create([
            'name' => 'Inactive Provider',
            'provider' => 'gemini',
            'models' => ['gemini-2.0-flash'],
            'is_active' => false,
        ]);

        $activeList = AiProvider::active()->get();
        $this->assertTrue($activeList->contains('id', $active->id));
        $this->assertFalse($activeList->contains('id', $inactive->id));
    }

    /**
     * White-box WB06: Relationships between AiProvider, AiChatSession, and AiChatMessage.
     */
    public function test_white_box_ai_studio_model_relationships(): void
    {
        $provider = AiProvider::create([
            'name' => 'Relationship Provider',
            'provider' => 'openai',
            'models' => ['gpt-4o'],
            'is_active' => true,
        ]);

        $session = AiChatSession::create([
            'title' => 'Sesi Uji Relasi',
            'ai_provider_id' => $provider->id,
            'model' => 'gpt-4o',
        ]);

        $this->assertTrue(Str::isUuid($session->uuid));
        $this->assertEquals($provider->id, $session->provider->id);

        $userMsg = AiChatMessage::create([
            'session_id' => $session->id,
            'role' => 'user',
            'content' => 'Halo AI!',
            'model_used' => 'gpt-4o',
        ]);

        $assistantMsg = AiChatMessage::create([
            'session_id' => $session->id,
            'role' => 'assistant',
            'content' => 'Halo! Ada yang bisa dibantu?',
            'model_used' => 'gpt-4o',
        ]);

        $messages = $session->messages;
        $this->assertCount(2, $messages);
        $this->assertEquals('user', $messages->first()->role);
        $this->assertEquals('assistant', $messages->last()->role);
        $this->assertEquals($session->id, $userMsg->session->id);
    }

    /**
     * White-box WB07: AiStudioService::getProviderPresets provides valid configurations for all drivers.
     */
    public function test_white_box_service_get_provider_presets(): void
    {
        $presets = $this->aiStudioService->getProviderPresets();

        $this->assertArrayHasKey('openai', $presets);
        $this->assertArrayHasKey('gemini', $presets);
        $this->assertArrayHasKey('anthropic', $presets);
        $this->assertArrayHasKey('groq', $presets);
        $this->assertArrayHasKey('ollama', $presets);

        $this->assertNotEmpty($presets['openai']['default_models']);
        $this->assertContains('gpt-4o', $presets['openai']['default_models']);
        $this->assertFalse($presets['ollama']['requires_api_key']);
        $this->assertTrue($presets['gemini']['requires_api_key']);
    }

    /**
     * White-box WB08: ensureDefaultProvidersExist populates default providers idempotently.
     */
    public function test_white_box_service_ensure_default_providers_exist(): void
    {
        AiProvider::query()->delete();
        $this->assertEquals(0, AiProvider::count());

        $this->aiStudioService->ensureDefaultProvidersExist();
        $countAfterFirst = AiProvider::count();
        $this->assertGreaterThanOrEqual(3, $countAfterFirst);

        // Calling again does not duplicate records
        $this->aiStudioService->ensureDefaultProvidersExist();
        $this->assertEquals($countAfterFirst, AiProvider::count());
    }

    /**
     * White-box WB09: testConnection handles demo/test keys and returns success latency.
     */
    public function test_white_box_service_test_connection_demo_mode(): void
    {
        $provider = AiProvider::create([
            'name' => 'Demo Provider',
            'provider' => 'openai',
            'base_url' => 'https://api.openai.com/v1',
            'api_key' => 'sk-test-demo-key-123',
            'models' => ['gpt-4o'],
            'status' => 'ready',
        ]);

        $result = $this->aiStudioService->testConnection($provider);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('berhasil diverifikasi', $result['message']);
        $this->assertGreaterThan(0, $result['latency_ms']);

        $provider->refresh();
        $this->assertEquals('connected', $provider->status);
        $this->assertNotNull($provider->last_tested_at);
    }

    /**
     * White-box WB10: chat generates contextual academic responses based on user query keywords.
     */
    public function test_white_box_service_chat_academic_responses(): void
    {
        $provider = AiProvider::create([
            'name' => 'OpenAI Riset',
            'provider' => 'openai',
            'api_key' => 'sk-test-demo',
            'models' => ['gpt-4o'],
        ]);

        // 1. Methodology query
        $methodologyResult = $this->aiStudioService->chat(
            $provider,
            'gpt-4o',
            [['role' => 'user', 'content' => 'Bagaimana metodologi penelitian yang baik?']]
        );
        $this->assertTrue($methodologyResult['success']);
        $this->assertStringContainsString('metodologi', strtolower($methodologyResult['content']));
        $this->assertEquals('gpt-4o', $methodologyResult['model']);

        // 2. Abstract query
        $abstractResult = $this->aiStudioService->chat(
            $provider,
            'gpt-4o',
            [['role' => 'user', 'content' => 'Bantu buatkan abstrak jurnal']]
        );
        $this->assertTrue($abstractResult['success']);
        $this->assertStringContainsString('abstrak', strtolower($abstractResult['content']));

        // 3. Citation query
        $citationResult = $this->aiStudioService->chat(
            $provider,
            'gpt-4o',
            [['role' => 'user', 'content' => 'Bagaimana format sitasi APA dan IEEE?']]
        );
        $this->assertTrue($citationResult['success']);
        $this->assertStringContainsString('apa 7th', strtolower($citationResult['content']));
    }

    /**
     * White-box WB11: Controller parseModelsInput handles comma-separated, newlines, and array inputs.
     */
    public function test_white_box_controller_parse_models_input(): void
    {
        $method = new \ReflectionMethod(AiStudioController::class, 'parseModelsInput');
        $method->setAccessible(true);

        // String with commas
        $parsed1 = $method->invoke($this->controller, 'gpt-4o, gpt-4o-mini, gpt-4-turbo');
        $this->assertEquals(['gpt-4o', 'gpt-4o-mini', 'gpt-4-turbo'], $parsed1);

        // String with newlines and duplicate spaces
        $parsed2 = $method->invoke($this->controller, "claude-3-5-sonnet\nclaude-3-5-haiku,\n  claude-3-opus  ");
        $this->assertEquals(['claude-3-5-sonnet', 'claude-3-5-haiku', 'claude-3-opus'], $parsed2);

        // Array input
        $parsed3 = $method->invoke($this->controller, ['gemini-2.0-flash', 'gemini-1.5-pro']);
        $this->assertEquals(['gemini-2.0-flash', 'gemini-1.5-pro'], $parsed3);
    }
}
