<?php
declare(strict_types=1);

namespace App\Services\Analytics;

use App\Enums\ConversationStatus;
use App\Models\Conversation;
use App\Models\Rating;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Generates detailed analytics reports for departments and agents.
 */
final class ReportGenerator
{
    /**
     * Generate a daily report for a specific department.
     *
     * @param  int  $departmentId
     * @param  string|null  $date  Date string in Y-m-d format. Defaults to yesterday.
     * @return array
     */
    public function generateDailyReport(int $departmentId, ?string $date = null): array
    {
        $reportDate = $date ? Carbon::parse($date) : Carbon::yesterday();
        $startOfDay = $reportDate->copy()->startOfDay();
        $endOfDay = $reportDate->copy()->endOfDay();

        $conversations = Conversation::where('department_id', $departmentId)
            ->whereBetween('created_at', [$startOfDay, $endOfDay]);

        $totalConversations = (clone $conversations)->count();
        $closedConversations = (clone $conversations)
            ->where('status', ConversationStatus::Closed->value)
            ->count();
        $transferredConversations = (clone $conversations)
            ->where('status', ConversationStatus::Transferred->value)
            ->count();

        $totalMessages = $this->getMessageCount($departmentId, $startOfDay, $endOfDay);

        $ratings = $this->getRatings($departmentId, $startOfDay, $endOfDay);
        $averageRating = $ratings->isNotEmpty() ? round($ratings->avg('rating'), 2) : 0;

        $agentActivity = $this->getAgentActivityForPeriod($departmentId, $startOfDay, $endOfDay);

        return [
            'report_type' => 'daily',
            'department_id' => $departmentId,
            'date' => $reportDate->toDateString(),
            'summary' => [
                'total_conversations' => $totalConversations,
                'closed_conversations' => $closedConversations,
                'transferred_conversations' => $transferredConversations,
                'total_messages' => $totalMessages,
                'total_ratings' => $ratings->count(),
                'average_rating' => $averageRating,
                'completion_rate' => $totalConversations > 0
                    ? round(($closedConversations / $totalConversations) * 100, 2)
                    : 0,
            ],
            'agent_activity' => $agentActivity,
            'hourly_breakdown' => $this->getHourlyBreakdown($departmentId, $startOfDay, $endOfDay),
            'generated_at' => Carbon::now()->toDateTimeString(),
        ];
    }

    /**
     * Generate a weekly report for the past 7 days.
     *
     * @param  int  $departmentId
     * @return array
     */
    public function generateWeeklyReport(int $departmentId): array
    {
        $endDate = Carbon::yesterday();
        $startDate = $endDate->copy()->subDays(6);

        $dailyBreakdown = [];
        $period = Carbon::parse($startDate)->copy();

        while ($period->lte($endDate)) {
            $dailyBreakdown[] = $this->generateDailyReport(
                $departmentId,
                $period->toDateString()
            );
            $period->addDay();
        }

        $startOfWeek = Carbon::parse($startDate)->startOfDay();
        $endOfWeek = Carbon::parse($endDate)->endOfDay();

        $conversations = Conversation::where('department_id', $departmentId)
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek]);

        $totalConversations = (clone $conversations)->count();
        $closedConversations = (clone $conversations)
            ->where('status', ConversationStatus::Closed->value)
            ->count();

        $totalMessages = $this->getMessageCount($departmentId, $startOfWeek, $endOfWeek);

        $ratings = $this->getRatings($departmentId, $startOfWeek, $endOfWeek);
        $averageRating = $ratings->isNotEmpty() ? round($ratings->avg('rating'), 2) : 0;

        $agentPerformance = $this->getAgentPerformanceForPeriod($departmentId, $startOfWeek, $endOfWeek);

        return [
            'report_type' => 'weekly',
            'department_id' => $departmentId,
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'summary' => [
                'total_conversations' => $totalConversations,
                'closed_conversations' => $closedConversations,
                'total_messages' => $totalMessages,
                'total_ratings' => $ratings->count(),
                'average_rating' => $averageRating,
                'completion_rate' => $totalConversations > 0
                    ? round(($closedConversations / $totalConversations) * 100, 2)
                    : 0,
            ],
            'daily_breakdown' => $dailyBreakdown,
            'agent_performance' => $agentPerformance,
            'generated_at' => Carbon::now()->toDateTimeString(),
        ];
    }

    /**
     * Generate a monthly report for a specific month.
     *
     * @param  int  $departmentId
     * @param  int  $month  1-12
     * @param  int  $year
     * @return array
     */
    public function generateMonthlyReport(int $departmentId, int $month, int $year): array
    {
        $startOfMonth = Carbon::create($year, $month, 1)->startOfDay();
        $endOfMonth = $startOfMonth->copy()->endOfMonth()->endOfDay();

        $weeklyBreakdown = [];
        $currentWeekStart = $startOfMonth->copy();

        while ($currentWeekStart->lte($endOfMonth)) {
            $weekEnd = $currentWeekStart->copy()->addDays(6);
            if ($weekEnd->gt($endOfMonth)) {
                $weekEnd = $endOfMonth->copy();
            }

            $weeklyBreakdown[] = [
                'period' => [
                    'start_date' => $currentWeekStart->toDateString(),
                    'end_date' => $weekEnd->toDateString(),
                ],
                'conversations' => Conversation::where('department_id', $departmentId)
                    ->whereBetween('created_at', [$currentWeekStart, $weekEnd])
                    ->count(),
                'messages' => $this->getMessageCount(
                    $departmentId,
                    $currentWeekStart,
                    $weekEnd
                ),
                'ratings_count' => $this->getRatings(
                    $departmentId,
                    $currentWeekStart,
                    $weekEnd
                )->count(),
                'average_rating' => round(
                    $this->getRatings($departmentId, $currentWeekStart, $weekEnd)->avg('rating') ?? 0,
                    2
                ),
            ];

            $currentWeekStart->addDays(7);
        }

        $conversations = Conversation::where('department_id', $departmentId)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth]);

        $totalConversations = (clone $conversations)->count();
        $closedConversations = (clone $conversations)
            ->where('status', ConversationStatus::Closed->value)
            ->count();

        $totalMessages = $this->getMessageCount($departmentId, $startOfMonth, $endOfMonth);

        $ratings = $this->getRatings($departmentId, $startOfMonth, $endOfMonth);
        $averageRating = $ratings->isNotEmpty() ? round($ratings->avg('rating'), 2) : 0;

        $agentPerformance = $this->getAgentPerformanceForPeriod($departmentId, $startOfMonth, $endOfMonth);

        return [
            'report_type' => 'monthly',
            'department_id' => $departmentId,
            'period' => [
                'month' => $month,
                'year' => $year,
                'month_name' => $startOfMonth->monthName,
                'start_date' => $startOfMonth->toDateString(),
                'end_date' => $endOfMonth->toDateString(),
            ],
            'summary' => [
                'total_conversations' => $totalConversations,
                'closed_conversations' => $closedConversations,
                'total_messages' => $totalMessages,
                'total_ratings' => $ratings->count(),
                'average_rating' => $averageRating,
                'completion_rate' => $totalConversations > 0
                    ? round(($closedConversations / $totalConversations) * 100, 2)
                    : 0,
            ],
            'weekly_breakdown' => $weeklyBreakdown,
            'agent_performance' => $agentPerformance,
            'generated_at' => Carbon::now()->toDateTimeString(),
        ];
    }

    /**
     * Generate an individual agent performance report.
     *
     * @param  int  $agentId
     * @param  int  $days
     * @return array
     */
    public function generateAgentReport(int $agentId, int $days = 30): array
    {
        $agent = User::findOrFail($agentId);
        $startDate = Carbon::now()->subDays($days)->startOfDay();

        $conversations = Conversation::where('agent_id', $agentId)
            ->where('created_at', '>=', $startDate);

        $totalConversations = (clone $conversations)->count();
        $closedConversations = (clone $conversations)
            ->where('status', ConversationStatus::Closed->value)
            ->count();

        $totalMessages = Message::where('sender_id', $agentId)
            ->where('sender_type', 'agent')
            ->where('created_at', '>=', $startDate)
            ->count();

        $ratings = Rating::whereHas('conversation', function ($q) use ($agentId) {
            $q->where('agent_id', $agentId);
        })->where('created_at', '>=', $startDate);

        $ratingsCollection = $ratings->get();
        $averageRating = $ratingsCollection->isNotEmpty()
            ? round($ratingsCollection->avg('rating'), 2)
            : 0;

        $averageResponseTime = $this->calculateAgentResponseTime($agentId, $startDate);

        return [
            'report_type' => 'agent',
            'agent' => [
                'id' => $agent->id,
                'name' => $agent->name,
                'email' => $agent->email,
                'status' => $agent->status,
            ],
            'period' => [
                'days' => $days,
                'start_date' => $startDate->toDateString(),
                'end_date' => Carbon::now()->toDateString(),
            ],
            'summary' => [
                'total_conversations' => $totalConversations,
                'closed_conversations' => $closedConversations,
                'total_messages' => $totalMessages,
                'total_ratings' => $ratingsCollection->count(),
                'average_rating' => $averageRating,
                'average_response_time_seconds' => $averageResponseTime,
                'completion_rate' => $totalConversations > 0
                    ? round(($closedConversations / $totalConversations) * 100, 2)
                    : 0,
                'messages_per_conversation' => $totalConversations > 0
                    ? round($totalMessages / $totalConversations, 2)
                    : 0,
            ],
            'daily_activity' => $this->getAgentDailyActivity($agentId, $startDate),
            'rating_distribution' => $this->getRatingDistribution($ratingsCollection),
            'generated_at' => Carbon::now()->toDateTimeString(),
        ];
    }

    /**
     * Generate a comparison report across all departments.
     *
     * @param  int  $days
     * @return array
     */
    public function generateDepartmentComparison(int $days = 7): array
    {
        $startDate = Carbon::now()->subDays($days)->startOfDay();

        $departments = DB::table('departments')
            ->where('is_active', true)
            ->get();

        $departmentStats = [];

        foreach ($departments as $department) {
            $conversations = Conversation::where('department_id', $department->id)
                ->where('created_at', '>=', $startDate);

            $totalConversations = (clone $conversations)->count();
            $closedConversations = (clone $conversations)
                ->where('status', ConversationStatus::Closed->value)
                ->count();

            $totalMessages = Message::whereHas('conversation', function ($q) use ($department) {
                $q->where('department_id', $department->id);
            })->where('created_at', '>=', $startDate)->count();

            $ratings = Rating::whereHas('conversation', function ($q) use ($department) {
                $q->where('department_id', $department->id);
            })->where('created_at', '>=', $startDate);

            $ratingsCollection = $ratings->get();
            $averageRating = $ratingsCollection->isNotEmpty()
                ? round($ratingsCollection->avg('rating'), 2)
                : 0;

            $activeAgents = User::where('department_id', $department->id)
                ->where('role', 'agent')
                ->where('is_active', true)
                ->count();

            $departmentStats[] = [
                'department_id' => $department->id,
                'department_name' => $department->name,
                'total_conversations' => $totalConversations,
                'closed_conversations' => $closedConversations,
                'total_messages' => $totalMessages,
                'total_ratings' => $ratingsCollection->count(),
                'average_rating' => $averageRating,
                'active_agents' => $activeAgents,
                'completion_rate' => $totalConversations > 0
                    ? round(($closedConversations / $totalConversations) * 100, 2)
                    : 0,
                'conversations_per_agent' => $activeAgents > 0
                    ? round($totalConversations / $activeAgents, 2)
                    : 0,
            ];
        }

        // Sort by conversation count descending
        usort($departmentStats, fn(array $a, array $b) =>
            $b['total_conversations'] <=> $a['total_conversations']
        );

        return [
            'report_type' => 'department_comparison',
            'period' => [
                'days' => $days,
                'start_date' => $startDate->toDateString(),
                'end_date' => Carbon::now()->toDateString(),
            ],
            'departments' => $departmentStats,
            'totals' => [
                'total_conversations' => array_sum(array_column($departmentStats, 'total_conversations')),
                'total_messages' => array_sum(array_column($departmentStats, 'total_messages')),
                'total_ratings' => array_sum(array_column($departmentStats, 'total_ratings')),
                'average_rating' => $this->calculateOverallAverageRating($departmentStats),
            ],
            'generated_at' => Carbon::now()->toDateTimeString(),
        ];
    }

    // ─── Private Helper Methods ──────────────────────────────────────────

    /**
     * Get message count for a department within a date range.
     */
    private function getMessageCount(int $departmentId, Carbon $startDate, Carbon $endDate): int
    {
        return Message::whereHas('conversation', function ($q) use ($departmentId) {
            $q->where('department_id', $departmentId);
        })->whereBetween('created_at', [$startDate, $endDate])->count();
    }

    /**
     * Get ratings collection for a department within a date range.
     */
    private function getRatings(int $departmentId, Carbon $startDate, Carbon $endDate): \Illuminate\Database\Eloquent\Collection
    {
        return Rating::whereHas('conversation', function ($q) use ($departmentId) {
            $q->where('department_id', $departmentId);
        })->whereBetween('created_at', [$startDate, $endDate])->get();
    }

    /**
     * Get agent activity for a specific period.
     */
    private function getAgentActivityForPeriod(int $departmentId, Carbon $startDate, Carbon $endDate): array
    {
        return User::where('department_id', $departmentId)
            ->where('role', 'agent')
            ->where('is_active', true)
            ->get()
            ->map(function (User $agent) use ($startDate, $endDate) {
                $conversations = Conversation::where('agent_id', $agent->id)
                    ->whereBetween('created_at', [$startDate, $endDate]);

                $totalConversations = (clone $conversations)->count();
                $closedConversations = (clone $conversations)
                    ->where('status', ConversationStatus::Closed->value)
                    ->count();

                return [
                    'agent_id' => $agent->id,
                    'name' => $agent->name,
                    'total_conversations' => $totalConversations,
                    'closed_conversations' => $closedConversations,
                    'completion_rate' => $totalConversations > 0
                        ? round(($closedConversations / $totalConversations) * 100, 2)
                        : 0,
                ];
            })
            ->toArray();
    }

    /**
     * Get hourly conversation breakdown for a day.
     */
    private function getHourlyBreakdown(int $departmentId, Carbon $startDate, Carbon $endDate): array
    {
        $hourlyData = Conversation::where('department_id', $departmentId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('COUNT(*) as count'))
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->pluck('count', 'hour')
            ->toArray();

        // Fill in missing hours with 0
        $breakdown = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $breakdown[] = [
                'hour' => $hour,
                'label' => sprintf('%02d:00', $hour),
                'conversations' => $hourlyData[$hour] ?? 0,
            ];
        }

        return $breakdown;
    }

    /**
     * Get agent performance metrics for a period.
     */
    private function getAgentPerformanceForPeriod(int $departmentId, Carbon $startDate, Carbon $endDate): array
    {
        return User::where('department_id', $departmentId)
            ->where('role', 'agent')
            ->where('is_active', true)
            ->get()
            ->map(function (User $agent) use ($startDate, $endDate) {
                $conversations = Conversation::where('agent_id', $agent->id)
                    ->whereBetween('created_at', [$startDate, $endDate]);

                $totalConversations = (clone $conversations)->count();
                $closedConversations = (clone $conversations)
                    ->where('status', ConversationStatus::Closed->value)
                    ->count();

                $totalMessages = Message::where('sender_id', $agent->id)
                    ->where('sender_type', 'agent')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count();

                $ratings = Rating::whereHas('conversation', function ($q) use ($agent) {
                    $q->where('agent_id', $agent->id);
                })->whereBetween('created_at', [$startDate, $endDate]);

                $ratingsCollection = $ratings->get();
                $averageRating = $ratingsCollection->isNotEmpty()
                    ? round($ratingsCollection->avg('rating'), 2)
                    : 0;

                return [
                    'agent_id' => $agent->id,
                    'name' => $agent->name,
                    'total_conversations' => $totalConversations,
                    'closed_conversations' => $closedConversations,
                    'total_messages' => $totalMessages,
                    'average_rating' => $averageRating,
                    'completion_rate' => $totalConversations > 0
                        ? round(($closedConversations / $totalConversations) * 100, 2)
                        : 0,
                ];
            })
            ->toArray();
    }

    /**
     * Calculate average response time for an agent.
     */
    private function calculateAgentResponseTime(int $agentId, Carbon $startDate): int
    {
        $conversations = Conversation::where('agent_id', $agentId)
            ->where('created_at', '>=', $startDate)
            ->with(['messages' => function ($q) {
                $q->orderBy('created_at');
            }])
            ->get();

        $totalResponseTime = 0;
        $count = 0;

        foreach ($conversations as $conversation) {
            $messages = $conversation->messages;
            if ($messages->count() < 2) {
                continue;
            }

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
     * Get agent daily activity breakdown.
     */
    private function getAgentDailyActivity(int $agentId, Carbon $startDate): array
    {
        $dailyData = Conversation::where('agent_id', $agentId)
            ->where('created_at', '>=', $startDate)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $dailyData->map(fn($item) => [
            'date' => $item->date,
            'conversations' => $item->count,
        ])->toArray();
    }

    /**
     * Get rating distribution from a ratings collection.
     */
    private function getRatingDistribution(\Illuminate\Database\Eloquent\Collection $ratings): array
    {
        $distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

        foreach ($ratings as $rating) {
            $distribution[$rating->rating] = ($distribution[$rating->rating] ?? 0) + 1;
        }

        return array_map(fn(int $count, int $stars) => [
            'stars' => $stars,
            'count' => $count,
            'percentage' => $ratings->count() > 0
                ? round(($count / $ratings->count()) * 100, 2)
                : 0,
        ], $distribution, array_keys($distribution));
    }

    /**
     * Calculate overall average rating from department stats array.
     */
    private function calculateOverallAverageRating(array $departmentStats): float
    {
        $totalRatings = array_sum(array_column($departmentStats, 'total_ratings'));

        if ($totalRatings === 0) {
            return 0;
        }

        $weightedSum = 0;
        foreach ($departmentStats as $dept) {
            $weightedSum += $dept['average_rating'] * $dept['total_ratings'];
        }

        return round($weightedSum / $totalRatings, 2);
    }
}
