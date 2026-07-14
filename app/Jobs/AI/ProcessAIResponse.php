<?php

declare(strict_types=1);

namespace App\Jobs\AI;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\AI\AIOrchestrator;
use App\Services\Chat\MessageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Queue job to process an AI response for an incoming customer message.
 *
 * Dispatched when a customer sends a message in an AI-only conversation.
 * Orchestrates the full AI pipeline (language detection, intent analysis,
 * KB search, Gemini generation) and persists the AI response message.
 *
 * Failed jobs trigger a fallback message so the customer is never left
 * waiting without acknowledgement.
 */
final class ProcessAIResponse implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** @var int Maximum number of attempts before marking as failed. */
    public int $tries = 3;

    /** @var int Seconds before the job is considered timed out. */
    public int $timeout = 30;

    /** @var int Maximum exceptions allowed before release. */
    public int $maxExceptions = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly Message $message,
        public readonly Conversation $conversation,
    ) {
        // Use the conversation's queue for orderly processing
        $this->onQueue('ai-processing');
    }

    /**
     * Execute the job.
     *
     * Process the customer message through the AI orchestrator and
     * create the AI response message on success.
     */
    public function handle(AIOrchestrator $orchestrator, MessageService $messageService): void
    {
        Log::info('Processing AI response', [
            'message_id' => $this->message->id,
            'conversation_id' => $this->conversation->id,
        ]);

        $aiResponse = $orchestrator->processMessage($this->message, $this->conversation);

        if ($aiResponse === null) {
            Log::warning('AI orchestrator returned null response', [
                'message_id' => $this->message->id,
                'conversation_id' => $this->conversation->id,
            ]);

            $this->sendFallbackMessage($messageService);

            return;
        }

        // Persist the AI response
        $messageService->send(
            conversation: $this->conversation,
            senderId: 0,
            senderType: 'ai',
            content: $aiResponse,
            messageType: 'text',
            isAiGenerated: true,
        );

        Log::info('AI response generated successfully', [
            'message_id' => $this->message->id,
            'conversation_id' => $this->conversation->id,
            'response_length' => mb_strlen($aiResponse),
        ]);
    }

    /**
     * Handle job failure.
     *
     * Sends a fallback message to the conversation so the customer
     * knows their message was received even when AI processing fails.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('AI response processing failed', [
            'message_id' => $this->message->id,
            'conversation_id' => $this->conversation->id,
            'error' => $exception->getMessage(),
            'attempt' => $this->attempts(),
        ]);

        try {
            $messageService = app(MessageService::class);
            $this->sendFallbackMessage($messageService);
        } catch (\Throwable $fallbackException) {
            Log::error('Failed to send fallback message', [
                'conversation_id' => $this->conversation->id,
                'error' => $fallbackException->getMessage(),
            ]);
        }
    }

    /**
     * Send a fallback message when AI processing fails.
     */
    private function sendFallbackMessage(MessageService $messageService): void
    {
        $language = $this->conversation->language ?? 'en';

        $fallbackContent = $language === 'bm'
            ? 'Maaf, saya mengalami masalah teknikal. Sila cuba sebentar lagi atau taip "ejen" untuk bercakap dengan ejen manusia.'
            : 'Sorry, I\'m experiencing technical difficulties. Please try again in a moment or type "agent" to speak with a human agent.';

        $messageService->sendSystemMessage(
            $this->conversation,
            $fallbackContent,
            ['type' => 'ai_fallback', 'language' => $language],
        );
    }
}
