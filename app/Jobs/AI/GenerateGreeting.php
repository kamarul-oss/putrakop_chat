<?php

declare(strict_types=1);

namespace App\Jobs\AI;

use App\Models\Conversation;
use App\Services\AI\AIOrchestrator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Queue job to generate an AI greeting for a new conversation.
 *
 * Dispatched immediately when a customer starts an AI-only conversation.
 * Generates a localized greeting message and persists it, providing
 * a warm welcome without blocking the HTTP response.
 */
final class GenerateGreeting implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** @var int Maximum number of attempts. */
    public int $tries = 2;

    /** @var int Seconds before the job times out. */
    public int $timeout = 15;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly Conversation $conversation,
        public readonly string $language = 'en',
    ) {
        $this->onQueue('ai-processing');
    }

    /**
     * Execute the job.
     *
     * Delegates greeting generation to the AI orchestrator, which
     * handles localization and persists the greeting message.
     */
    public function handle(AIOrchestrator $orchestrator): void
    {
        Log::info('Generating AI greeting', [
            'conversation_id' => $this->conversation->id,
            'language' => $this->language,
        ]);

        $greeting = $orchestrator->generateGreeting($this->conversation, $this->language);

        Log::info('AI greeting generated', [
            'conversation_id' => $this->conversation->id,
            'greeting_length' => mb_strlen($greeting),
        ]);
    }

    /**
     * Handle job failure.
     *
     * Logs the failure. The conversation will remain without a greeting,
     * but the customer can still send messages and receive AI responses.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('AI greeting generation failed', [
            'conversation_id' => $this->conversation->id,
            'language' => $this->language,
            'error' => $exception->getMessage(),
        ]);
    }
}
