<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Agent;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\InternalNote;
use App\Services\Chat\ConversationService;
use App\Services\Chat\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(
        private readonly ConversationService $conversationService,
        private readonly MessageService $messageService,
    ) {}

    /**
     * Get conversations assigned to the authenticated agent.
     */
    public function getAssignedConversations(Request $request): JsonResponse
    {
        $user = $request->user();

        $conversations = Conversation::where('agent_id', $user->id)
            ->with(['customer', 'lastMessage', 'department'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'conversations' => $conversations,
            ],
        ]);
    }

    /**
     * Accept a queued conversation.
     */
    public function acceptConversation(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        if ($conversation->status !== Conversation::STATUS_QUEUED) {
            return response()->json([
                'success' => false,
                'message' => 'Conversation is not in the queue.',
            ], 422);
        }

        if ($conversation->department_id !== $user->department_id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not assigned to this department.',
            ], 403);
        }

        $conversation = $this->conversationService->assignAgent(
            conversation: $conversation,
            agentId: $user->id,
        );

        return response()->json([
            'success' => true,
            'data' => [
                'conversation' => $conversation->load(['customer', 'department']),
            ],
        ]);
    }

    /**
     * Send a message in a conversation.
     */
    public function sendMessage(Request $request, Conversation $conversation): JsonResponse
    {
        if ($conversation->agent_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not assigned to this conversation.',
            ], 403);
        }

        if ($conversation->status === Conversation::STATUS_CLOSED) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot send message to a closed conversation.',
            ], 422);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:5000',
            'message_type' => 'nullable|in:text,image,file,quick_reply',
        ]);

        $message = $this->messageService->sendMessage(
            conversation: $conversation,
            senderId: $request->user()->id,
            senderType: 'agent',
            content: $validated['content'],
            messageType: $validated['message_type'] ?? 'text',
        );

        return response()->json([
            'success' => true,
            'data' => [
                'message' => $message,
            ],
        ], 201);
    }

    /**
     * Get messages for a conversation with pagination.
     */
    public function getMessages(Request $request, Conversation $conversation): JsonResponse
    {
        if ($conversation->agent_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not assigned to this conversation.',
            ], 403);
        }

        $validated = $request->validate([
            'limit' => 'nullable|integer|min:1|max:100',
            'before_id' => 'nullable|integer',
        ]);

        $limit = $validated['limit'] ?? 50;
        $beforeId = $validated['before_id'] ?? null;

        $messages = $this->messageService->getMessages(
            conversation: $conversation,
            limit: $limit,
            beforeId: $beforeId,
        );

        $hasMore = $messages->count() === $limit;

        return response()->json([
            'success' => true,
            'data' => [
                'messages' => $messages->values(),
                'has_more' => $hasMore,
            ],
        ]);
    }

    /**
     * Mark all unread messages in a conversation as read.
     */
    public function markAsRead(Request $request, Conversation $conversation): JsonResponse
    {
        if ($conversation->agent_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not assigned to this conversation.',
            ], 403);
        }

        $markedCount = $this->messageService->markAsRead(
            conversation: $conversation,
            readerId: $request->user()->id,
        );

        return response()->json([
            'success' => true,
            'data' => [
                'marked_count' => $markedCount,
            ],
        ]);
    }

    /**
     * Transfer a conversation to another agent or department.
     */
    public function transferConversation(Request $request, Conversation $conversation): JsonResponse
    {
        if ($conversation->agent_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not assigned to this conversation.',
            ], 403);
        }

        $validated = $request->validate([
            'to_department_id' => 'nullable|exists:departments,id',
            'to_agent_id' => 'nullable|exists:users,id',
            'reason' => 'nullable|string|max:500',
        ]);

        if (empty($validated['to_department_id']) && empty($validated['to_agent_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Either to_department_id or to_agent_id must be provided.',
            ], 422);
        }

        $conversation = $this->conversationService->transferConversation(
            conversation: $conversation,
            toDepartmentId: $validated['to_department_id'] ?? null,
            toAgentId: $validated['to_agent_id'] ?? null,
            transferredBy: $request->user()->id,
            reason: $validated['reason'] ?? null,
        );

        return response()->json([
            'success' => true,
            'data' => [
                'conversation' => $conversation->load(['customer', 'department', 'agent']),
            ],
        ]);
    }

    /**
     * Close a conversation.
     */
    public function closeConversation(Request $request, Conversation $conversation): JsonResponse
    {
        if ($conversation->agent_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not assigned to this conversation.',
            ], 403);
        }

        if ($conversation->status === Conversation::STATUS_CLOSED) {
            return response()->json([
                'success' => false,
                'message' => 'Conversation is already closed.',
            ], 422);
        }

        $conversation = $this->conversationService->closeConversation(
            conversation: $conversation,
            closedBy: 'agent',
        );

        return response()->json([
            'success' => true,
            'data' => [
                'conversation' => $conversation,
            ],
        ]);
    }

    /**
     * Add an internal note to a conversation.
     */
    public function addInternalNote(Request $request, Conversation $conversation): JsonResponse
    {
        if ($conversation->agent_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not assigned to this conversation.',
            ], 403);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        $note = InternalNote::create([
            'conversation_id' => $conversation->id,
            'user_id' => $request->user()->id,
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
     * Get internal notes for a conversation.
     */
    public function getInternalNotes(Request $request, Conversation $conversation): JsonResponse
    {
        if ($conversation->agent_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not assigned to this conversation.',
            ], 403);
        }

        $notes = InternalNote::where('conversation_id', $conversation->id)
            ->with('user')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'notes' => $notes,
            ],
        ]);
    }
}
