<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentVersion extends Model
{
    protected $fillable = [
        'document_id',
        'version_number',
        'title',
        'content_html',
        'model_json',
        'author',
        'change_summary',
    ];

    protected $casts = [
        'model_json' => 'array',
        'version_number' => 'integer',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
