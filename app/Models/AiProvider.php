<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AiProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'name',
        'provider',
        'base_url',
        'api_key',
        'models',
        'default_model',
        'is_active',
        'description',
        'status',
        'last_tested_at',
    ];

    protected $casts = [
        'models' => 'array',
        'is_active' => 'boolean',
        'last_tested_at' => 'datetime',
        'api_key' => 'encrypted',
    ];

    protected $hidden = [
        'api_key',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (AiProvider $provider): void {
            if (empty($provider->uuid)) {
                $provider->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get chat sessions associated with this provider.
     */
    public function chatSessions(): HasMany
    {
        return $this->hasMany(AiChatSession::class, 'ai_provider_id');
    }

    /**
     * Scope to active providers only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get masked representation of the API key for secure display.
     */
    public function getMaskedApiKeyAttribute(): string
    {
        $rawKey = $this->api_key;
        if (empty($rawKey)) {
            return '(Tanpa API Key / Lokal)';
        }

        $length = strlen($rawKey);
        if ($length <= 8) {
            return str_repeat('•', max(4, $length));
        }

        $start = substr($rawKey, 0, 4);
        $end = substr($rawKey, -4);

        return $start.'••••••••'.$end;
    }

    /**
     * Get a human-readable display label for the provider driver.
     */
    public function getProviderLabelAttribute(): string
    {
        return match ($this->provider) {
            'openai' => 'OpenAI',
            'anthropic' => 'Anthropic Claude',
            'gemini' => 'Google Gemini',
            'groq' => 'Groq Cloud',
            'openrouter' => 'OpenRouter',
            'ollama' => 'Ollama (Local)',
            'mistral' => 'Mistral AI',
            'deepseek' => 'DeepSeek',
            default => 'Custom OpenAI-Compatible',
        };
    }

    /**
     * Get badge color styling for UI representation.
     */
    public function getBadgeColorAttribute(): string
    {
        return match ($this->provider) {
            'openai' => 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
            'anthropic' => 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800',
            'gemini' => 'bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-800',
            'groq' => 'bg-orange-50 dark:bg-orange-950/60 text-orange-700 dark:text-orange-300 border-orange-200 dark:border-orange-800',
            'openrouter' => 'bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-800',
            'ollama' => 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700',
            default => 'bg-cyan-50 dark:bg-cyan-950/60 text-cyan-700 dark:text-cyan-300 border-cyan-200 dark:border-cyan-800',
        };
    }
}
