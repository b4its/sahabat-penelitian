<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditEvent extends Model
{
    protected $fillable = [
        'document_id',
        'event_type',
        'actor',
        'details',
        'ip_address',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
