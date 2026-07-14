<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Services\Chat\QueueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Customer-facing queue controller.
 *
 * Provides real-time queue position and estimated wait time information
 * for conversations waiting to be connected with an agent.
 */
final class QueueController extends Controller
{
    public function __construct(
        private readonly QueueService $queueService,
    ) {}

    /**
     * Get the queue status for a specific conversation.
     *
     * GET /api/v1/customer/conversations/{conversation}/queue-status
     *
     * Returns the conversation's current position in the queue,
     * estimated wait time in seconds, and status.
     */
    public function getStatus(Request $request, Conversation $conversation): JsonResponse
    {
        if ($conversation->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $position = $this->queueService->getPosition($conversation);

        if ($position === null) {
            return response()->json([
                'success' => true,
                'data' => [
                    'position' => null,
                    'estimated_wait_seconds' => 0,
                    'status' => $conversation->status,
                    'message' => 'Conversation is not currently in the queue.',
                ],
            ]);
        }

        // Get estimated wait from the queue entry
        $queueEntry = $conversation->queue()
            ->where('status', 'waiting')
            ->first();

        $estimatedWait = $queueEntry?->estimated_wait_seconds ?? 0;

        return response()->json([
            'success' => true,
            'data' => [
                'position' => $position,
                'estimated_wait_seconds' => $estimatedWait,
                'status' => $conversation->status,
            ],
        ]);
    }

    /**
     * Get general queue information for a department.
     *
     * GET /api/v1/customer/queue-info?department_id=1
     *
     * Returns the total number of waiting conversations, average wait
     * time, and estimated wait for a new entrant.
     */
    public function getQueueInfo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
        ]);

        $departmentId = $validated['department_id'];

        $stats = $this->queueService->getQueueStats($departmentId);

        // Estimate wait for a new entrant: position would be total_waiting + 1
        $estimatedNewEntrantWait = $stats['average_wait_seconds'] !== null
            ? ($stats['total_waiting'] + 1) * $stats['average_wait_seconds']
            : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'total_waiting' => $stats['total_waiting'],
                'average_wait' => $stats['average_wait_seconds'] ?? 0,
                'estimated_wait' => $estimatedNewEntrantWait,
            ],
        ]);
    }
}
