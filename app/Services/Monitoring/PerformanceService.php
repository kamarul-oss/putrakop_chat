<?php

declare(strict_types=1);

namespace App\Services\Monitoring;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Collects and reports application performance metrics.
 *
 * Tracks query counts, cache hit rates, memory usage, and response
 * timings for monitoring dashboards and health-check endpoints.
 */
final class PerformanceService
{
    /**
     * Aggregate all performance metrics into a single snapshot.
     *
     * @return array{
     *     queries: array{count: int, slow_queries: int},
     *     memory: array{peak_mb: float, current_mb: float},
     *     cache: array{hits: int, misses: int, hit_rate: float},
     *     uptime: array{seconds: int, human: string},
     *     php_version: string,
     *     laravel_version: string,
     * }
     */
    public function getMetrics(): array
    {
        $memoryUsage = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);

        return [
            'queries' => $this->getQueryMetrics(),
            'memory' => [
                'peak_mb'    => round($peakMemory / 1024 / 1024, 2),
                'current_mb' => round($memoryUsage / 1024 / 1024, 2),
            ],
            'cache' => $this->getCacheMetrics(),
            'uptime' => $this->getUptimeMetrics(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ];
    }

    /**
     * Get database query performance metrics.
     */
    public function getQueryMetrics(): array
    {
        try {
            $queryLog = DB::getQueryLog();
            $totalQueries = count($queryLog);

            $slowQueries = (int) collect($queryLog)
                ->filter(fn (array $query): bool => ($query['time'] ?? 0) > 100)
                ->count();

            return [
                'count'        => $totalQueries,
                'slow_queries' => $slowQueries,
            ];
        } catch (\Throwable) {
            return [
                'count'        => 0,
                'slow_queries' => 0,
            ];
        }
    }

    /**
     * Get cache performance metrics.
     *
     * Uses a simple counter-key strategy; not atomic but acceptable
     * for a monitoring/observability endpoint.
     */
    public function getCacheMetrics(): array
    {
        try {
            $hits = (int) Cache::get('perf_cache_hits', 0);
            $misses = (int) Cache::get('perf_cache_misses', 0);
            $total = $hits + $misses;

            return [
                'hits'     => $hits,
                'misses'   => $misses,
                'hit_rate' => $total > 0 ? round($hits / $total * 100, 2) : 0.0,
            ];
        } catch (\Throwable) {
            return [
                'hits'     => 0,
                'misses'   => 0,
                'hit_rate' => 0.0,
            ];
        }
    }

    /**
     * Get application uptime metrics.
     */
    public function getUptimeMetrics(): array
    {
        $startTime = (float) Cache::get('app_start_time', (string) time());
        $uptimeSeconds = max(0, (int) time() - (int) $startTime);

        return [
            'seconds' => $uptimeSeconds,
            'human'   => $this->formatDuration($uptimeSeconds),
        ];
    }

    /**
     * Convert seconds into a human-readable duration string.
     */
    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds}s";
        }

        $minutes = intdiv($seconds, 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes < 60) {
            return "{$minutes}m {$remainingSeconds}s";
        }

        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        if ($hours < 24) {
            return "{$hours}h {$remainingMinutes}m";
        }

        $days = intdiv($hours, 24);
        $remainingHours = $hours % 24;

        return "{$days}d {$remainingHours}h";
    }
}
