<?php

use App\Models\AiProvider;

/*
|--------------------------------------------------------------------------
| Black Box Feature Tests: AI Provider Personalize & Transparency
|--------------------------------------------------------------------------
| Testing user-facing page, searchable provider select, registered models,
| Frontier vs Worker job allocations, transparency matrix, and save logic.
*/

test('black-box: ai provider personalize page loads successfully at /panel/ai-studio/personalize', function () {
    $response = $this->get('/panel/ai-studio/personalize');

    $response->assertStatus(200);
    $response->assertSee('Personalisasi & Transparansi Model AI');
    $response->assertSee('Frontier Model');
    $response->assertSee('Worker Model');
});

test('black-box: ai provider personalize page renders searchable provider select with search input', function () {
    $response = $this->get('/panel/ai-studio/personalize');

    $response->assertStatus(200);
    $response->assertSee('Langkah 1: Pilih AI Provider');
    $response->assertSee('personalize-provider-select', false);
    $response->assertSee('personalize-provider-search-trigger', false);
    $response->assertSee('personalize-provider-search-input', false);
    $response->assertSee('personalize-provider-options-list', false);
    $response->assertSee('personalize-provider-dropdown', false);
    $response->assertSee('personalize-provider-empty-result', false);
    $response->assertSee('personalize-provider-chevron', false);
});

test('black-box: ai provider personalize page displays registered models and both job selectors', function () {
    $provider = AiProvider::active()->first();
    expect($provider)->not->toBeNull();

    $response = $this->get('/panel/ai-studio/personalize?provider='.$provider->uuid);

    $response->assertStatus(200);
    $response->assertSee('Model yang Terdaftar pada '.$provider->name);
    $response->assertSee('frontier-model-select', false);
    $response->assertSee('frontier-model-search-trigger', false);
    $response->assertSee('frontier-model-search-input', false);
    $response->assertSee('frontier-model-dropdown', false);
    $response->assertSee('frontier-model-options-list', false);
    $response->assertSee('frontier-model-empty-result', false);
    $response->assertSee('worker-model-select', false);
    $response->assertSee('worker-model-search-trigger', false);
    $response->assertSee('worker-model-search-input', false);
    $response->assertSee('worker-model-dropdown', false);
    $response->assertSee('worker-model-options-list', false);
    $response->assertSee('worker-model-empty-result', false);
    $response->assertSee('btn-save-personalize', false);
    $response->assertSee('Matriks Transparansi Alokasi Komputasi');
    $response->assertSee('Uji Coba Alokasi Permintaan Otomatis');
});

test('black-box: user can save personalized frontier and worker model allocations via form', function () {
    $provider = AiProvider::create([
        'name' => 'Custom Allocation Provider '.uniqid(),
        'provider' => 'openai',
        'models' => ['gpt-4o', 'gpt-4o-mini', 'o1-preview'],
        'default_model' => 'gpt-4o',
        'frontier_model' => 'gpt-4o',
        'worker_model' => 'gpt-4o-mini',
        'is_active' => true,
    ]);

    $response = $this->post('/panel/ai-studio/personalize', [
        'provider_uuid' => $provider->uuid,
        'frontier_model' => 'o1-preview',
        'worker_model' => 'gpt-4o-mini',
    ]);

    $response->assertRedirect('/panel/ai-studio/personalize?provider='.$provider->uuid);
    $response->assertSessionHas('success');

    $provider->refresh();
    expect($provider->frontier_model)->toBe('o1-preview')
        ->and($provider->worker_model)->toBe('gpt-4o-mini');
});

test('black-box: save personalize supports json requests and returns updated model roles', function () {
    $provider = AiProvider::create([
        'name' => 'JSON Personalize Provider '.uniqid(),
        'provider' => 'gemini',
        'models' => ['gemini-2.0-flash', 'gemini-1.5-pro'],
        'default_model' => 'gemini-2.0-flash',
        'frontier_model' => 'gemini-2.0-flash',
        'worker_model' => 'gemini-2.0-flash',
        'is_active' => true,
    ]);

    $response = $this->postJson('/panel/ai-studio/personalize', [
        'provider_uuid' => $provider->uuid,
        'frontier_model' => 'gemini-1.5-pro',
        'worker_model' => 'gemini-2.0-flash',
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'provider' => [
                'uuid' => $provider->uuid,
                'frontier_model' => 'gemini-1.5-pro',
                'worker_model' => 'gemini-2.0-flash',
            ],
        ]);
});

test('black-box: save personalize validates required inputs with 422', function () {
    $response = $this->postJson('/panel/ai-studio/personalize', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['provider_uuid', 'frontier_model', 'worker_model']);
});

test('black-box: api provider models endpoint returns json with models and active roles', function () {
    $provider = AiProvider::active()->first();
    expect($provider)->not->toBeNull();

    $response = $this->getJson('/panel/ai-studio/providers/'.$provider->uuid.'/models');

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'uuid',
            'name',
            'provider_label',
            'badge_color',
            'models',
            'default_model',
            'frontier_model',
            'worker_model',
        ]);
});

test('black-box: panel sidebar contains Personalisasi Model navigation link', function () {
    $response = $this->get('/panel/dashboard');

    $response->assertStatus(200);
    $response->assertSee(route('panel.ai-studio.personalize'), false);
    $response->assertSee('Personalisasi Model');
    $response->assertSee('Roles');
});
