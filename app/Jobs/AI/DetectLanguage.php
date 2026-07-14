<?php

declare(strict_types=1);

namespace App\Jobs\AI;

use App\Models\Message;
use App\Services\AI\LanguageDetector;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Queue job to detect the language of a message.
 *
 * Analyzes the message content using keyword frequency detection
 * and updates the message's language field. Lightweight and fast,
 * with a 1-attempt / 5-second budget to keep the pipeline moving.
 */
final class DetectLanguage implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** @var int Only try once — language detection is best-effort. */
    public int $tries = 1;

    /** @var int Very short timeout for a fast operation. */
    public int $timeout = 5;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly Message $message,
    ) {
        $this->onQueue('ai-processing');
    }

    /**
     * Execute the job.
     *
     * Detects the language and persists it on the message model.
     * If detection fails, the message retains its existing language
     * value (defaulting to 'en').
     */
    public function handle(LanguageDetector $detector): void
    {
        $detectedLanguage = $detector->detect($this->message->content);

        $this->message->update([
            'language' => $detectedLanguage,
        ]);

        Log::debug('Language detected for message', [
            'message_id' => $this->message->id,
            'detected_language' => $detectedLanguage,
        ]);
    }

    /**
     * Handle job failure.
     *
     * Language detection is best-effort — a failure does not block
     * message processing. The message keeps its default language.
     */
    public function failed(\Throwable $exception): void
    {
        Log::warning('Language detection failed', [
            'message_id' => $this->message->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
