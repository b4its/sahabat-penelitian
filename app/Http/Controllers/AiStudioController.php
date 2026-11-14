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
            'models' => ['nullable'],
            'default_model' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $modelsList = ! empty($validated['models']) ? $this->parseModelsInput($validated['models']) : [];

        $defaultModel = $validated['default_model'] ?? ($modelsList[0] ?? null);
        if ($defaultModel && ! in_array($defaultModel, $modelsList, true)) {
            $defaultModel = $modelsList[0] ?? null;
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
            'models' => ['nullable'],
            'default_model' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $modelsList = isset($validated['models']) ? $this->parseModelsInput($validated['models']) : ($provider->models ?? []);

        $defaultModel = $validated['default_model'] ?? ($modelsList[0] ?? null);
        if ($defaultModel && ! in_array($defaultModel, $modelsList, true)) {
            $defaultModel = $modelsList[0] ?? null;
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
     * Bulk delete multiple selected AI Providers.
     */
    public function bulkDestroyProviders(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'uuids' => ['required', 'array', 'min:1'],
            'uuids.*' => ['required', 'string'],
        ]);

        $uuids = $request->input('uuids', []);
        $providers = AiProvider::whereIn('uuid', $uuids)->get();

        if ($providers->isEmpty()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada provider valid yang dipilih untuk dihapus.',
                ], 422);
            }

            return back()->with('error', 'Tidak ada provider valid yang dipilih untuk dihapus.');
        }

        $deletedCount = 0;
        foreach ($providers as $provider) {
            $provider->delete();
            $deletedCount++;
        }

        $msg = "{$deletedCount} provider AI berhasil dihapus.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'count' => $deletedCount,
            ]);
        }

        return redirect()->route('panel.ai-studio.index', ['tab' => 'connections'])
            ->with('status', $msg);
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
                'formatted_content' => Str::markdown($assistantMsg->content),
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
     * Display the AI Provider Personalize page (Transparency & Model Roles: Frontier vs Worker).
     */
    public function personalize(Request $request): View
    {
        $this->aiStudioService->ensureDefaultProvidersExist();

        $providers = AiProvider::active()->latest()->get();
        $selectedProvider = null;

        if ($request->filled('provider')) {
            $selectedProvider = $providers->firstWhere('uuid', $request->query('provider'));
        }

        if (! $selectedProvider) {
            $selectedProvider = $providers->first();
        }

        $divisionOfLabor = [
            'frontier' => [
                'role' => 'Frontier Model',
                'title' => 'Penalaran & Analisis Mendalam (High Reasoning)',
                'badge' => 'Tugas Penalaran',
                'badge_color' => 'bg-purple-100 text-purple-700 dark:bg-purple-950/70 dark:text-purple-300 border-purple-200 dark:border-purple-800',
                'icon' => 'fa-solid fa-brain',
                'summary' => 'Didedikasikan untuk tugas kognitif bernalar tinggi, sintesis argumentasi ilmiah, orkestrasi bab dokumen, dan analisis hipotesis.',
                'tasks' => [
                    [
                        'name' => 'Brainstorming Ide & Kerangka Bab',
                        'desc' => 'Merancang struktur naskah, memetakan pertanyaan riset, dan merumuskan kerangka teori.',
                        'complexity' => 'Tinggi (Kompleks)',
                    ],
                    [
                        'name' => 'Elaborasi & Analisis Konseptual',
                        'desc' => 'Memperdalam pembahasan analitis dan memperluas narasi bab dengan perspektif kritis.',
                        'complexity' => 'Tinggi (Kompleks)',
                    ],
                    [
                        'name' => 'Sintesis Literatur Multidisiplin',
                        'desc' => 'Menghubungkan teori-teori relevan dan membangun jembatan antar argumen akademik.',
                        'complexity' => 'Tinggi (Kompleks)',
                    ],
                    [
                        'name' => 'Peer Review & Evaluasi Naskah',
                        'desc' => 'Menilai koherensi paragraf, mengidentifikasi bias, dan memberikan kritik metodologis.',
                        'complexity' => 'Tinggi (Kompleks)',
                    ],
                ],
            ],
            'worker' => [
                'role' => 'Worker Model',
                'title' => 'Kecepatan & Presisi Eksekusi (High Speed & Precision)',
                'badge' => 'Tugas Cepat',
                'badge_color' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
                'icon' => 'fa-solid fa-bolt-lightning',
                'summary' => 'Didedikasikan untuk tugas mikro berkecepatan tinggi, penyelarasan format tata bahasa baku PUEBI, dan penataan sitasi instan.',
                'tasks' => [
                    [
                        'name' => 'Koreksi Ejaan & PUEBI (Gramatika)',
                        'desc' => 'Memperbaiki kata non-baku, tipo, tanda baca, dan konsistensi leksikal secara cepat.',
                        'complexity' => 'Cepat & Presisi',
                    ],
                    [
                        'name' => 'Format Sitasi APA 7th & IEEE',
                        'desc' => 'Menyusun daftar pustaka dan sitasi dalam teks sesuai standar publikasi internasional.',
                        'complexity' => 'Cepat & Presisi',
                    ],
                    [
                        'name' => 'Parafrase Nada & Ringkasan Singkat',
                        'desc' => 'Menyajikan pemadatan paragraf satu kalimat dan transformasi nada formal/akademik.',
                        'complexity' => 'Cepat & Presisi',
                    ],
                    [
                        'name' => 'Generasi Daftar Isi & Metadata Bab',
                        'desc' => 'Mengekstraksi penomoran bab dan running header tanpa overhead token berlebih.',
                        'complexity' => 'Cepat & Presisi',
                    ],
                ],
            ],
        ];

        return view('panel.ai-studio.personalize', [
            'providers' => $providers,
            'selectedProvider' => $selectedProvider,
            'divisionOfLabor' => $divisionOfLabor,
        ]);
    }

    /**
     * Save personalized Frontier and Worker model configuration.
     */
    public function savePersonalize(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'provider_uuid' => ['required', 'string', 'exists:ai_providers,uuid'],
            'frontier_model' => ['required', 'string', 'max:100'],
            'worker_model' => ['required', 'string', 'max:100'],
        ]);

        $provider = AiProvider::where('uuid', $validated['provider_uuid'])->firstOrFail();

        $provider->update([
            'frontier_model' => $validated['frontier_model'],
            'worker_model' => $validated['worker_model'],
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Konfigurasi personalisasi Frontier dan Worker model berhasil disimpan.',
                'provider' => [
                    'uuid' => $provider->uuid,
                    'name' => $provider->name,
                    'frontier_model' => $provider->frontier_model,
                    'worker_model' => $provider->worker_model,
                ],
            ]);
        }

        return redirect()->route('panel.ai-studio.personalize', ['provider' => $provider->uuid])
            ->with('success', 'Personalisasi model untuk '.$provider->name.' berhasil diperbarui!');
    }

    /**
     * Get details and models of a specific AI provider for personalization.
     */
    public function getProviderModels(string $uuid): JsonResponse
    {
        $provider = AiProvider::where('uuid', $uuid)->firstOrFail();

        return response()->json([
            'success' => true,
            'uuid' => $provider->uuid,
            'name' => $provider->name,
            'provider' => $provider->provider,
            'provider_label' => $provider->provider_label,
            'badge_color' => $provider->badge_color,
            'models' => $provider->models ?? [],
            'default_model' => $provider->default_model,
            'frontier_model' => $provider->active_frontier_model,
            'worker_model' => $provider->active_worker_model,
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
