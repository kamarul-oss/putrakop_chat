<?php
declare(strict_types=1);

namespace App\Services\Analytics;

use App\Enums\ConversationStatus;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\Rating;
use App\Models\Queue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Provides real-time and historical statistics for the manager dashboard.
 */
final class DashboardStatsService
{
    private const CACHE_TTL = 60; // seconds

    /**
     * Get comprehensive dashboard stats for a department.
     */
    public function getDepartmentStats(int $departmentId, int $days = 7): array
    {
        $cacheKey = "dashboard_stats_{$departmentId}_{$days}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($departmentId, $days) {
            return [
                'overview' => $this->getOverview($departmentId, $days),
                'realtime' => $this->getRealtimeStats($departmentId),
                'trends' => $this->getTrends($departmentId, $days),
                'agent_performance' => $this->getAgentPerformance($departmentId, $days),
                'conversation_metrics' => $this->getConversationMetrics($departmentId, $days),
                'satisfaction' => $this->getSatisfactionMetrics($departmentId, $days),
            ];
        });
    }

    /**
     * Get real-time statistics (no cache).
     */
    public function getRealtimeStats(int $departmentId): array
    {
        $now = Carbon::now();
        $today = $now->copy()->startOfDay();

        return [
            'active_conversations' => Conversation::where('department_id', $departmentId)
                ->where('status', ConversationStatus::Active->value)
                ->count(),
            'pending_queue' => Queue::where('department_id', $departmentId)
                ->where('status', 'waiting')
                ->count(),
            'agents_online' => User::where('department_id', $departmentId)
                ->where('role', 'agent')
                ->where('status', 'online')
                ->where('is_active', true)
                ->count(),
            'agents_away' => User::where('department_id', $departmentId)
                ->where('role', 'agent')
                ->where('status', 'away')
                ->count(),
            'agents_busy' => User::where('department_id', $departmentId)
                ->where('role', 'agent')
                ->where('status', 'busy')
                ->count(),
            'today_conversations' => Conversation::where('department_id', $departmentId)
                ->where('created_at', '>=', $today)
                ->count(),
            'today_messages' => Message::whereHas('conversation', function ($q) use ($departmentId) {
                    $q->where('department_id', $departmentId);
                })
                ->where('created_at', '>=', $today)
                ->count(),
        ];
    }

    /**
     * Get overview statistics.
     */
    private function getOverview(int $departmentId, int $days): array
    {
        $startDate = Carbon::now()->subDays($days);

        return [
            'total_conversations' => Conversation::where('department_id', $departmentId)
                ->where('created_at', '>=', $startDate)
                ->count(),
            'total_messages' => Message::whereHas('conversation', function ($q) use ($departmentId) {
                    $q->where('department_id', $departmentId);
                })
                ->where('created_at', '>=', $startDate)
                ->count(),
            'total_ratings' => Rating::whereHas('conversation', function ($q) use ($departmentId) {
                    $q->where('department_id', $departmentId);
                })
                ->where('created_at', '>=', $startDate)
                ->count(),
            'average_rating' => $this->getAverageRating($departmentId, $days),
            'average_wait_time' => $this->getAverageWaitTime($departmentId, $days),
            'average_response_time' => $this->getAverageResponseTime($departmentId, $days),
        ];
    }

    /**
     * Get trend data for charts.
     */
    private function getTrends(int $departmentId, int $days): array
    {
        $startDate = Carbon::now()->subDays($days);
        
        $conversationsByDay = Conversation::where('department_id', $departmentId)
            ->where('created_at', '>=', $startDate)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $messagesByDay = Message::whereHas('conversation', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            })
            ->where('created_at', '>=', $startDate)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $ratingsByDay = Rating::whereHas('conversation', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            })
            ->where('created_at', '>=', $startDate)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('AVG(rating) as average'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'conversations' => $conversationsByDay,
            'messages' => $messagesByDay,
            'ratings' => $ratingsByDay,
        ];
    }

    /**
     * Get agent performance metrics.
     */
    private function getAgentPerformance(int $departmentId, int $days): array
    {
        $startDate = Carbon::now()->subDays($days);
        
        return User::where('department_id', $departmentId)
            ->where('role', 'agent')
            ->where('is_active', true)
            ->get()
            ->map(function (User $agent) use ($startDate) {
                $conversations = Conversation::where('agent_id', $agent->id)
                    ->where('created_at', '>=', $startDate);
                
                $closedConversations = (clone $conversations)
                    ->where('status', ConversationStatus::Closed->value)
                    ->count();

                $totalConversations = (clone $conversations)->count();
                
                $avgRating = Rating::whereHas('conversation', function ($q) use ($agent) {
                        $q->where('agent_id', $agent->id);
                    })
                    ->where('created_at', '>=', $startDate)
                    ->avg('rating');

                return [
                    'agent_id' => $agent->id,
                    'name' => $agent->name,
                    'status' => $agent->status,
                    'total_conversations' => $totalConversations,
                    'closed_conversations' => $closedConversations,
                    'average_rating' => round($avgRating ?? 0, 2),
                    'active_now' => Conversation::where('agent_id', $agent->id)
                        ->where('status', ConversationStatus::Active->value)
                        ->count(),
                ];
            })
            ->toArray();
    }

    /**
     * Get conversation metrics.
     */
    private function getConversationMetrics(int $departmentId, int $days): array
    {
        $startDate = Carbon::now()->subDays($days);
        
        $statusCounts = Conversation::where('department_id', $departmentId)
            ->where('created_at', '>=', $startDate)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        return [
            'by_status' => $statusCounts,
            'completion_rate' => $this->calculateCompletionRate($departmentId, $days),
            'transfer_rate' => $this->calculateTransferRate($departmentId, $days),
        ];
    }

    /**
     * Get satisfaction metrics.
     */
    private function getSatisfactionMetrics(int $departmentId, int $days): array
    {
        $startDate = Carbon::now()->subDays($days);
        
        $ratings = Rating::whereHas('conversation', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            })
            ->where('created_at', '>=', $startDate)
            ->get();

        $distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        foreach ($ratings as $rating) {
            $distribution[$rating->rating] = ($distribution[$rating->rating] ?? 0) + 1;
        }

        return [
            'total_ratings' => $ratings->count(),
            'average' => round($ratings->avg('rating') ?? 0, 2),
            'distribution' => $distribution,
            'satisfaction_rate' => $this->calculateSatisfactionRate($ratings),
        ];
    }

    /**
     * Get average rating for department.
     */
    private function getAverageRating(int $departmentId, int $days): float
    {
        $startDate = Carbon::now()->subDays($days);
        
        return round(Rating::whereHas('conversation', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            })
            ->where('created_at', '>=', $startDate)
            ->avg('rating') ?? 0, 2);
    }

    /**
     * Get average wait time in seconds.
     */
    private function getAverageWaitTime(int $departmentId, int $days): int
    {
        $startDate = Carbon::now()->subDays($days);
        
        return Conversation::where('department_id', $departmentId)
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('agent_id')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at)) as avg_wait'))
            ->value('avg_wait') ?? 0;
    }

    /**
     * Get average response time in seconds.
     */
    private function getAverageResponseTime(int $departmentId, int $days): int
    {
        $startDate = Carbon::now()->subDays($days);
        
        // Calculate time between customer message and first agent response
        $conversations = Conversation::where('department_id', $departmentId)
            ->where('created_at', '>=', $startDate)
            ->where('status', ConversationStatus::Closed->value)
            ->with(['messages' => function ($q) {
                $q->orderBy('created_at');
            }])
            ->get();

        $totalResponseTime = 0;
        $count = 0;

        foreach ($conversations as $conversation) {
            $messages = $conversation->messages;
            if ($messages->count() < 2) continue;

            $customerMessage = $messages->firstWhere('sender_type', 'customer');
            $agentMessage = $messages->firstWhere('sender_type', 'agent');

            if ($customerMessage && $agentMessage) {
                $totalResponseTime += $agentMessage->created_at->diffInSeconds($customerMessage->created_at);
                $count++;
            }
        }

        return $count > 0 ? (int) ($totalResponseTime / $count) : 0;
    }

    /**
     * Calculate completion rate.
     */
    private function calculateCompletionRate(int $departmentId, int $days): float
    {
        $startDate = Carbon::now()->subDays($days);
        
        $total = Conversation::where('department_id', $departmentId)
            ->where('created_at', '>=', $startDate)
            ->count();
        
        $closed = Conversation::where('department_id', $departmentId)
            ->where('created_at', '>=', $startDate)
            ->where('status', ConversationStatus::Closed->value)
            ->count();

        return $total > 0 ? round(($closed / $total) * 100, 2) : 0;
    }

    /**
     * Calculate transfer rate.
     */
    private function calculateTransferRate(int $departmentId, int $days): float
    {
        $startDate = Carbon::now()->subDays($days);
        
        $total = Conversation::where('department_id', $departmentId)
            ->where('created_at', '>=', $startDate)
            ->count();
        
        $transferred = Conversation::where('department_id', $departmentId)
            ->where('created_at', '>=', $startDate)
            ->where('status', ConversationStatus::Transferred->value)
            ->count();

        return $total > 0 ? round(($transferred / $total) * 100, 2) : 0;
    }

    /**
     * Calculate satisfaction rate (4-5 stars = satisfied).
     */
    private function calculateSatisfactionRate($ratings): float
    {
        if ($ratings->isEmpty()) return 0;
        
        $satisfied = $ratings->filter(fn($r) => $r->rating >= 4)->count();
        return round(($satisfied / $ratings->count()) * 100, 2);
    }
}
