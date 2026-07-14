<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Manager;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\User;
use App\Services\Analytics\DashboardStatsService;
use App\Services\Chat\ConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Manager agent controller — manages agents within the manager's department.
 *
 * Provides agent listing with status and workload, status updates,
 * performance statistics, and conversation assignment capabilities.
 */
final class AgentController extends Controller
{
    public function __construct(
        private readonly DashboardStatsService $statsService,
        private readonly ConversationService $conversationService,
    ) {}

    /**
     * Get all agents in the manager's department.
     *
     * Returns a list of agents with their current status, active
     * conversation count, and last login timestamp.
     *
     * GET /api/v1/manager/agents
     */
    public function getAgents(Request $request): JsonResponse
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
            'data' => [
                'agents' => $agents,
            ],
        ]);
    }

    /**
     * Update an agent's status within the manager's department.
     *
     * Validates that the target agent belongs to the same department
     * as the manager before applying the status change.
     *
     * PUT /api/v1/manager/agents/{agent}/status
     */
    public function updateAgentStatus(Request $request, User $agent): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Ensure the agent belongs to the manager's department
        if ($agent->department_id !== $user->department_id) {
            return response()->json([
                'success' => false,
                'message' => 'Agent does not belong to your department.',
            ], 403);
        }

        if (!$agent->isAgent()) {
            return response()->json([
                'success' => false,
                'message' => 'The specified user is not an agent.',
            ], 422);
        }

        $validated = $request->validate([
            'status' => 'required|in:online,away,busy,offline',
        ]);

        $agent->update([
            'status' => $validated['status'],
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'agent' => [
                    'id' => $agent->id,
                    'name' => $agent->name,
                    'status' => $agent->fresh()->status,
                    'is_active' => $agent->is_active,
                ],
            ],
        ]);
    }

    /**
     * Get performance statistics for a specific agent.
     *
     * Delegates to DashboardStatsService for comprehensive per-agent
     * metrics including conversations handled, resolution times, and
     * satisfaction scores.
     *
     * GET /api/v1/manager/agents/{agent}/stats?days=30
     */
    public function getAgentStats(Request $request, User $agent): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Ensure the agent belongs to the manager's department
        if ($agent->department_id !== $user->department_id) {
            return response()->json([
                'success' => false,
                'message' => 'Agent does not belong to your department.',
            ], 403);
        }

        if (!$agent->isAgent()) {
            return response()->json([
                'success' => false,
                'message' => 'The specified user is not an agent.',
            ], 422);
        }

        $validated = $request->validate([
            'days' => 'nullable|integer|min:1|max:90',
        ]);

        $days = $validated['days'] ?? 30;

        $stats = $this->statsService->getAgentStats(
            departmentId: $user->department_id,
            agentId: $agent->id,
            days: $days,
        );

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Assign a conversation to a specific agent.
     *
     * Validates that both the agent and the conversation belong to the
     * manager's department before performing the assignment.
     *
     * POST /api/v1/manager/agents/{agent}/assign
     */
    public function assignConversation(Request $request, User $agent): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Ensure the agent belongs to the manager's department
        if ($agent->department_id !== $user->department_id) {
            return response()->json([
                'success' => false,
                'message' => 'Agent does not belong to your department.',
            ], 403);
        }

        if (!$agent->isAgent()) {
            return response()->json([
                'success' => false,
                'message' => 'The specified user is not an agent.',
            ], 422);
        }

        $validated = $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
        ]);

        $conversation = Conversation::findOrFail($validated['conversation_id']);

        // Ensure the conversation belongs to the manager's department
        if ($conversation->department_id !== $user->department_id) {
            return response()->json([
                'success' => false,
                'message' => 'Conversation does not belong to your department.',
            ], 403);
        }

        if ($conversation->isClosed()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot assign a closed conversation.',
            ], 422);
        }

        $conversation = $this->conversationService->assign(
            conversation: $conversation,
            agentId: $agent->id,
        );

        return response()->json([
            'success' => true,
            'data' => [
                'conversation' => $conversation->load(['customer', 'agent', 'department']),
            ],
        ]);
    }
}
