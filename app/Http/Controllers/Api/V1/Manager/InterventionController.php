<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Manager;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\InternalNote;
use App\Models\User;
use App\Services\Chat\ConversationService;
use App\Services\Chat\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Manager intervention controller — provides tools for managers to
 * intervene in and supervise conversations within their department.
 *
 * Allows taking over conversations, adding private notes, force-closing
 * conversations, and sending system-level messages for operational
 * oversight.
 */
final class InterventionController extends Controller
{
    public function __construct(
        private readonly ConversationService $conversationService,
        private readonly MessageService $messageService,
    ) {}

    /**
     * Manager takes over a conversation from the assigned agent.
     *
     * Reassigns the conversation to the manager and sends a system
     * message notifying both the customer and agent of the intervention.
     *
     * POST /api/v1/manager/interventions/{conversation}/take-over
     */
    public function takeOver(Request $request, Conversation $conversation): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

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
                'message' => 'Cannot take over a closed conversation.',
            ], 422);
        }

        // Capture the previous agent before reassignment
        $previousAgentId = $conversation->agent_id;

        // Reassign conversation to the manager
        $conversation = $this->conversationService->assign(
            conversation: $conversation,
            agentId: $user->id,
        );

        // Send a system message about the intervention
        $this->messageService->sendSystemMessage(
            conversation: $conversation,
            content: 'A manager has taken over this conversation.',
            metadata: [
                'action' => 'manager_takeover',
                'manager_id' => $user->id,
                'previous_agent_id' => $previousAgentId,
            ],
        );

        return response()->json([
            'success' => true,
            'data' => [
                'conversation' => $conversation->load(['customer', 'agent', 'department']),
            ],
        ]);
    }

    /**
     * Add a private internal note to a conversation.
     *
     * Internal notes are only visible to agents and managers — never
     * to customers. Useful for documenting intervention rationale,
     * escalation context, or follow-up actions.
     *
     * POST /api/v1/manager/interventions/{conversation}/notes
     */
    public function addNote(Request $request, Conversation $conversation): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Ensure the conversation belongs to the manager's department
        if ($conversation->department_id !== $user->department_id) {
            return response()->json([
                'success' => false,
                'message' => 'Conversation does not belong to your department.',
            ], 403);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        $note = InternalNote::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'content' => $validated['content'],
        ]);

        $note->load('user');

        return response()->json([
            'success' => true,
            'data' => [
                'note' => $note,
            ],
        ], 201);
    }

    /**
     * Force-close a conversation within the manager's department.
     *
     * Bypasses the normal agent close flow — useful for terminating
     * conversations that are abandoned, abusive, or otherwise need
     * immediate closure from a management perspective.
     *
     * POST /api/v1/manager/interventions/{conversation}/close
     */
    public function closeConversation(Request $request, Conversation $conversation): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

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
                'message' => 'Conversation is already closed.',
            ], 422);
        }

        $conversation = $this->conversationService->close(
            conversation: $conversation,
            reason: 'Conversation closed by manager.',
        );

        return response()->json([
            'success' => true,
            'data' => [
                'conversation' => $conversation->load(['customer', 'agent', 'department']),
            ],
        ]);
    }

    /**
     * Send a system message to a conversation.
     *
     * System messages appear as official notices within the conversation
     * thread. Useful for broadcasting policy reminders, escalation
     * notices, or any management-level communication.
     *
     * POST /api/v1/manager/interventions/{conversation}/system-message
     */
    public function sendSystemMessage(Request $request, Conversation $conversation): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

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
                'message' => 'Cannot send a message to a closed conversation.',
            ], 422);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        $message = $this->messageService->sendSystemMessage(
            conversation: $conversation,
            content: $validated['content'],
            metadata: [
                'action' => 'manager_message',
                'manager_id' => $user->id,
            ],
        );

        return response()->json([
            'success' => true,
            'data' => [
                'message' => $message,
            ],
        ]);
    }
}
