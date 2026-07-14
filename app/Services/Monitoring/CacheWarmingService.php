<?php

declare(strict_types=1);

namespace App\Services\Monitoring;

use App\Models\Department;
use App\Models\KnowledgeBase;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Warms and manages application caches for optimal performance.
 *
 * Pre-loads frequently accessed data (departments, knowledge base articles,
 * settings, agent availability) into cache to reduce database queries
 * during high-traffic periods.
 */
final class CacheWarmingService
{
    /**
     * Cache key prefixes for organized management.
     */
    private const PREFIX = 'putrakop:';

    /**
     * Default TTLs in minutes.
     */
    private const TTL_DEPARTMENTS = 60;
    private const TTL_KNOWLEDGE_BASE = 120;
    private const TTL_SETTINGS = 180;
    private const TTL_AGENT_AVAILABILITY = 5;

    /**
     * Warm all application caches.
     */
    public function warmAll(): void
    {
        $this->warmDepartments();
        $this->warmKnowledgeBase();
        $this->warmSettings();
        $this->warmAgentAvailability();

        Cache::put(self::PREFIX . 'last_warmed', Carbon::now()->toISOString(), now()->addHours(24));
    }

    /**
     * Cache active departments for quick access.
     */
    public function warmDepartments(): void
    {
        $departments = Department::where('is_active', true)
            ->orderBy('priority', 'asc')
            ->get()
            ->map(fn (Department $dept) => [
                'id' => $dept->id,
                'name_en' => $dept->name_en,
                'name_bm' => $dept->name_bm,
                'description_en' => $dept->description_en,
                'description_bm' => $dept->description_bm,
                'color' => $dept->color,
                'icon' => $dept->icon,
                'priority' => $dept->priority,
                'max_queue_size' => $dept->max_queue_size,
                'max_agents' => $dept->max_agents,
                'business_hours' => $dept->business_hours,
            ])
            ->toArray();

        Cache::put(self::PREFIX . 'departments:active', $departments, now()->addMinutes(self::TTL_DEPARTMENTS));

        // Cache individual departments for quick lookup
        foreach ($departments as $department) {
            Cache::put(
                self::PREFIX . 'department:' . $department['id'],
                $department,
                now()->addMinutes(self::TTL_DEPARTMENTS)
            );
        }

        // Cache department count
        Cache::put(self::PREFIX . 'departments:count', count($departments), now()->addMinutes(self::TTL_DEPARTMENTS));
    }

    /**
     * Cache active knowledge base articles grouped by department and category.
     */
    public function warmKnowledgeBase(): void
    {
        $articles = KnowledgeBase::where('is_active', true)
            ->orderBy('priority', 'desc')
            ->get();

        // Cache all active articles
        $allArticles = $articles->map(fn (KnowledgeBase $kb) => [
            'id' => $kb->id,
            'title_en' => $kb->title_en,
            'title_bm' => $kb->title_bm,
            'content_en' => $kb->content_en,
            'content_bm' => $kb->content_bm,
            'department_id' => $kb->department_id,
            'category' => $kb->category,
            'priority' => $kb->priority,
            'trigger_keywords' => $kb->trigger_keywords,
        ])->toArray();

        Cache::put(self::PREFIX . 'kb:all', $allArticles, now()->addMinutes(self::TTL_KNOWLEDGE_BASE));

        // Cache articles grouped by department
        $grouped = $articles->groupBy('department_id');
        foreach ($grouped as $deptId => $deptArticles) {
            Cache::put(
                self::PREFIX . 'kb:department:' . $deptId,
                $deptArticles->toArray(),
                now()->addMinutes(self::TTL_KNOWLEDGE_BASE)
            );
        }

        // Cache articles grouped by category
        $byCategory = $articles->groupBy('category');
        foreach ($byCategory as $category => $catArticles) {
            Cache::put(
                self::PREFIX . 'kb:category:' . $category,
                $catArticles->toArray(),
                now()->addMinutes(self::TTL_KNOWLEDGE_BASE)
            );
        }

        // Cache keyword index for AI matching
        $keywordIndex = [];
        foreach ($articles as $article) {
            if (!empty($article->trigger_keywords) && is_array($article->trigger_keywords)) {
                foreach ($article->trigger_keywords as $keyword) {
                    $normalizedKeyword = strtolower(trim($keyword));
                    if (!isset($keywordIndex[$normalizedKeyword])) {
                        $keywordIndex[$normalizedKeyword] = [];
                    }
                    $keywordIndex[$normalizedKeyword][] = $article->id;
                }
            }
        }
        Cache::put(self::PREFIX . 'kb:keyword_index', $keywordIndex, now()->addMinutes(self::TTL_KNOWLEDGE_BASE));

        // Cache total count
        Cache::put(self::PREFIX . 'kb:count', count($allArticles), now()->addMinutes(self::TTL_KNOWLEDGE_BASE));
    }

    /**
     * Cache system settings grouped by category.
     */
    public function warmSettings(): void
    {
        $settings = Setting::all();

        // Cache all settings as key-value pairs
        $allSettings = [];
        foreach ($settings as $setting) {
            $allSettings[$setting->key] = $setting->value;
        }
        Cache::put(self::PREFIX . 'settings:all', $allSettings, now()->addMinutes(self::TTL_SETTINGS));

        // Cache settings grouped by group
        $grouped = $settings->groupBy('group');
        foreach ($grouped as $group => $groupSettings) {
            if ($group === null) {
                continue;
            }
            $groupData = [];
            foreach ($groupSettings as $setting) {
                $groupData[$setting->key] = $setting->value;
            }
            Cache::put(
                self::PREFIX . 'settings:group:' . $group,
                $groupData,
                now()->addMinutes(self::TTL_SETTINGS)
            );
        }

        // Cache specific high-use settings individually
        $criticalKeys = [
            'chat Welcome Message',
            'chat offline_message',
            'chat max_file_size',
            'chat allowed_file_types',
            'system maintenance_mode',
        ];

        foreach ($criticalKeys as $key) {
            $setting = $settings->firstWhere('key', $key);
            if ($setting) {
                Cache::put(
                    self::PREFIX . 'setting:' . $key,
                    $setting->value,
                    now()->addMinutes(self::TTL_SETTINGS)
                );
            }
        }
    }

    /**
     * Cache available agents per department for quick assignment.
     */
    public function warmAgentAvailability(): void
    {
        $departments = Department::where('is_active', true)->pluck('id');

        foreach ($departments as $deptId) {
            $availableAgents = User::where('role', 'agent')
                ->where('department_id', $deptId)
                ->where('is_active', true)
                ->where('status', 'online')
                ->select('id', 'name', 'avatar')
                ->get()
                ->toArray();

            Cache::put(
                self::PREFIX . 'agents:available:dept:' . $deptId,
                $availableAgents,
                now()->addMinutes(self::TTL_AGENT_AVAILABILITY)
            );

            // Cache total agent count for department
            $totalAgents = User::where('role', 'agent')
                ->where('department_id', $deptId)
                ->where('is_active', true)
                ->count();

            Cache::put(
                self::PREFIX . 'agents:count:dept:' . $deptId,
                $totalAgents,
                now()->addMinutes(self::TTL_AGENT_AVAILABILITY)
            );
        }

        // Cache global agent count
        $globalOnlineCount = User::where('role', 'agent')
            ->where('is_active', true)
            ->where('status', 'online')
            ->count();

        Cache::put(
            self::PREFIX . 'agents:online:global',
            $globalOnlineCount,
            now()->addMinutes(self::TTL_AGENT_AVAILABILITY)
        );
    }

    /**
     * Clear all application caches.
     */
    public function clearAllCaches(): void
    {
        $keys = [
            self::PREFIX . 'departments:active',
            self::PREFIX . 'departments:count',
            self::PREFIX . 'kb:all',
            self::PREFIX . 'kb:count',
            self::PREFIX . 'kb:keyword_index',
            self::PREFIX . 'settings:all',
            self::PREFIX . 'agents:online:global',
            self::PREFIX . 'last_warmed',
        ];

        // Clear main keys
        foreach ($keys as $key) {
            Cache::forget($key);
        }

        // Clear department-specific caches
        $departments = Department::pluck('id');
        foreach ($departments as $deptId) {
            Cache::forget(self::PREFIX . 'department:' . $deptId);
            Cache::forget(self::PREFIX . 'kb:department:' . $deptId);
            Cache::forget(self::PREFIX . 'agents:available:dept:' . $deptId);
            Cache::forget(self::PREFIX . 'agents:count:dept:' . $deptId);
        }

        // Clear category caches
        $categories = KnowledgeBase::distinct()->pluck('category')->filter();
        foreach ($categories as $category) {
            Cache::forget(self::PREFIX . 'kb:category:' . $category);
        }

        // Clear setting group caches
        $groups = Setting::distinct()->pluck('group')->filter();
        foreach ($groups as $group) {
            Cache::forget(self::PREFIX . 'settings:group:' . $group);
        }

        // Clear individual setting caches
        $settings = Setting::pluck('key');
        foreach ($settings as $key) {
            Cache::forget(self::PREFIX . 'setting:' . $key);
        }
    }

    /**
     * Get cache hit/miss statistics and warming status.
     */
    public function getCacheStats(): array
    {
        $lastWarmed = Cache::get(self::PREFIX . 'last_warmed');

        // Estimate cache freshness
        $cacheStatus = 'unknown';
        $stalenessMinutes = null;

        if ($lastWarmed) {
            $warmedAt = Carbon::parse($lastWarmed);
            $stalenessMinutes = (int) Carbon::now()->diffInMinutes($warmedAt);

            $cacheStatus = match (true) {
                $stalenessMinutes < 10 => 'fresh',
                $stalenessMinutes < 60 => 'acceptable',
                $stalenessMinutes < 180 => 'stale',
                default => 'expired',
            };
        }

        // Check individual cache pools
        $poolStatus = [
            'departments' => Cache::has(self::PREFIX . 'departments:active'),
            'knowledge_base' => Cache::has(self::PREFIX . 'kb:all'),
            'settings' => Cache::has(self::PREFIX . 'settings:all'),
            'agent_availability' => Cache::has(self::PREFIX . 'agents:online:global'),
        ];

        $warmedPools = count(array_filter($poolStatus));
        $totalPools = count($poolStatus);

        // Estimate memory usage of cached data
        $estimatedSizeKb = 0;
        foreach ($poolStatus as $pool => $exists) {
            if ($exists) {
                $key = match ($pool) {
                    'departments' => self::PREFIX . 'departments:active',
                    'knowledge_base' => self::PREFIX . 'kb:all',
                    'settings' => self::PREFIX . 'settings:all',
                    'agent_availability' => self::PREFIX . 'agents:online:global',
                    default => '',
                };
                $data = Cache::get($key);
                if ($data !== null) {
                    $estimatedSizeKb += (int) (strlen(serialize($data)) / 1024);
                }
            }
        }

        return [
            'last_warmed' => $lastWarmed,
            'cache_status' => $cacheStatus,
            'staleness_minutes' => $stalenessMinutes,
            'pools' => $poolStatus,
            'warmed_pools' => $warmedPools,
            'total_pools' => $totalPools,
            'warming_progress_pct' => $totalPools > 0 ? round(($warmedPools / $totalPools) * 100, 1) : 0,
            'estimated_cache_size_kb' => $estimatedSizeKb,
            'recommended_refresh_interval_minutes' => self::TTL_AGENT_AVAILABILITY,
        ];
    }
}
