<?php

declare(strict_types=1);

namespace App\Services\Chat;

use App\Models\Conversation;
use App\Models\Rating;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Handles customer satisfaction ratings for conversations.
 *
 * Provides submission, retrieval, and aggregation of 1–5 star ratings
 * with optional feedback and complaint text. Caches department-level
 * aggregates to avoid repeated expensive aggregation queries.
 */
final class RatingService
{
    private const CACHE_PREFIX = 'chat:rating:';
    private const CACHE_TTL = 300; // 5 minutes

    /**
     * Submit a rating for a closed conversation.
     *
     * Creates the Rating record, updates the conversation metadata
     * to flag it as rated, and clears any cached aggregates for the
     * department.
     */
    public function submitRating(
        Conversation $conversation,
        int $rating,
        ?string $feedback,
        ?string $complaint,
        int $userId,
    ): Rating {
        $record = Rating::create([
            'conversation_id' => $conversation->id,
            'rating' => $rating,
            'feedback' => $feedback,
            'complaint' => $complaint,
            'created_by' => $userId,
        ]);

        // Flag conversation as rated
        $conversation->update(['has_rating' => true]);

        // Clear cached aggregates for this department
        $this->clearDepartmentCache($conversation->department_id);

        return $record->load('conversation');
    }

    /**
     * Calculate the average rating for a department over the given period.
     *
     * Returns 0.0 when no ratings exist. Result is cached for 5 minutes.
     */
    public function getAverageRating(int $departmentId, int $days = 30): float
    {
        $cacheKey = self::CACHE_PREFIX . "avg:{$departmentId}:{$days}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($departmentId, $days): float {
            $avg = Rating::whereHas('conversation', function ($query) use ($departmentId): void {
                $query->where('department_id', $departmentId);
            })
                ->where('created_at', '>=', now()->subDays($days))
                ->avg('rating');

            return $avg !== null ? round((float) $avg, 2) : 0.0;
        });
    }

    /**
     * Get the count of ratings at each level (1–5) for a department.
     *
     * Returns an associative array keyed by rating level, e.g.
     * [1 => 3, 2 => 1, 3 => 5, 4 => 12, 5 => 20].
     */
    public function getRatingDistribution(int $departmentId, int $days = 30): array
    {
        $cacheKey = self::CACHE_PREFIX . "dist:{$departmentId}:{$days}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($departmentId, $days): array {
            $counts = Rating::whereHas('conversation', function ($query) use ($departmentId): void {
                $query->where('department_id', $departmentId);
            })
                ->where('created_at', '>=', now()->subDays($days))
                ->select('rating', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
                ->groupBy('rating')
                ->pluck('total', 'rating')
                ->toArray();

            // Ensure all rating levels are present
            $distribution = [];
            for ($level = 1; $level <= 5; $level++) {
                $distribution[$level] = $counts[$level] ?? 0;
            }

            return $distribution;
        });
    }

    /**
     * Get the most recent ratings for a department.
     *
     * Includes conversation and creator (customer) data for context.
     */
    public function getRecentRatings(int $departmentId, int $limit = 10): Collection
    {
        return Rating::whereHas('conversation', function ($query) use ($departmentId): void {
            $query->where('department_id', $departmentId);
        })
            ->with([
                'conversation:id,uuid,user_id,department_id,agent_id,status',
                'creator:id,name,avatar',
            ])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Determine whether a conversation already has a rating.
     */
    public function hasRated(Conversation $conversation): bool
    {
        return Rating::where('conversation_id', $conversation->id)->exists();
    }

    // ─── Private helpers ────────────────────────────────────────

    /**
     * Flush all cached aggregates for a department.
     */
    private function clearDepartmentCache(int $departmentId): void
    {
        // Flush avg and dist cache keys for common day ranges
        foreach ([7, 14, 30, 60, 90] as $days) {
            Cache::forget(self::CACHE_PREFIX . "avg:{$departmentId}:{$days}");
            Cache::forget(self::CACHE_PREFIX . "dist:{$departmentId}:{$days}");
        }
    }
}
