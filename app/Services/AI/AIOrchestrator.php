<?php
declare(strict_types=1);

namespace App\Services\AI;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\Chat\ConversationService;
use App\Services\Chat\MessageService;
use App\Services\Chat\QueueService;
use App\Services\Chat\RoutingService;
use App\Services\Chat\SmartRoutingService;
use Illuminate\Support\Facades\Log;

/**
 * Main AI orchestrator that coordinates all AI services.
 *
 * Handles the complete AI response pipeline:
 * 1. Detect language
 * 2. Check intent (greeting, question, complaint, etc.)
 * 3. Search knowledge base
 * 4. Generate response via Gemini
 * 5. Apply safety filters
 * 6. Detect when to transfer to a human agent
 */
final class AIOrchestrator
{
    /**
     * Keywords/phrases (lowercase) that signal a customer wants a human agent.
     *
     * @var list<string>
     */
    private const TRANSFER_KEYWORDS = [
        'speak to agent',
        'speak to a agent',
        'speak to an agent',
        'talk to agent',
        'talk to a agent',
        'talk to an agent',
        'talk to human',
        'talk to a human',
        'speak to human',
        'speak to a human',
        'real person',
        'real agent',
        'human agent',
        'live agent',
        'bukan robot',
        'bukan manusia',
        'manusia',
        'ejen',
        'orang sebenar',
        'cakap dengan orang',
        'mahu bercakap',
    ];

    /**
     * Strong sentiment / frustration keywords.
     *
     * @var list<string>
     */
    private const FRUSTRATION_KEYWORDS = [
        'very frustrated',
        'extremely frustrated',
        'so frustrated',
        'angry',
        'very angry',
        'unacceptable',
        'terrible',
        'worst',
        'pathetic',
        'useless',
        'waste of time',
        'sangat kecewa',
        'sangat marah',
        'tidak boleh diterima',
        'sangat teruk',
        'membazir masa',
    ];

    /**
     * Maximum number of consecutive AI failures before forcing a transfer.
     */
    private const MAX_AI_FAILURES_BEFORE_TRANSFER = 3;

    public function __construct(
        private readonly LanguageDetector $languageDetector,
        private readonly KBSearchService $kbSearch,
        private readonly GeminiService $gemini,
        private readonly MessageService $messageService,
        private readonly ConversationService $conversationService,
        private readonly SmartRoutingService $smartRoutingService,
        private readonly RoutingService $routingService,
        private readonly QueueService $queueService,
    ) {}

    /**
     * Process an incoming customer message and generate AI response.
     */
    public function processMessage(Message $message, Conversation $conversation): ?string
    {
        try {
            // 1. Detect language if not set
            $language = $message->language ?: $this->languageDetector->detect($message->content);

            // 2. Analyze intent
            $intent = $this->analyzeIntent($message->content);

            // 3. Handle different intents
            return match ($intent) {
                'greeting' => $this->handleGreeting($conversation, $language),
                'question' => $this->handleQuestion($message->content, $conversation, $language),
                'complaint' => $this->handleComplaint($message->content, $conversation, $language),
                'farewell' => $this->handleFarewell($conversation, $language),
                default => $this->handleGeneral($message->content, $conversation, $language),
            };
        } catch (\Exception $e) {
            Log::error('AI processing failed', [
                'message_id' => $message->id,
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);

            // Fallback to generic response
            return $this->getFallbackResponse($conversation->language ?? 'en');
        }
    }

    /**
     * Generate AI greeting for new conversation.
     */
    public function generateGreeting(Conversation $conversation, ?string $language = null): string
    {
        $lang = $language ?? $conversation->language ?? 'en';

        $greeting = $lang === 'bm'
            ? 'Selamat datang ke PutraKop! 👋 Saya adalah pembantu AI anda. Bagaimana saya boleh membantu anda hari ini?'
            : 'Welcome to PutraKop! 👋 I\'m your AI assistant. How can I help you today?';

        // Send greeting as system message
        $this->messageService->sendSystemMessage(
            $conversation,
            $greeting,
            ['type' => 'ai_greeting', 'language' => $lang]
        );

        return $greeting;
    }

    /**
     * Analyze the intent of a message.
     */
    private function analyzeIntent(string $content): string
    {
        $lowerContent = mb_strtolower($content);

        // Greeting patterns
        $greetingPatterns = ['hello', 'hi', 'hey', 'good morning', 'good afternoon', 'selamat', 'hai', 'mari'];
        foreach ($greetingPatterns as $pattern) {
            if (str_contains($lowerContent, $pattern)) {
                return 'greeting';
            }
        }

        // Question patterns
        $questionPatterns = ['?', 'how', 'what', 'where', 'when', 'why', 'can', 'could', 'bagaimana', 'apa', 'di mana', 'bila', 'mengapa', 'boleh'];
        foreach ($questionPatterns as $pattern) {
            if (str_contains($lowerContent, $pattern)) {
                return 'question';
            }
        }

        // Complaint patterns
        $complaintPatterns = ['problem', 'issue', 'error', 'broken', 'not working', 'masalah', 'tidak berfungsi', 'rosak'];
        foreach ($complaintPatterns as $pattern) {
            if (str_contains($lowerContent, $pattern)) {
                return 'complaint';
            }
        }

        // Farewell patterns
        $farewellPatterns = ['bye', 'goodbye', 'thank', 'thanks', 'selamat tinggal', 'terima kasih'];
        foreach ($farewellPatterns as $pattern) {
            if (str_contains($lowerContent, $pattern)) {
                return 'farewell';
            }
        }

        return 'general';
    }

    /**
     * Handle greeting intent.
     */
    private function handleGreeting(Conversation $conversation, string $language): string
    {
        return $this->generateGreeting($conversation, $language);
    }

    /**
     * Handle question intent - search KB first, then AI.
     */
    private function handleQuestion(string $content, Conversation $conversation, string $language): string
    {
        // Search knowledge base first
        $kbResults = $this->kbSearch->search(
            $content,
            $conversation->department_id,
            $language,
            3 // top 3 results
        );

        if ($kbResults->isNotEmpty()) {
            // Found relevant KB articles
            $context = $kbResults->map(fn ($item) => $item->getContent($language))->implode("\n\n");

            return $this->gemini->generateWithContext(
                $content,
                $context,
                $language,
                'answer_question'
            );
        }

        // No KB results, use general AI
        return $this->gemini->generate($content, $language, 'answer_question');
    }

    /**
     * Handle complaint intent.
     */
    private function handleComplaint(string $content, Conversation $conversation, string $language): string
    {
        $response = $language === 'bm'
            ? 'Saya memahami masalah anda. Saya akan membantu anda menyelesaikannya. Boleh anda berikan lebih butiran?'
            : 'I understand your concern. I\'ll help you resolve this. Could you provide more details?';

        // Log complaint for follow-up
        Log::info('Customer complaint detected', [
            'conversation_uuid' => $conversation->uuid,
            'content' => $content,
            'language' => $language,
        ]);

        return $response;
    }

    /**
     * Handle farewell intent.
     */
    private function handleFarewell(Conversation $conversation, string $language): string
    {
        return $language === 'bm'
            ? 'Terima kasih kerana menghubungi PutraKop! 😊 Jika anda memerlukan bantuan lagi, jangan segan untuk bertanya. Selamat tinggal!'
            : 'Thank you for contacting PutraKop! 😊 If you need further assistance, don\'t hesitate to ask. Goodbye!';
    }

    /**
     * Handle general messages.
     */
    private function handleGeneral(string $content, Conversation $conversation, string $language): string
    {
        return $this->gemini->generate($content, $language, 'general_chat');
    }

    /**
     * Get fallback response when AI fails.
     */
    private function getFallbackResponse(string $language): string
    {
        return $language === 'bm'
            ? 'Maaf, saya mengalami masalah teknikal. Seorang ejen akan membantu anda tidak lama lagi.'
            : 'Sorry, I\'m experiencing technical difficulties. An agent will assist you shortly.';
    }

    // ─── Human-agent transfer detection ─────────────────────────

    /**
     * Determine whether the current message (or conversation context)
     * warrants a transfer from AI to a human agent.
     *
     * Checks are evaluated in order — the first match triggers the
     * transfer recommendation:
     *
     * 1. Explicit transfer keywords ("speak to agent", "bukan robot", …).
     * 2. Complex complaint — multiple sentences with strong negative
     *    sentiment words.
     * 3. AI has failed to answer consecutively (tracked via metadata).
     */
    public function shouldTransferToAgent(Message $message, Conversation $conversation): bool
    {
        $content = mb_strtolower($message->content);

        // 1. Explicit transfer request
        if ($this->containsTransferKeywords($content)) {
            Log::info('Transfer recommended — explicit keyword match', [
                'message_id' => $message->id,
                'conversation_uuid' => $conversation->uuid,
            ]);

            return true;
        }

        // 2. Complex complaint with strong frustration
        if ($this->isComplexComplaint($content)) {
            Log::info('Transfer recommended — complex complaint detected', [
                'message_id' => $message->id,
                'conversation_uuid' => $conversation->uuid,
            ]);

            return true;
        }

        // 3. Repeated AI failures
        if ($this->hasAiFailedTooManyTimes($conversation)) {
            Log::info('Transfer recommended — AI failure threshold exceeded', [
                'message_id' => $message->id,
                'conversation_uuid' => $conversation->uuid,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Handle a customer's request to transfer to a human agent.
     *
     * Generates a language-appropriate confirmation message and queues
     * the conversation for agent assignment. Returns the confirmation
     * message that should be sent to the customer.
     */
    public function handleTransferRequest(Conversation $conversation, string $language): string
    {
        // Build the confirmation message
        $message = $language === 'bm'
            ? 'Baik, saya akan memindahkan anda kepada ejen manusia. Sila tunggu sebentar. Ejen akan masuk tidak lama lagi. 😊'
            : 'Sure, I\'ll transfer you to a human agent. Please wait a moment. An agent will join shortly. 😊';

        // Ensure the conversation is queued for agent pickup
        $this->queueService->enqueue($conversation);

        // Attempt immediate routing if agents are available
        $agent = $this->smartRoutingService->routeConversation($conversation);

        if ($agent !== null) {
            $this->conversationService->assign($conversation, $agent->id);

            Log::info('Transfer request — agent assigned immediately', [
                'conversation_uuid' => $conversation->uuid,
                'agent_id' => $agent->id,
            ]);
        } else {
            Log::info('Transfer request — conversation queued for agent', [
                'conversation_uuid' => $conversation->uuid,
                'queue_position' => $this->queueService->getPosition($conversation),
            ]);
        }

        return $message;
    }

    // ─── Private transfer-detection helpers ──────────────────────

    /**
     * Check whether the message content contains explicit transfer keywords.
     */
    private function containsTransferKeywords(string $lowerContent): bool
    {
        foreach (self::TRANSFER_KEYWORDS as $keyword) {
            if (str_contains($lowerContent, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect a complex complaint that the AI is unlikely to resolve.
     *
     * A complaint is considered complex when:
     * - The message has multiple sentences (contains periods, exclamation
     *   marks, or question marks as sentence delimiters), AND
     * - It contains at least two frustration keywords.
     */
    private function isComplexComplaint(string $lowerContent): bool
    {
        // Count sentences (rough heuristic: split on . ! ? followed by space or end)
        $sentenceCount = preg_match_all('/[.!?]+[\s]/u', $lowerContent) + 1;

        if ($sentenceCount < 2) {
            return false;
        }

        // Count frustration keywords
        $frustrationCount = 0;

        foreach (self::FRUSTRATION_KEYWORDS as $keyword) {
            if (str_contains($lowerContent, $keyword)) {
                $frustrationCount++;
            }
        }

        return $frustrationCount >= 2;
    }

    /**
     * Check if the AI has failed to answer too many times in this
     * conversation.
     *
     * Reads the `ai_failure_count` value from the conversation's latest
     * AI-generated message metadata. If the count meets or exceeds the
     * threshold, a transfer is recommended.
     */
    private function hasAiFailedTooManyTimes(Conversation $conversation): bool
    {
        // Count consecutive AI failures by inspecting the last few messages
        $recentAiMessages = $conversation->messages()
            ->where('is_ai_generated', true)
            ->latest()
            ->limit(self::MAX_AI_FAILURES_BEFORE_TRANSFER)
            ->pluck('metadata')
            ->toArray();

        if (count($recentAiMessages) < self::MAX_AI_FAILURES_BEFORE_TRANSFER) {
            return false;
        }

        // All recent AI messages must be marked as failures
        $consecutiveFailures = 0;

        foreach ($recentAiMessages as $metadata) {
            if (is_array($metadata) && ($metadata['is_failure'] ?? false) === true) {
                $consecutiveFailures++;
            } else {
                break;
            }
        }

        return $consecutiveFailures >= self::MAX_AI_FAILURES_BEFORE_TRANSFER;
    }

    /**
     * Increment the AI failure counter on the conversation metadata.
     *
     * This should be called whenever the AI pipeline encounters an
     * exception or produces a fallback response.
     */
    public function recordAiFailure(Conversation $conversation): void
    {
        $lastAiMessage = $conversation->messages()
            ->where('is_ai_generated', true)
            ->latest()
            ->first();

        if ($lastAiMessage === null) {
            return;
        }

        $metadata = $lastAiMessage->metadata ?? [];
        $metadata['is_failure'] = true;
        $metadata['failure_count'] = (($metadata['failure_count'] ?? 0) + 1);

        $lastAiMessage->update(['metadata' => $metadata]);
    }
}
