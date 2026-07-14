<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Conversation;
use App\Models\Department;
use App\Models\KnowledgeBase;
use App\Models\Message;
use App\Models\Rating;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    /**
     * Get the main admin dashboard overview statistics.
     */
    public function index(Request $request): JsonResponse
    {
        $now           = Carbon::now();
        $todayStart    = $now->copy()->startOfDay();
        $weekStart     = $now->copy()->startOfWeek();
        $monthStart    = $now->copy()->startOfMonth();

        $data = [
            'overview' => [
                'total_users'          => User::count(),
                'total_departments'    => Department::count(),
                'total_conversations'  => Conversation::count(),
                'total_messages'       => Message::count(),
                'total_kb_articles'    => KnowledgeBase::count(),
                'total_ratings'        => Rating::count(),
            ],
            'today' => [
                'new_conversations'    => Conversation::where('created_at', '>=', $todayStart)->count(),
                'new_messages'         => Message::where('created_at', '>=', $todayStart)->count(),
                'new_users'            => User::where('created_at', '>=', $todayStart)->count(),
                'resolved_conversations' => Conversation::where('status', 'resolved')
                    ->where('updated_at', '>=', $todayStart)->count(),
            ],
            'this_week' => [
                'new_conversations'    => Conversation::where('created_at', '>=', $weekStart)->count(),
                'new_messages'         => Message::where('created_at', '>=', $weekStart)->count(),
                'new_users'            => User::where('created_at', '>=', $weekStart)->count(),
            ],
            'this_month' => [
                'new_conversations'    => Conversation::where('created_at', '>=', $monthStart)->count(),
                'new_messages'         => Message::where('created_at', '>=', $monthStart)->count(),
                'new_users'            => User::where('created_at', '>=', $monthStart)->count(),
                'avg_rating'           => Rating::where('created_at', '>=', $monthStart)->avg('score'),
            ],
        ];

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * Get system health and resource statistics.
     */
    public function getSystemStats(Request $request): JsonResponse
    {
        $data = [
            'users_by_role' => User::select('role', \DB::raw('COUNT(*) as count'))
                ->groupBy('role')
                ->pluck('count', 'role'),
            'departments' => [
                'total'           => Department::count(),
                'active'          => Department::where('is_active', true)->count(),
                'inactive'        => Department::where('is_active', false)->count(),
            ],
            'conversations_by_status' => Conversation::select('status', \DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status'),
            'messages_today' => Message::where('created_at', '>=', Carbon::now()->startOfDay())->count(),
            'active_conversations' => Conversation::where('status', 'active')->count(),
            'queue_size' => Conversation::where('status', 'waiting')->count(),
            'system' => [
                'php_version'         => PHP_VERSION,
                'laravel_version'     => app()->version(),
                'database_driver'     => config('database.default'),
                'cache_driver'        => config('cache.default'),
                'queue_driver'        => config('queue.default'),
                'disk_usage'          => $this->getDiskUsage(),
            ],
        ];

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * Get detailed user statistics.
     */
    public function getUserStats(Request $request): JsonResponse
    {
        $data = [
            'total' => User::count(),
            'by_role' => User::select('role', \DB::raw('COUNT(*) as count'))
                ->groupBy('role')
                ->pluck('count', 'role'),
            'by_status' => User::select('status', \DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status'),
            'by_department' => User::select('department_id', \DB::raw('COUNT(*) as count'))
                ->whereNotNull('department_id')
                ->groupBy('department_id')
                ->with('department:id,name')
                ->get()
                ->pluck('count', 'department.name'),
            'recent_registrations' => [
                'today'  => User::where('created_at', '>=', Carbon::now()->startOfDay())->count(),
                'week'   => User::where('created_at', '>=', Carbon::now()->startOfWeek())->count(),
                'month'  => User::where('created_at', '>=', Carbon::now()->startOfMonth())->count(),
            ],
            'active_users' => [
                'last_24h'  => User::where('last_login_at', '>=', Carbon::now()->subDay())->count(),
                'last_7d'   => User::where('last_login_at', '>=', Carbon::now()->subDays(7))->count(),
                'last_30d'  => User::where('last_login_at', '>=', Carbon::now()->subDays(30))->count(),
            ],
        ];

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * Get recent system activity including audit logs, conversations, and user registrations.
     */
    public function getRecentActivity(Request $request): JsonResponse
    {
        $limit = $request->integer('limit', 20);

        $recentAuditLogs = AuditLog::with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        $recentConversations = Conversation::with(['user', 'department'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        $recentUsers = User::with('department')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        $recentRatings = Rating::with('conversation')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data'    => [
                'audit_logs'       => $recentAuditLogs,
                'conversations'    => $recentConversations,
                'users'            => $recentUsers,
                'ratings'          => $recentRatings,
            ],
        ]);
    }

    /**
     * Calculate disk usage percentage for the application.
     */
    private function getDiskUsage(): array
    {
        $total = disk_total_space('/');
        $free  = disk_free_space('/');

        if ($total === false || $free === false) {
            return ['total' => 0, 'free' => 0, 'used' => 0, 'percentage' => 0];
        }

        $used     = $total - $free;
        $percent  = $total > 0 ? round(($used / $total) * 100, 2) : 0;

        return [
            'total'      => round($total / 1073741824, 2) . ' GB',
            'free'       => round($free / 1073741824, 2) . ' GB',
            'used'       => round($used / 1073741824, 2) . ' GB',
            'percentage' => $percent,
        ];
    }
}
