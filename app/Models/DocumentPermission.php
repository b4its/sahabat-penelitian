<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentPermission extends Model
{
    protected $fillable = [
        'document_id',
        'user_email',
        'role',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
