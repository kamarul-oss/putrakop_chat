<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Agent;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Chat\ConversationService;
use App\Services\Chat\QueueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Agent-facing queue controller.
 *
 * Provides agents with their next queued conversation and real-time
 * queue statistics for their department.
 */
final class QueueController extends Controller
{
    public function __construct(
        private readonly QueueService $queueService,
        private readonly ConversationService $conversationService,
    ) {}

    /**
     * Get the next conversation from the queue for the agent to handle.
     *
     * GET /api/v1/agent/queue/next
     *
     * Returns the next queued conversation in the agent's department
     * based on priority and arrival time. Returns null when the queue
     * is empty.
     */
    public function getNextInQueue(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $departmentId = $user->department_id;

        if ($departmentId === null) {
            return response()->json([
                'success' => false,
                'message' => 'You are not assigned to a department.',
            ], 403);
        }

        $conversation = $this->queueService->getNext($departmentId);

        if ($conversation === null) {
            return response()->json([
                'success' => true,
                'data' => [
                    'conversation' => null,
                    'message' => 'No conversations in queue.',
                ],
            ]);
        }

        // Eager-load relationships for the agent workspace
        $conversation->load([
            'customer:id,name,avatar,email,phone',
            'department:id,name',
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'conversation' => $conversation,
            ],
        ]);
    }

    /**
     * Get queue statistics for the agent's department.
     *
     * GET /api/v1/agent/queue/stats
     *
     * Returns total waiting conversations, average wait time,
     * and the number of agents currently online.
     */
    public function getQueueStats(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $departmentId = $user->department_id;

        if ($departmentId === null) {
            return response()->json([
                'success' => false,
                'message' => 'You are not assigned to a department.',
            ], 403);
        }

        $stats = $this->queueService->getQueueStats($departmentId);

        $agentsOnline = User::where('department_id', $departmentId)
            ->where('role', 'agent')
            ->where('status', 'online')
            ->where('is_active', true)
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_waiting' => $stats['total_waiting'],
                'average_wait' => $stats['average_wait_seconds'] ?? 0,
                'agents_online' => $agentsOnline,
            ],
        ]);
    }
}
