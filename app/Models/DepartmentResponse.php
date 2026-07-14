<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\DepartmentScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FAQ response template for a specific department.
 *
 * Each response has bilingual content (EN/BM), trigger keywords
 * for AI-powered matching, and a workflow state (active/approved).
 */
final class DepartmentResponse extends Model
{
    use HasFactory;

    /**
     * Boot the model — apply global scopes and mutators.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Apply department isolation globally (except for admin queries)
        static::addGlobalScope(new DepartmentScope);

        // Sanitize content before saving
        static::saving(function (DepartmentResponse $model): void {
            $model->content_en = $model->sanitizeContent($model->content_en);
            $model->content_bm = $model->sanitizeContent($model->content_bm);
            $model->response_key = $model->sanitizeResponseKey($model->response_key);
            $model->trigger_keywords = $model->sanitizeKeywords($model->trigger_keywords);
        });
    }

    /**
     * Mass-assignable fields.
     *
     * SECURITY: department_id, is_approved, created_by are excluded.
     * These must be set server-side only to prevent privilege escalation.
     */
    /** @var list<string> */
    protected $fillable = [
        'response_key',
        'content_en',
        'content_bm',
        'trigger_keywords',
        'priority',
        'is_active',
        'updated_by',
    ];

    /**
     * Fields that should NOT be mass-assigned even if in $fillable.
     * Defense-in-depth: these are set via explicit assignment only.
     */
    /** @var list<string> */
    protected $guarded = [
        'id',
        'department_id',
        'is_approved',
        'created_by',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'trigger_keywords' => 'array',
        'priority' => 'integer',
        'is_active' => 'boolean',
        'is_approved' => 'boolean',
    ];

    // ─── Relationships ──────────────────────────────────────────

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ─── Scopes ─────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('is_approved', true);
    }

    public function scopeByDepartment(Builder $query, int $departmentId): Builder
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('priority')->orderBy('response_key');
    }

    // ─── Helpers ────────────────────────────────────────────────

    /**
     * Get the content for the given language code.
     */
    public function getContent(string $language = 'en'): string
    {
        return match ($language) {
            'bm', 'ms' => $this->content_bm,
            default => $this->content_en,
        };
    }

    /**
     * Sanitize content — strip dangerous HTML, keep safe formatting only.
     *
     * SECURITY: Prevents stored XSS via FAQ content fields.
     */
    private function sanitizeContent(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        // Strip all HTML tags except safe formatting
        $cleaned = strip_tags($value, '<p><br><strong><em><ul><ol><li>');

        // Encode any remaining angle brackets
        $cleaned = htmlspecialchars($cleaned, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Re-apply allowed tags (htmlspecialchars encodes them)
        $cleaned = str_replace(
            ['&lt;p&gt;', '&lt;br&gt;', '&lt;strong&gt;', '&lt;em&gt;', '&lt;ul&gt;', '&lt;ol&gt;', '&lt;li&gt;', '&lt;/p&gt;', '&lt;/strong&gt;', '&lt;/em&gt;', '&lt;/ul&gt;', '&lt;/ol&gt;', '&lt;/li&gt;'],
            ['<p>', '<br>', '<strong>', '<em>', '<ul>', '<ol>', '<li>', '</p>', '</strong>', '</em>', '</ul>', '</ol>', '</li>'],
            $cleaned
        );

        return trim($cleaned);
    }

    /**
     * Sanitize response key — alphanumeric, hyphens, underscores only.
     *
     * SECURITY: Prevents injection via response_key field.
     */
    private function sanitizeResponseKey(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        // Allow only alphanumeric, hyphens, underscores, spaces
        $cleaned = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $value);

        return trim($cleaned);
    }

    /**
     * Sanitize trigger keywords — strip tags, limit count, trim each.
     *
     * SECURITY: Prevents stored XSS via keyword fields.
     */
    private function sanitizeKeywords(?array $keywords): array
    {
        if (!is_array($keywords)) {
            return [];
        }

        // Limit to max 20 keywords
        $keywords = array_slice($keywords, 0, 20);

        return array_map(function (string $keyword): string {
            // Strip all HTML tags
            $cleaned = strip_tags($keyword);
            // Trim whitespace
            $cleaned = trim($cleaned);
            // Limit length
            $cleaned = mb_substr($cleaned, 0, 100);

            return $cleaned;
        }, $keywords);
    }
}
