<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiMessage extends Model
{
    protected $fillable = [
        'ai_session_id', 
        'role', 
        'content', 
        'detected_intent', 
        'entity_type', 
        'suggested_ids'
    ];

    protected $casts = [
        'suggested_ids' => 'array',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(AiChatSession::class, 'ai_session_id');
    }
}