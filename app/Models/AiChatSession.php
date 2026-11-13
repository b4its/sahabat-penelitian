<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AiChatSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'title',
        'ai_provider_id',
        'model',
        'system_prompt',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (AiChatSession $session): void {
            if (empty($session->uuid)) {
                $session->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * The AI provider associated with this chat session.
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class, 'ai_provider_id');
    }

    /**
     * The messages in this chat session.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(AiChatMessage::class, 'session_id')->oldest();
    }
}
