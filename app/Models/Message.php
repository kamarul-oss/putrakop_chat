<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single message within a chat conversation.
 *
 * Used by the AIService to resolve department context and message content.
 */
final class Message extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'conversation_id',
        'department_id',
        'sender_id',
        'sender_type',
        'content',
        'message_type',
        'language',
        'is_ai_generated',
        'metadata',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_ai_generated' => 'boolean',
        'metadata' => 'array',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function sender(): BelongsTo
    {
        return $this->morphTo();
    }
}
