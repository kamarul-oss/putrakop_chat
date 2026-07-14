<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Internal note attached to a conversation.
 *
 * Visible only to agents and managers — never exposed to customers.
 */
final class InternalNote extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'conversation_id',
        'user_id',
        'content',
    ];

    // ─── Relationships ──────────────────────────────────────────

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
