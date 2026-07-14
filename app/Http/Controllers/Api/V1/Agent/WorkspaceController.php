<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Agent;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Queue;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Agent workspace controller — the primary interface for support agents.
 *
 * Provides the agent's active conversations, department context, and queue
 * visibility, plus status management to signal availability to the system.
 */
final class WorkspaceController extends Controller
{
    /**
     * Get the agent's workspace data.
     *
     * Aggregates three data sources in a single response:
     * 1. Active conversations assigned to the agent
     * 2. The agent's department info
     * 3. Current queue status for the department
     *
     * GET /api/v1/agent/workspace
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // ── Active conversations assigned to this agent ──────────
        $conversations = Conversation::query()
            ->where('agent_id', $user->id)
            ->whereIn('status', ['active', 'pending', 'transferred'])
            ->with(['customer:id,name,avatar', 'department:id,name_en,name_bm,color'])
            ->orderByDesc('started_at')
            ->get()
            ->map(fn (Conversation $conversation) => [
                'id' => $conversation->id,
                'uuid' => $conversation->uuid,
                'status' => $conversation->status,
                'language' => $conversation->language,
                'priority' => $conversation->priority,
                'started_at' => $conversation->started_at?->toISOString(),
                'customer' => $conversation->customer !== null
                    ? [
                        'id' => $conversation->customer->id,
                        'name' => $conversation->customer->name,
                        'avatar' => $conversation->customer->avatar,
                    ]
                    : null,
                'department' => $conversation->department !== null
                    ? [
                        'id' => $conversation->department->id,
                        'name' => $conversation->department->name_en,
                        'color' => $conversation->department->color,
                    ]
                    : null,
            ]);

        // ── Department info ─────────────────────────────────────
        $department = $user->department_id !== null
            ? [
                'id' => $user->department->id,
                'name' => $user->department->name_en,
                'name_bm' => $user->department->name_bm,
                'color' => $user->department->color,
                'max_queue_size' => $user->department->max_queue_size,
                'max_agents' => $user->department->max_agents,
            ]
            : null;

        // ── Queue status for the agent's department ─────────────
        $queueStatus = $this->getQueueStatus($user->department_id);

        return response()->json([
            'success' => true,
            'data' => [
                'conversations' => $conversations,
                'department' => $department,
                'queue_status' => $queueStatus,
            ],
        ]);
    }

    /**
     * Update the agent's availability status.
     *
     * Validates against the allowed status values and persists the
     * change so the queue router and dashboard reflect it immediately.
     *
     * PATCH /api/v1/agent/workspace/status
     */
    public function status(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:online,away,busy,offline'],
        ]);

        $user->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $validated['status'],
            ],
        ]);
    }

    /**
     * Calculate queue statistics for a department.
     *
     * Returns the total waiting count, average estimated wait time,
     * and the total number of conversations currently in the queue.
     */
    private function getQueueStatus(?int $departmentId): array
    {
        if ($departmentId === null) {
            return [
                'total_waiting' => 0,
                'average_wait_seconds' => 0,
                'total_in_queue' => 0,
            ];
        }

        $waitingQueues = Queue::query()
            ->where('department_id', $departmentId)
            ->where('status', 'waiting')
            ->get();

        $totalWaiting = $waitingQueues->count();

        $averageWait = $totalWaiting > 0
            ? (int) round($waitingQueues->avg('estimated_wait_seconds'))
            : 0;

        return [
            'total_waiting' => $totalWaiting,
            'average_wait_seconds' => $averageWait,
            'total_in_queue' => $totalWaiting,
        ];
    }
}
