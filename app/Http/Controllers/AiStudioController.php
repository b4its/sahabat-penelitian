<?php

namespace App\Http\Controllers;

use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use App\Models\AiProvider;
use App\Services\AiStudioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AiStudioController extends Controller
{
    public function __construct(
        public AiStudioService $aiStudioService
    ) {}

    /**
     * Display the AI Studio main page (Connection & Chat tabs).
     */
    public function index(Request $request): View
    {
        $this->aiStudioService->ensureDefaultProvidersExist();

        $activeTab = $request->query('tab', 'connections');
        if (! in_array($activeTab, ['connections', 'chat'], true)) {
            $activeTab = 'connections';
        }

        $providers = AiProvider::latest()->get();
        $activeProviders = AiProvider::active()->latest()->get();
        $presets = $this->aiStudioService->getProviderPresets();

        // Get or initialize active chat session
        $chatSession = null;
        if ($request->filled('session')) {
            $chatSession = AiChatSession::with('messages')
                ->where('uuid', $request->query('session'))
                ->first();
        }

        if (! $chatSession) {
            $chatSession = AiChatSession::with('messages')->latest()->first();
        }

        if (! $chatSession && $activeProviders->isNotEmpty()) {
            $defaultProvider = $activeProviders->first();
            $chatSession = AiChatSession::create([
                'uuid' => (string) Str::uuid(),
                'title' => 'Obrolan AI Riset',
                'ai_provider_id' => $defaultProvider->id,
                'model' => $defaultProvider->default_model,
                'system_prompt' => 'Anda adalah asisten kecerdasan buatan terpercaya untuk riset akademik dan penulisan karya ilmiah.',
            ]);
            $chatSession->load('messages');
        }

        return view('panel.ai-studio.index', [
            'activeTab' => $activeTab,
            'providers' => $providers,
            'activeProviders' => $activeProviders,
            'presets' => $presets,
            'chatSession' => $chatSession,
        ]);
    }

    /**
     * Dedicated direct route for AI Studio Connection tab.
     */
    public function connections(): RedirectResponse
    {
        return redirect()->route('panel.ai-studio.index', ['tab' => 'connections']);
    }

    /**
     * Dedicated direct route for AI Studio Chat tab.
     */
    public function chatView(): RedirectResponse
    {
        return redirect()->route('panel.ai-studio.index', ['tab' => 'chat']);
    }

    /**
     * Get JSON list of active providers and their available models.
     */
    public function getProvidersJson(): JsonResponse
    {
        $providers = AiProvider::active()->latest()->get()->map(function (AiProvider $provider) {
            return [
                'id' => $provider->id,
                'uuid' => $provider->uuid,
                'name' => $provider->name,
                'provider' => $provider->provider,
                'provider_label' => $provider->provider_label,
                'models' => $provider->models ?? [],
                'default_model' => $provider->default_model,
                'badge_color' => $provider->badge_color,
                'status' => $provider->status,
            ];
        });

        return response()->json([
            'success' => true,
            'providers' => $providers,
        ]);
    }

    /**
     * Store a newly created AI Provider.
     */
    public function storeProvider(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'provider' => ['required', 'string', 'max:50'],
            'base_url' => ['nullable', 'string', 'max:500'],
            'api_key' => ['nullable', 'string', 'max:1000'],
            'models' => ['required'],
            'default_model' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $modelsList = $this->parseModelsInput($validated['models']);
        if (empty($modelsList)) {
            $presets = $this->aiStudioService->getProviderPresets();
            $modelsList = $presets[$validated['provider']]['default_models'] ?? ['default-model'];
        }

        $defaultModel = $validated['default_model'] ?? ($modelsList[0] ?? 'default-model');
        if (! in_array($defaultModel, $modelsList, true)) {
            $defaultModel = $modelsList[0] ?? 'default-model';
        }

        $provider = AiProvider::create([
            'uuid' => (string) Str::uuid(),
            'name' => trim($validated['name']),
            'provider' => trim($validated['provider']),
            'base_url' => ! empty($validated['base_url']) ? trim($validated['base_url']) : null,
            'api_key' => ! empty($validated['api_key']) ? trim($validated['api_key']) : null,
            'models' => $modelsList,
            'default_model' => $defaultModel,
            'description' => ! empty($validated['description']) ? trim($validated['description']) : null,
            'is_active' => $request->boolean('is_active', true),
            'status' => 'ready',
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Provider AI '{$provider->name}' berhasil ditambahkan.",
                'provider' => $provider,
            ]);
        }

        return redirect()->route('panel.ai-studio.index', ['tab' => 'connections'])
            ->with('status', "Provider AI '{$provider->name}' berhasil ditambahkan ke daftar koneksi.");
    }

    /**
     * Update an existing AI Provider.
     */
    public function updateProvider(Request $request, string $uuid): RedirectResponse|JsonResponse
    {
        $provider = AiProvider::where('uuid', $uuid)->firstOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'provider' => ['required', 'string', 'max:50'],
            'base_url' => ['nullable', 'string', 'max:500'],
            'api_key' => ['nullable', 'string', 'max:1000'],
            'models' => ['required'],
            'default_model' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $modelsList = $this->parseModelsInput($validated['models']);
        if (empty($modelsList)) {
            $modelsList = $provider->models ?: ['default-model'];
        }

        $defaultModel = $validated['default_model'] ?? ($modelsList[0] ?? 'default-model');
        if (! in_array($defaultModel, $modelsList, true)) {
            $defaultModel = $modelsList[0] ?? 'default-model';
        }

        $updateData = [
            'name' => trim($validated['name']),
            'provider' => trim($validated['provider']),
            'base_url' => ! empty($validated['base_url']) ? trim($validated['base_url']) : null,
            'models' => $modelsList,
            'default_model' => $defaultModel,
            'description' => ! empty($validated['description']) ? trim($validated['description']) : null,
            'is_active' => $request->boolean('is_active', true),
        ];

        // Only update API key if supplied
        if ($request->filled('api_key')) {
            $updateData['api_key'] = trim($request->input('api_key'));
        }

        $provider->update($updateData);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Provider '{$provider->name}' berhasil diperbarui.",
                'provider' => $provider,
            ]);
        }

        return redirect()->route('panel.ai-studio.index', ['tab' => 'connections'])
            ->with('status', "Provider '{$provider->name}' berhasil diperbarui.");
    }

    /**
     * Delete an AI Provider.
     */
    public function destroyProvider(Request $request, string $uuid): RedirectResponse|JsonResponse
    {
        $provider = AiProvider::where('uuid', $uuid)->firstOrFail();
        $name = $provider->name;
        $provider->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Provider '{$name}' telah dihapus dari sistem.",
            ]);
        }

        return redirect()->route('panel.ai-studio.index', ['tab' => 'connections'])
            ->with('status', "Provider '{$name}' telah dihapus dari sistem.");
    }

    /**
     * Test connection to an AI Provider.
     */
    public function testProvider(string $uuid): JsonResponse
    {
        $provider = AiProvider::where('uuid', $uuid)->firstOrFail();
        $result = $this->aiStudioService->testConnection($provider);

        return response()->json($result);
    }

    /**
     * Toggle active status of an AI Provider.
     */
    public function toggleProvider(Request $request, string $uuid): RedirectResponse|JsonResponse
    {
        $provider = AiProvider::where('uuid', $uuid)->firstOrFail();
        $provider->is_active = ! $provider->is_active;
        $provider->save();

        $statusText = $provider->is_active ? 'diaktifkan' : 'dinonaktifkan';

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_active' => $provider->is_active,
                'message' => "Provider '{$provider->name}' berhasil {$statusText}.",
            ]);
        }

        return redirect()->route('panel.ai-studio.index', ['tab' => 'connections'])
            ->with('status', "Provider '{$provider->name}' berhasil {$statusText}.");
    }

    /**
     * Send a message in AI Studio Chat using the selected connected provider and model.
     */
    public function sendChatMessage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider_uuid' => ['required', 'string'],
            'model' => ['required', 'string'],
            'message' => ['required', 'string', 'max:8000'],
            'system_prompt' => ['nullable', 'string', 'max:2000'],
            'session_uuid' => ['nullable', 'string'],
        ]);

        $provider = AiProvider::where('uuid', $validated['provider_uuid'])->first();
        if (! $provider) {
            return response()->json([
                'success' => false,
                'message' => 'Provider AI yang dipilih tidak ditemukan.',
            ], 404);
        }

        if (! $provider->is_active) {
            return response()->json([
                'success' => false,
                'message' => "Provider '{$provider->name}' saat ini non-aktif. Silakan aktifkan terlebih dahulu di tabel koneksi.",
            ], 422);
        }

        // Validate model belongs to provider
        $allowedModels = $provider->models ?? [];
        if (! empty($allowedModels) && ! in_array($validated['model'], $allowedModels, true)) {
            // Append model if custom
            $allowedModels[] = $validated['model'];
            $provider->models = $allowedModels;
            $provider->save();
        }

        // Find or create session
        $session = null;
        if (! empty($validated['session_uuid'])) {
            $session = AiChatSession::where('uuid', $validated['session_uuid'])->first();
        }

        if (! $session) {
            $session = AiChatSession::create([
                'uuid' => (string) Str::uuid(),
                'title' => Str::limit(trim($validated['message']), 40, '...'),
                'ai_provider_id' => $provider->id,
                'model' => $validated['model'],
                'system_prompt' => $validated['system_prompt'] ?? null,
            ]);
        } else {
            $session->update([
                'ai_provider_id' => $provider->id,
                'model' => $validated['model'],
            ]);
        }

        // Store user message
        $userMsg = AiChatMessage::create([
            'session_id' => $session->id,
            'role' => 'user',
            'content' => trim($validated['message']),
            'provider_name' => $provider->name,
            'model_used' => $validated['model'],
        ]);

        // Build messages array
        $recentMessages = $session->messages()->latest()->take(10)->get()->reverse();
        $messagesPayload = [];
        foreach ($recentMessages as $msg) {
            $messagesPayload[] = [
                'role' => $msg->role,
                'content' => $msg->content,
            ];
        }

        // Execute chat via AI Studio Service
        $response = $this->aiStudioService->chat(
            $provider,
            $validated['model'],
            $messagesPayload,
            $validated['system_prompt'] ?? $session->system_prompt
        );

        // Store assistant reply
        $assistantMsg = AiChatMessage::create([
            'session_id' => $session->id,
            'role' => 'assistant',
            'content' => $response['content'],
            'provider_name' => $provider->name,
            'model_used' => $validated['model'],
            'tokens_used' => $response['tokens_used'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'session_uuid' => $session->uuid,
            'user_message' => [
                'id' => $userMsg->id,
                'role' => $userMsg->role,
                'content' => $userMsg->content,
                'created_at' => $userMsg->created_at->format('H:i'),
            ],
            'assistant_message' => [
                'id' => $assistantMsg->id,
                'role' => $assistantMsg->role,
                'content' => $assistantMsg->content,
                'provider_name' => $provider->name,
                'model_used' => $validated['model'],
                'tokens_used' => $response['tokens_used'] ?? null,
                'created_at' => $assistantMsg->created_at->format('H:i'),
            ],
        ]);
    }

    /**
     * Clear current chat session messages or create a fresh one.
     */
    public function clearChatSession(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_uuid' => ['nullable', 'string'],
        ]);

        if (! empty($validated['session_uuid'])) {
            $session = AiChatSession::where('uuid', $validated['session_uuid'])->first();
            if ($session) {
                $session->messages()->delete();
                $session->delete();
            }
        }

        // Create fresh session
        $firstActive = AiProvider::active()->first();
        $newSession = AiChatSession::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Obrolan AI Riset Baru',
            'ai_provider_id' => $firstActive?->id,
            'model' => $firstActive?->default_model,
        ]);

        return response()->json([
            'success' => true,
            'session_uuid' => $newSession->uuid,
            'message' => 'Sesi obrolan baru telah dimulai.',
        ]);
    }

    /**
     * Parse models input string (e.g. comma separated or newlines) or array into a clean array.
     */
    protected function parseModelsInput(mixed $input): array
    {
        if (is_array($input)) {
            return array_values(array_filter(array_map('trim', $input)));
        }

        if (is_string($input)) {
            // Split by comma or newline
            $items = preg_split('/[\r\n,]+/', $input);
            $cleaned = array_filter(array_map('trim', (array) $items));

            return array_values(array_unique($cleaned));
        }

        return [];
    }
}
