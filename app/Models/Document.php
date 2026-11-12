<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Document extends Model
{
    protected $fillable = [
        'uuid',
        'title',
        'content_html',
        'model_json',
        'owner_id',
        'restricted_mode',
        'password_hash',
        'current_version',
    ];

    protected $casts = [
        'model_json' => 'array',
        'current_version' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Document $doc) {
            if (empty($doc->uuid)) {
                $doc->uuid = (string) Str::uuid();
            }
        });
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class);
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(DocumentPermission::class);
    }

    public function operations(): HasMany
    {
        return $this->hasMany(DocumentOperation::class);
    }

    public function auditEvents(): HasMany
    {
        return $this->hasMany(AuditEvent::class);
    }
}
