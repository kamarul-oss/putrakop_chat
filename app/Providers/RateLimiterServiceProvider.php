<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

/**
 * Rate Limiter Service Provider.
 *
 * SECURITY: Defines rate limits for different route groups to prevent abuse.
 * - agent-faq: 30 requests per minute per user
 * - manager-faq: 60 requests per minute per user
 * - ai-chat: 20 requests per minute per user
 */
final class RateLimiterServiceProvider extends ServiceProvider
{
    /**
     * Register rate limiter configurations.
     */
    public function boot(): void
    {
        $this->configureRateLimiters();
    }

    /**
     * Configure the application rate limiters.
     */
    private function configureRateLimiters(): void
    {
        // Agent FAQ management — 30 requests per minute
        RateLimiter::for('agent-faq', function (Request $request): Limit {
            return Limit::perMinute(30)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'message' => 'Too many requests. Please wait before trying again.',
                        'retry_after' => $headers['Retry-After'] ?? 60,
                    ], 429, $headers);
                });
        });

        // Manager FAQ management — 60 requests per minute (higher for approval workflows)
        RateLimiter::for('manager-faq', function (Request $request): Limit {
            return Limit::perMinute(60)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'message' => 'Too many requests. Please wait before trying again.',
                        'retry_after' => $headers['Retry-After'] ?? 60,
                    ], 429, $headers);
                });
        });

        // AI Chat — 20 requests per minute per user
        RateLimiter::for('ai-chat', function (Request $request): Limit {
            return Limit::perMinute(20)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'message' => 'Too many chat requests. Please wait before trying again.',
                        'retry_after' => $headers['Retry-After'] ?? 60,
                    ], 429, $headers);
                });
        });

        // General API — 60 requests per minute
        RateLimiter::for('api', function (Request $request): Limit {
            return Limit::perMinute(60)
                ->by($request->user()?->id ?: $request->ip());
        });

        // Login — 5 attempts per minute
        RateLimiter::for('login', function (Request $request): Limit {
            return Limit::perMinute(5)
                ->by($request->input('email') . '|' . $request->ip());
        });
    }
}
