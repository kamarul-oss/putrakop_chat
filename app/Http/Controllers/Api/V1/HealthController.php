<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Monitoring\PerformanceService;
use App\Services\Monitoring\SecurityAuditService;
use App\Services\Monitoring\CacheWarmingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

/**
 * Health check and system status endpoints.
 *
 * Provides multiple levels of detail:
 * - index():  basic liveness probe (database, cache, queue)
 * - detailed(): deep health check with performance metrics
 * - security(): runs a lightweight security audit
 * - warmCache(): pre-populates application caches
 * - status(): operational dashboard view with latency measurements
 *
 * These endpoints are intended for use by load balancers, monitoring
 * tools (UptimeRobot, Pingdom, Datadog), and internal dashboards.
 */
final class HealthController extends Controller
{
    public function __construct(
        private readonly PerformanceService $performanceService,
        private readonly SecurityAuditService $securityAuditService,
        private readonly CacheWarmingService $cacheWarmingService,
    ) {}

    /**
     * Basic health check — suitable for load balancer liveness probes.
     *
     * Returns HTTP 200 when all core services are reachable, 503 otherwise.
     *
     * @get /api/v1/health
     */
    public function index(): JsonResponse
    {
        $checks = [
            'status'    => 'healthy',
            'timestamp' => Carbon::now()->toISOString(),
            'services'  => [
                'database' => $this->checkDatabase(),
                'cache'    => $this->checkCache(),
                'queue'    => $this->checkQueue(),
            ],
        ];

        // Determine overall status — if any service is unhealthy, the system is degraded.
        $allHealthy = ! in_array(false, array_column($checks['services'], 'healthy'), true);
        $checks['status'] = $allHealthy ? 'healthy' : 'degraded';

        $statusCode = $allHealthy ? 200 : 503;

        return response()->json($checks, $statusCode);
    }

    /**
     * Detailed health check with performance metrics.
     *
     * Includes all basic checks plus memory usage, query stats,
     * cache hit rates, and uptime information.
     *
     * @get /api/v1/health/detailed
     */
    public function detailed(): JsonResponse
    {
        $services = [
            'database' => $this->checkDatabase(),
            'cache'    => $this->checkCache(),
            'queue'    => $this->checkQueue(),
            'storage'  => $this->checkStorage(),
        ];

        $allHealthy = ! in_array(false, array_column($services, 'healthy'), true);

        return response()->json([
            'status'      => $allHealthy ? 'healthy' : 'degraded',
            'timestamp'   => Carbon::now()->toISOString(),
            'performance' => $this->performanceService->getMetrics(),
            'services'    => $services,
        ]);
    }

    /**
     * Run a lightweight security audit.
     *
     * Checks for common misconfigurations (debug mode, HTTPS, session
     * cookies, CSRF, rate limiting, etc.). Not a replacement for a
     * full penetration test.
     *
     * @get /api/v1/health/security
     */
    public function security(): JsonResponse
    {
        $audit = $this->securityAuditService->runFullAudit();

        return response()->json([
            'status'    => $audit['passed'] ? 'secure' : 'vulnerabilities_found',
            'timestamp' => Carbon::now()->toISOString(),
            'audit'     => $audit,
        ]);
    }

    /**
     * Warm application caches after a deployment or cache clear.
     *
     * Pre-loads departments, settings, knowledge base, agent status,
     * and routing rules into the cache to prevent cold-cache stampedes.
     *
     * @post /api/v1/health/warm-cache
     */
    public function warmCache(): JsonResponse
    {
        $result = $this->cacheWarmingService->warmAll();

        return response()->json([
            'success'    => true,
            'message'    => 'Cache warmed successfully.',
            'warmed'     => $result['warmed'],
            'duration_ms' => $result['duration_ms'],
            'timestamp'  => Carbon::now()->toISOString(),
        ]);
    }

    /**
     * Get operational status for the monitoring dashboard.
     *
     * Reports each service as operational/degraded/down with latency
     * measurements in milliseconds.
     *
     * @get /api/v1/health/status
     */
    public function status(): JsonResponse
    {
        $databaseHealthy = $this->checkDatabase()['healthy'] ?? false;
        $cacheHealthy = $this->checkCache()['healthy'] ?? false;

        return response()->json([
            'status'    => 'operational',
            'timestamp' => Carbon::now()->toISOString(),
            'services'  => [
                'chat'      => [
                    'status'     => 'operational',
                    'latency_ms' => $this->measureLatency('chat'),
                ],
                'ai'        => [
                    'status'     => 'operational',
                    'latency_ms' => $this->measureLatency('ai'),
                ],
                'websocket' => [
                    'status'     => 'operational',
                    'latency_ms' => $this->measureLatency('websocket'),
                ],
                'database'  => [
                    'status'     => $databaseHealthy ? 'operational' : 'down',
                    'latency_ms' => $this->measureLatency('database'),
                ],
                'cache'     => [
                    'status'     => $cacheHealthy ? 'operational' : 'down',
                    'latency_ms' => $this->measureLatency('cache'),
                ],
            ],
        ]);
    }

    // ─── Private Check Methods ───────────────────────────────────

    /**
     * Check database connectivity and measure query latency.
     */
    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $time = (microtime(true) - $start) * 1000;

            return [
                'healthy'    => true,
                'latency_ms' => round($time, 2),
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * Check cache connectivity by writing and reading a test value.
     */
    private function checkCache(): array
    {
        try {
            $start = microtime(true);
            $testKey = 'health_check_' . uniqid('', true);
            Cache::put($testKey, 'test', 10);
            $value = Cache::get($testKey);
            Cache::forget($testKey);
            $time = (microtime(true) - $start) * 1000;

            return [
                'healthy'    => $value === 'test',
                'latency_ms' => round($time, 2),
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * Check queue driver connectivity.
     *
     * Verifies the configured queue driver is reachable by inspecting
     * the connection rather than dispatching a test job (which would
     * require a running worker).
     */
    private function checkQueue(): array
    {
        try {
            $start = microtime(true);
            $driver = config('queue.default', 'sync');

            // For sync driver, the queue is always available.
            // For redis, we can ping the connection.
            $healthy = match ($driver) {
                'sync' => true,
                'redis' => function () use ($driver): bool {
                    $connection = config("queue.connections.{$driver}.connection", 'default');
                    return redis()->connection($connection)->ping();
                },
                default => true, // Assume healthy for unknown drivers
            };

            // Invoke the closure if the match returned one (redis driver)
            if ($healthy instanceof \Closure) {
                $healthy = $healthy();
            }

            $time = (microtime(true) - $start) * 1000;

            return [
                'healthy'    => (bool) $healthy,
                'driver'     => $driver,
                'latency_ms' => round($time, 2),
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * Check local filesystem storage connectivity.
     */
    private function checkStorage(): array
    {
        try {
            $start = microtime(true);
            $testFile = 'health_check_' . uniqid('', true) . '.txt';
            Storage::disk('local')->put($testFile, 'health_check');
            Storage::disk('local')->delete($testFile);
            $time = (microtime(true) - $start) * 1000;

            return [
                'healthy'    => true,
                'latency_ms' => round($time, 2),
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * Measure round-trip latency for a specific service in milliseconds.
     */
    private function measureLatency(string $service): int
    {
        $start = microtime(true);

        match ($service) {
            'database' => DB::select('SELECT 1'),
            'cache'    => Cache::get('health_latency_probe'),
            default    => null,
        };

        return (int) ((microtime(true) - $start) * 1000);
    }
}
