<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

/**
 * Customer satisfaction rating for a completed conversation.
 *
 * Supports a 1–5 star rating with optional feedback and complaint text.
 */
final class Rating extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'conversation_id',
        'rating',
        'feedback',
        'complaint',
        'created_by',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'rating' => 'integer',
    ];

    /**
     * Boot the model — enforce rating constraint on save.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (Rating $model): void {
            if ($model->rating < 1 || $model->rating > 5) {
                throw new InvalidArgumentException(
                    'Rating must be between 1 and 5. Got: ' . $model->rating
                );
            }
        });
    }

    // ─── Relationships ──────────────────────────────────────────

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Helpers ────────────────────────────────────────────────

    /**
     * Get a human-readable label for the rating.
     */
    public function getRatingLabelAttribute(): string
    {
        return match ($this->rating) {
            1 => 'Very Poor',
            2 => 'Poor',
            3 => 'Average',
            4 => 'Good',
            5 => 'Excellent',
            default => 'Unknown',
        };
    }
}
