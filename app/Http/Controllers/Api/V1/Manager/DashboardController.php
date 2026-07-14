<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Manager;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Queue;
use App\Models\Rating;
use App\Models\User;
use App\Services\Analytics\DashboardStatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Manager dashboard controller — operational overview for department managers.
 *
 * Provides aggregated conversation metrics, agent availability summary,
 * queue statistics, and recent customer satisfaction ratings to give
 * managers real-time visibility into their department's performance.
 */
final class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardStatsService $statsService,
    ) {}

    /**
     * Get the manager dashboard data.
     *
     * Returns four data clusters:
     * 1. Conversations grouped by status (pending, queued, active, closed)
     * 2. Agent status summary (online, away, busy, offline counts)
     * 3. Queue statistics (average wait time, total in queue)
     * 4. Recent customer ratings (last 10)
     *
     * GET /api/v1/manager/dashboard
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $departmentId = $user->department_id;

        // ── Conversation counts by status ───────────────────────
        $conversationCounts = $this->getConversationCounts($departmentId);

        // ── Agent status summary ────────────────────────────────
        $agentSummary = $this->getAgentStatusSummary($departmentId);

        // ── Queue statistics ────────────────────────────────────
        $queueStats = $this->getQueueStatistics($departmentId);

        // ── Recent ratings (last 10) ────────────────────────────
        $recentRatings = $this->getRecentRatings($departmentId);

        return response()->json([
            'success' => true,
            'data' => [
                'conversations' => $conversationCounts,
                'agents' => $agentSummary,
                'queue' => $queueStats,
                'ratings' => $recentRatings,
            ],
        ]);
    }

    /**
     * Get all agents in the manager's department with their status
     * and active conversation count.
     *
     * GET /api/v1/manager/dashboard/agents
     */
    public function agents(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $departmentId = $user->department_id;

        $agents = User::query()
            ->where('department_id', $departmentId)
            ->where('role', 'agent')
            ->get()
            ->map(function (User $agent): array {
                $activeConversations = Conversation::query()
                    ->where('agent_id', $agent->id)
                    ->whereIn('status', ['active', 'pending', 'transferred'])
                    ->count();

                return [
                    'id' => $agent->id,
                    'name' => $agent->name,
                    'email' => $agent->email,
                    'avatar' => $agent->avatar,
                    'status' => $agent->status,
                    'is_active' => $agent->is_active,
                    'active_conversations' => $activeConversations,
                    'last_login_at' => $agent->last_login_at?->toISOString(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $agents,
        ]);
    }

    /**
     * Get dashboard statistics for a given time window.
     *
     * Delegates to DashboardStatsService which returns aggregated
     * metrics including conversation counts, resolution rates,
     * average response times, and satisfaction scores.
     *
     * GET /api/v1/manager/dashboard/stats?days=7
     */
    public function getStats(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'days' => 'nullable|integer|min:1|max:90',
        ]);

        $days = $validated['days'] ?? 7;

        $stats = $this->statsService->getStats(
            departmentId: $user->department_id,
            days: $days,
        );

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get real-time dashboard statistics with no caching.
     *
     * Intended for polling or WebSocket-backed dashboards that need
     * the freshest numbers possible. The underlying service bypasses
     * any cache layer to return up-to-the-second data.
     *
     * GET /api/v1/manager/dashboard/realtime
     */
    public function getRealtime(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $stats = $this->statsService->getRealtimeStats(
            departmentId: $user->department_id,
        );

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get trend data for the dashboard charts.
     *
     * Returns daily data points (conversations created, resolved,
     * average response time, satisfaction score) suitable for
     * rendering line or bar charts on the dashboard.
     *
     * GET /api/v1/manager/dashboard/trends?days=7
     */
    public function getTrends(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'days' => 'nullable|integer|min:1|max:90',
        ]);

        $days = $validated['days'] ?? 7;

        $trends = $this->statsService->getTrends(
            departmentId: $user->department_id,
            days: $days,
        );

        return response()->json([
            'success' => true,
            'data' => $trends,
        ]);
    }

    /**
     * Get conversation counts grouped by status for a department.
     *
     * Uses a single grouped query for efficiency rather than multiple
     * count queries. Returns zero-valued keys for empty statuses so
     * the frontend always receives a consistent shape.
     */
    private function getConversationCounts(?int $departmentId): array
    {
        $counts = Conversation::query()
            ->when($departmentId !== null, fn ($query) => $query->where('department_id', $departmentId))
            ->whereIn('status', ['pending', 'queued', 'active', 'closed'])
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        return [
            'pending' => $counts['pending'] ?? 0,
            'queued' => $counts['queued'] ?? 0,
            'active' => $counts['active'] ?? 0,
            'closed' => $counts['closed'] ?? 0,
        ];
    }

    /**
     * Get agent status distribution for a department.
     *
     * Returns counts for each status value (online, away, busy, offline)
     * plus the total agent count for the department.
     */
    private function getAgentStatusSummary(?int $departmentId): array
    {
        $counts = User::query()
            ->where('department_id', $departmentId)
            ->where('role', 'agent')
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $total = array_sum($counts);

        return [
            'online' => $counts['online'] ?? 0,
            'away' => $counts['away'] ?? 0,
            'busy' => $counts['busy'] ?? 0,
            'offline' => $counts['offline'] ?? 0,
            'total' => $total,
        ];
    }

    /**
     * Get queue statistics for a department.
     *
     * Calculates average wait time across all waiting queue entries
     * and the total number of conversations currently waiting.
     */
    private function getQueueStatistics(?int $departmentId): array
    {
        $waitingQueues = Queue::query()
            ->when($departmentId !== null, fn ($query) => $query->where('department_id', $departmentId))
            ->where('status', 'waiting')
            ->get();

        $totalInQueue = $waitingQueues->count();
        $averageWaitSeconds = $totalInQueue > 0
            ? (int) round($waitingQueues->avg('estimated_wait_seconds'))
            : 0;

        return [
            'total_in_queue' => $totalInQueue,
            'average_wait_seconds' => $averageWaitSeconds,
        ];
    }

    /**
     * Get the most recent customer ratings for conversations in a department.
     *
     * Returns the last 10 ratings with conversation and customer context,
     * ordered by creation date descending.
     */
    private function getRecentRatings(?int $departmentId): array
    {
        return Rating::query()
            ->with([
                'conversation:id,uuid,customer_id,department_id,status',
                'creator:id,name,avatar',
            ])
            ->whereHas('conversation', function ($query) use ($departmentId): void {
                if ($departmentId !== null) {
                    $query->where('department_id', $departmentId);
                }
            })
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn (Rating $rating) => [
                'id' => $rating->id,
                'rating' => $rating->rating,
                'label' => $rating->rating_label,
                'feedback' => $rating->feedback,
                'complaint' => $rating->complaint,
                'created_at' => $rating->created_at?->toISOString(),
                'conversation' => $rating->conversation !== null
                    ? [
                        'id' => $rating->conversation->id,
                        'uuid' => $rating->conversation->uuid,
                        'status' => $rating->conversation->status,
                    ]
                    : null,
                'customer' => $rating->creator !== null
                    ? [
                        'id' => $rating->creator->id,
                        'name' => $rating->creator->name,
                        'avatar' => $rating->creator->avatar,
                    ]
                    : null,
            ])
            ->toArray();
    }
}
