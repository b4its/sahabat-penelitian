<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentOperation extends Model
{
    protected $fillable = [
        'document_id',
        'version',
        'op_type',
        'payload',
        'actor',
    ];

    protected $casts = [
        'payload' => 'array',
        'version' => 'integer',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
