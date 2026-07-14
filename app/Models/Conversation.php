<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A chat conversation between a customer and the support system.
 *
 * Tracks lifecycle from queue entry through active chat to closure,
 * including department routing, agent assignment, and satisfaction ratings.
 */
final class Conversation extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'uuid',
        'user_id',
        'department_id',
        'agent_id',
        'status',
        'language',
        'started_at',
        'ended_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    /**
     * Boot the model — auto-generate UUID on creation.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Conversation $model): void {
            if ($model->uuid === null) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    // ─── Relationships ──────────────────────────────────────────

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * The customer who initiated this conversation.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The agent assigned to handle this conversation.
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /**
     * The queue entry tracking this conversation's wait position.
     */
    public function queue(): HasMany
    {
        return $this->hasMany(Queue::class);
    }

    /**
     * Customer satisfaction ratings for this conversation.
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    /**
     * Internal notes visible only to agents and managers.
     */
    public function internalNotes(): HasMany
    {
        return $this->hasMany(InternalNote::class);
    }

    // ─── Scopes ─────────────────────────────────────────────────

    /**
     * Filter conversations that are currently active (status = active or pending).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['active', 'pending']);
    }

    /**
     * Filter conversations by a specific status.
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Filter conversations belonging to a specific department.
     */
    public function scopeByDepartment(Builder $query, int $departmentId): Builder
    {
        return $query->where('department_id', $departmentId);
    }

    // ─── Helpers ────────────────────────────────────────────────

    /**
     * Check if this conversation is currently active.
     */
    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'pending'], true);
    }

    /**
     * Check if this conversation has been closed.
     */
    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    /**
     * Get the duration of the conversation in seconds.
     *
     * Returns null if the conversation hasn't ended yet (uses current time).
     */
    public function getDurationAttribute(): ?int
    {
        if ($this->started_at === null) {
            return null;
        }

        $endTime = $this->ended_at ?? Carbon::now();

        return (int) $this->started_at->diffInSeconds($endTime);
    }
}
