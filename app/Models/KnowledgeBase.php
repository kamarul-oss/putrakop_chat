<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Knowledge base article for AI-powered and agent-assisted responses.
 *
 * Bilingual content (EN/BM) with trigger keywords, category grouping,
 * and department-level scoping.
 */
final class KnowledgeBase extends Model
{
    use HasFactory;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'title_en',
        'title_bm',
        'content_en',
        'content_bm',
        'department_id',
        'category',
        'is_active',
        'priority',
        'trigger_keywords',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer',
        'trigger_keywords' => 'array',
    ];

    // ─── Relationships ──────────────────────────────────────────

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    // ─── Scopes ─────────────────────────────────────────────────

    /**
     * Filter only active knowledge base articles.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Filter articles belonging to a specific department.
     */
    public function scopeByDepartment(Builder $query, int $departmentId): Builder
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Filter articles belonging to a specific category.
     */
    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    // ─── Helpers ────────────────────────────────────────────────

    /**
     * Get the title for the given language code.
     */
    public function getTitle(string $language = 'en'): string
    {
        return match ($language) {
            'bm', 'ms' => $this->title_bm,
            default => $this->title_en,
        };
    }

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
}
