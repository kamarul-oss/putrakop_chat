<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a conversation's position in the waiting queue for a department.
 *
 * Tracks queue position, priority scoring, estimated wait time, and assignment status.
 */
final class Queue extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'department_id',
        'uuid',
        'status',
        'conversation_id',
        'position',
        'priority_score',
        'estimated_wait_seconds',
        'started_at',
        'assigned_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'position' => 'integer',
        'priority_score' => 'integer',
        'estimated_wait_seconds' => 'integer',
        'started_at' => 'datetime',
        'assigned_at' => 'datetime',
    ];

    // ─── Relationships ──────────────────────────────────────────

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    // ─── Scopes ─────────────────────────────────────────────────

    /**
     * Filter queues belonging to a specific department.
     */
    public function scopeByDepartment(Builder $query, int $departmentId): Builder
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Filter queues with status "waiting" (not yet assigned to an agent).
     */
    public function scopeWaiting(Builder $query): Builder
    {
        return $query->where('status', 'waiting');
    }

    /**
     * Filter queues that have been assigned to an agent.
     */
    public function scopeAssigned(Builder $query): Builder
    {
        return $query->where('status', 'assigned');
    }

    // ─── Helpers ────────────────────────────────────────────────

    /**
     * Check if this queue entry is currently waiting.
     */
    public function isWaiting(): bool
    {
        return $this->status === 'waiting';
    }

    /**
     * Check if this queue entry has been assigned.
     */
    public function isAssigned(): bool
    {
        return $this->status === 'assigned';
    }
}
