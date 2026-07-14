<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
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
     * Start a new chat conversation.
     */
    public function startConversation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'language' => 'nullable|in:en,bm',
        ]);

        $user = $request->user();

        $conversation = $this->conversationService->startConversation(
            userId: $user->id,
            departmentId: $validated['department_id'],
            language: $validated['language'] ?? 'en',
        );

        // Auto-route to an available agent
        $this->conversationService->autoRouteToAgent($conversation);

        return response()->json([
            'success' => true,
            'data' => [
                'conversation' => $conversation->load(['department', 'agent']),
            ],
        ], 201);
    }

    /**
     * Send a message in a conversation.
     */
    public function sendMessage(Request $request, Conversation $conversation): JsonResponse
    {
        if ($conversation->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
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
            'message_type' => 'nullable|in:text,image,file',
        ]);

        $message = $this->messageService->sendMessage(
            conversation: $conversation,
            senderId: $request->user()->id,
            senderType: 'customer',
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
        if ($conversation->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
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
     * Close a conversation.
     */
    public function closeConversation(Request $request, Conversation $conversation): JsonResponse
    {
        if ($conversation->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
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
            closedBy: 'customer',
        );

        return response()->json([
            'success' => true,
            'data' => [
                'conversation' => $conversation,
            ],
        ]);
    }

    /**
     * Get all conversations for the authenticated user.
     */
    public function getMyConversations(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Conversation::where('user_id', $user->id)
            ->with(['department', 'agent', 'lastMessage'])
            ->latest();

        $status = $request->query('status');
        if ($status) {
            $query->where('status', $status);
        }

        $conversations = $query->get();

        return response()->json([
            'success' => true,
            'data' => [
                'conversations' => $conversations,
            ],
        ]);
    }
}
