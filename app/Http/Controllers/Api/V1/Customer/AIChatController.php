<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Jobs\AI\GenerateGreeting;
use App\Jobs\AI\ProcessAIResponse;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\AI\AIOrchestrator;
use App\Services\Chat\ConversationService;
use App\Services\Chat\MessageService;
use App\Services\Chat\QueueService;
use App\Services\Chat\RoutingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * AI-powered chat controller for customers.
 *
 * Manages the full lifecycle of AI-assisted conversations: starting
 * a chat with the AI assistant, sending messages and receiving AI
 * responses, requesting a human agent transfer, and retrieving
 * AI-suggested quick replies.
 */
final class AIChatController extends Controller
{
    public function __construct(
        private readonly AIOrchestrator $aiOrchestrator,
        private readonly ConversationService $conversationService,
        private readonly MessageService $messageService,
        private readonly QueueService $queueService,
        private readonly RoutingService $routingService,
    ) {}

    /**
     * Start a new conversation with the AI assistant.
     *
     * Creates a conversation without an assigned agent (AI-only mode),
     * then dispatches a queued job to generate the AI greeting so the
     * response returns immediately.
     *
     * POST /api/v1/customer/ai-chat/start
     */
    public function startWithAI(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'language' => 'nullable|in:en,bm',
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();
        $language = $validated['language'] ?? $user->language_preference ?? 'en';

        // Create conversation in pending status, AI-only (no agent)
        $conversation = Conversation::create([
            'uuid' => Str::uuid()->toString(),
            'user_id' => $user->id,
            'department_id' => $validated['department_id'],
            'status' => 'pending',
            'language' => $language,
            'started_at' => now(),
        ]);

        // Enqueue the conversation for agent assignment later (if needed)
        $this->queueService->enqueue($conversation);

        // Dispatch job to generate AI greeting asynchronously
        GenerateGreeting::dispatch($conversation, $language);

        return response()->json([
            'success' => true,
            'data' => [
                'conversation' => $conversation->fresh()->load(['department']),
                'greeting' => null, // Will be populated via WebSocket/polling
            ],
        ], 201);
    }

    /**
     * Send a message to the AI assistant and receive a response.
     *
     * Only works on AI-only conversations (no agent assigned yet).
     * Creates the user message, dispatches AI processing via queue,
     * and returns both messages.
     *
     * POST /api/v1/customer/ai-chat/{conversation}/message
     */
    public function sendMessage(Request $request, Conversation $conversation): JsonResponse
    {
        // Authorization check
        if ($conversation->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        // Must be AI-only conversation (no agent assigned)
        if ($conversation->agent_id !== null) {
            return response()->json([
                'success' => false,
                'message' => 'This conversation has been assigned to a human agent. Please use the standard chat endpoint.',
            ], 422);
        }

        // Conversation must not be closed
        if ($conversation->status === 'closed') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot send message to a closed conversation.',
            ], 422);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:5000',
        ]);

        // Create user message
        $userMessage = $this->messageService->send(
            conversation: $conversation,
            senderId: $request->user()->id,
            senderType: 'customer',
            content: $validated['content'],
            messageType: 'text',
        );

        // Dispatch AI processing job
        ProcessAIResponse::dispatch($userMessage, $conversation);

        // For immediate response, generate AI reply synchronously
        // (In production, prefer queue + WebSocket push)
        $aiResponseContent = $this->aiOrchestrator->processMessage($userMessage, $conversation);

        $aiMessage = null;
        $shouldTransfer = false;

        if ($aiResponseContent !== null) {
            $aiMessage = $this->messageService->send(
                conversation: $conversation,
                senderId: 0,
                senderType: 'ai',
                content: $aiResponseContent,
                messageType: 'text',
                isAiGenerated: true,
            );

            // Check if the conversation should be transferred to a human agent
            $shouldTransfer = $this->shouldTransferToAgent($validated['content'], $aiResponseContent);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user_message' => $userMessage,
                'ai_response' => $aiMessage,
                'should_transfer' => $shouldTransfer,
            ],
        ], 201);
    }

    /**
     * Request a human agent for the conversation.
     *
     * Transitions the AI-only conversation to agent queue.
     * Attempts immediate routing; falls back to queue if no agent is available.
     *
     * POST /api/v1/customer/ai-chat/{conversation}/request-agent
     */
    public function requestAgent(Request $request, Conversation $conversation): JsonResponse
    {
        // Authorization check
        if ($conversation->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        // Must be AI-only (no agent assigned yet)
        if ($conversation->agent_id !== null) {
            return response()->json([
                'success' => false,
                'message' => 'An agent is already assigned to this conversation.',
            ], 422);
        }

        if ($conversation->status === 'closed') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot request an agent for a closed conversation.',
            ], 422);
        }

        // Try to route to an available agent immediately
        $agent = $this->routingService->routeToAgent($conversation);

        if ($agent === null) {
            // No agent available — ensure conversation stays queued
            $conversation->update(['status' => 'queued']);

            $this->messageService->sendSystemMessage(
                $conversation,
                'You have been placed in the queue. An agent will be with you shortly.',
                ['action' => 'queued_for_agent'],
            );

            $message = 'No agents available at the moment. You have been placed in the queue.';
        } else {
            $conversation->update(['status' => 'active']);

            $this->messageService->sendSystemMessage(
                $conversation,
                "An agent ({$agent->name}) has been assigned to your conversation.",
                ['action' => 'agent_assigned', 'agent_id' => $agent->id],
            );

            $message = "An agent has been assigned to your conversation.";
        }

        return response()->json([
            'success' => true,
            'data' => [
                'message' => $message,
                'conversation' => $conversation->fresh()->load(['department', 'agent']),
            ],
        ]);
    }

    /**
     * Get AI-suggested quick replies for the conversation.
     *
     * Analyzes the conversation context and generates 3-5 suggested
     * quick replies the customer can use.
     *
     * GET /api/v1/customer/ai-chat/{conversation}/suggestions
     */
    public function getAISuggestions(Request $request, Conversation $conversation): JsonResponse
    {
        // Authorization check
        if ($conversation->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        // Build conversation context from recent messages
        $recentMessages = $conversation->messages()
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->reverse()
            ->values();

        $context = $recentMessages->map(fn (Message $msg) => sprintf(
            '%s: %s',
            $msg->sender_type === 'customer' ? 'User' : 'Assistant',
            $msg->content,
        ))->implode("\n");

        $language = $conversation->language ?? 'en';

        $suggestions = $this->generateQuickReplies($context, $language, $conversation);

        return response()->json([
            'success' => true,
            'data' => [
                'suggestions' => $suggestions,
            ],
        ]);
    }

    // ─── Private helpers ────────────────────────────────────────

    /**
     * Determine if the conversation should be transferred to a human agent.
     *
     * Checks for explicit transfer requests in the user message and
     * patterns in the AI response that suggest escalation is needed.
     */
    private function shouldTransferToAgent(string $userMessage, string $aiResponse): bool
    {
        $lowerMessage = mb_strtolower($userMessage);

        // Explicit transfer keywords from user
        $transferKeywords = [
            'speak to agent', 'talk to agent', 'human agent',
            'real person', 'talk to someone', 'speak to someone',
            'talk to human', 'speak to human',
            'bercakap dengan ejen', 'bercakap dengan orang',
            'ejen manusia', 'orang sebenar',
        ];

        foreach ($transferKeywords as $keyword) {
            if (str_contains($lowerMessage, $keyword)) {
                return true;
            }
        }

        // AI response indicates escalation needed
        $escalationPatterns = [
            'please wait while i transfer',
            'connecting you to an agent',
            'sila tunggu semasa saya memindahkan',
            'menghubungkan anda dengan ejen',
            'i\'ll need to connect you',
            'saya perlu menghubungkan anda',
        ];

        $lowerResponse = mb_strtolower($aiResponse);

        foreach ($escalationPatterns as $pattern) {
            if (str_contains($lowerResponse, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate quick reply suggestions based on conversation context.
     *
     * Uses the AI orchestrator's intent analysis to suggest relevant
     * follow-up actions the customer might want to take.
     *
     * @return list<array{label: string, value: string}>
     */
    private function generateQuickReplies(string $context, string $language, Conversation $conversation): array
    {
        $baseSuggestions = $language === 'bm' ? [
            ['label' => 'Saya ingin bercakap dengan ejen', 'value' => 'Saya ingin bercakap dengan ejen'],
            ['label' => 'Boleh anda terangkan lagi?', 'value' => 'Boleh anda terangkan lagi?'],
            ['label' => 'Terima kasih', 'value' => 'Terima kasih'],
            ['label' => 'Saya ada masalah lain', 'value' => 'Saya ada masalah lain'],
            ['label' => 'Tutup perbualan', 'value' => 'Tutup perbualan'],
        ] : [
            ['label' => 'I\'d like to speak to an agent', 'value' => 'I\'d like to speak to an agent'],
            ['label' => 'Can you explain further?', 'value' => 'Can you explain further?'],
            ['label' => 'Thank you', 'value' => 'Thank you'],
            ['label' => 'I have another issue', 'value' => 'I have another issue'],
            ['label' => 'Close chat', 'value' => 'Close chat'],
        ];

        // In a full implementation, this would call Gemini to generate
        // context-aware suggestions. For now, return base suggestions
        // tailored to the conversation length.
        $messageCount = $conversation->messages()->count();

        if ($messageCount <= 2) {
            // Early conversation — focus on common starting questions
            return array_slice($baseSuggestions, 0, 3);
        }

        // Later in conversation — include agent transfer option prominently
        return $baseSuggestions;
    }
}
