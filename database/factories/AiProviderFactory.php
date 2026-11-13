<?php

namespace Database\Factories;

use App\Models\AiProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AiProviderFactory extends Factory
{
    protected $model = AiProvider::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'name' => 'OpenAI GPT-4o Production',
            'provider' => 'openai',
            'base_url' => 'https://api.openai.com/v1',
            'api_key' => 'sk-test-'.Str::random(32),
            'models' => ['gpt-4o', 'gpt-4o-mini', 'gpt-4-turbo'],
            'default_model' => 'gpt-4o',
            'is_active' => true,
            'description' => 'Default primary LLM provider for academic document processing.',
            'status' => 'ready',
            'last_tested_at' => now(),
        ];
    }

    public function gemini(): static
    {
        return $this->state(fn () => [
            'name' => 'Google Gemini Studio',
            'provider' => 'gemini',
            'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
            'api_key' => 'AIzaSy'.Str::random(30),
            'models' => ['gemini-2.0-flash', 'gemini-1.5-pro', 'gemini-1.5-flash'],
            'default_model' => 'gemini-2.0-flash',
        ]);
    }

    public function anthropic(): static
    {
        return $this->state(fn () => [
            'name' => 'Anthropic Claude AI',
            'provider' => 'anthropic',
            'base_url' => 'https://api.anthropic.com/v1',
            'api_key' => 'sk-ant-'.Str::random(32),
            'models' => ['claude-3-5-sonnet-20241022', 'claude-3-5-haiku-20241022'],
            'default_model' => 'claude-3-5-sonnet-20241022',
        ]);
    }

    public function ollama(): static
    {
        return $this->state(fn () => [
            'name' => 'Local Ollama Engine',
            'provider' => 'ollama',
            'base_url' => 'http://localhost:11434',
            'api_key' => null,
            'models' => ['llama3.2', 'deepseek-r1:8b', 'mistral'],
            'default_model' => 'llama3.2',
        ]);
    }
}
