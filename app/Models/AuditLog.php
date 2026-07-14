<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Immutable audit trail for model changes across the application.
 *
 * Records old/new values, the acting user, and request metadata
 * for every auditable event (created, updated, deleted, etc.).
 */
final class AuditLog extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'auditable_type',
        'auditable_id',
        'event',
        'old_values',
        'new_values',
        'user_id',
        'ip_address',
        'user_agent',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * Disable timestamps — audit logs are immutable once written.
     */
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = null;

    // ─── Relationships ──────────────────────────────────────────

    /**
     * The model that was audited (polymorphic).
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The user who performed the audited action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Static Factory ─────────────────────────────────────────

    /**
     * Create a new audit log entry.
     *
     * @param  Model        $model  The auditable model instance.
     * @param  string       $event  The event name (created, updated, deleted, etc.).
     * @param  array|null   $old    Previous attribute values (before change).
     * @param  array|null   $new    New attribute values (after change).
     * @return self
     */
    public static function log(
        Model $model,
        string $event,
        ?array $old = null,
        ?array $new = null,
    ): self {
        /** @var \Illuminate\Http\Request|null $request */
        $request = request();

        return static::create([
            'auditable_type' => get_class($model),
            'auditable_id' => $model->getKey(),
            'event' => $event,
            'old_values' => $old ?? [],
            'new_values' => $new ?? $model->getAttributes(),
            'user_id' => $request?->user()?->id,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    // ─── Scopes ─────────────────────────────────────────────────

    /**
     * Filter logs for a specific auditable model class.
     */
    public function scopeByAuditableType(Builder $query, string $type): Builder
    {
        return $query->where('auditable_type', $type);
    }

    /**
     * Filter logs for a specific event type.
     */
    public function scopeByEvent(Builder $query, string $event): Builder
    {
        return $query->where('event', $event);
    }
}
