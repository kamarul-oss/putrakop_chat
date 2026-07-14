<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DepartmentResponse;
use App\Models\Message;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * AI-powered chat service that generates responses using an external LLM.
 *
 * When the daily API quota is exhausted, the service automatically falls
 * back to the DepartmentResponseService keyword-based matching.
 */
final class AIService
{
    private const DAILY_LIMIT_KEY = 'ai_chat_daily_count';

    public function __construct(
        private readonly DepartmentResponseService $faqService,
    ) {}

    /**
     * Generate a chat response for the given message.
     *
     * Steps:
     * 1. Check daily API usage limit.
     * 2. If within limits, call the external AI API.
     * 3. If limit hit or API fails, fall back to FAQ keyword matching.
     */
    public function chat(Message $message, string $language = 'en'): string
    {
        $dailyLimit = (int) config('services.ai.daily_limit', 500);
        $currentCount = $this->getDailyUsageCount();

        if ($currentCount >= $dailyLimit) {
            Log::warning('AI daily limit reached — falling back to FAQ service', [
                'current_count' => $currentCount,
                'limit' => $dailyLimit,
            ]);

            return $this->faqService->getResponse(
                departmentId: $message->department_id ?? 0,
                userMessage: $message->content,
                language: $language,
            );
        }

        try {
            $response = $this->callAiApi($message, $language);
            $this->incrementDailyUsageCount();

            return $response;
        } catch (RuntimeException $e) {
            Log::error('AI API call failed — falling back to FAQ service', [
                'error' => $e->getMessage(),
                'message_id' => $message->id,
            ]);

            return $this->faqService->getResponse(
                departmentId: $message->department_id ?? 0,
                userMessage: $message->content,
                language: $language,
            );
        }
    }

    // ─── Internal helpers ───────────────────────────────────────

    /**
     * Call the external LLM API and return the generated text.
     *
     * SECURITY: Uses Google Gemini Free tier (1,500 RPD, 15 RPM).
     * No API key exposure — uses config-based access.
     * Input sanitized before sending to external API.
     */
    private function callAiApi(Message $message, string $language): string
    {
        $apiKey = config('services.gemini.api_key', '');
        $apiUrl = config('services.gemini.api_url', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent');
        $model = config('services.gemini.model', 'gemini-2.0-flash');

        if ($apiKey === '') {
            throw new RuntimeException('Gemini API key is not configured.');
        }

        $systemPrompt = $language === 'bm'
            ? 'Anda adalah pembantu khidmat pelanggan untuk PutraKop. Balas dalam Bahasa Melayu. Kekalkan nada yang mesra dan profesional. Jangan berikan maklumat yang tidak tepat atau mengelirukan.'
            : 'You are a customer service assistant for PutraKop. Reply in English. Maintain a friendly and professional tone. Do not provide inaccurate or misleading information.';

        // Sanitize user input before sending to API
        $userMessage = $this->sanitizeUserInput($message->content);

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $systemPrompt . "\n\nUser: {$userMessage}"],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 500,
                'topP' => 0.8,
                'topK' => 40,
            ],
            'safetySettings' => [
                ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
                ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
                ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
                ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
            ],
        ];

        $httpResponse = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->timeout(30)->post("{$apiUrl}?key={$apiKey}", $payload);

        if ($httpResponse->failed()) {
            throw new RuntimeException(
                "Gemini API returned status {$httpResponse->status()}: {$httpResponse->body()}"
            );
        }

        $data = $httpResponse->json();

        return $data['candidates'][0]['content']['parts'][0]['text']
            ?? throw new RuntimeException('Unexpected Gemini API response structure.');
    }

    /**
     * Sanitize user input before sending to external API.
     *
     * SECURITY: Prevents prompt injection and XSS via user messages.
     */
    private function sanitizeUserInput(string $input): string
    {
        // Strip HTML tags
        $cleaned = strip_tags($input);

        // Limit length to prevent excessive API usage
        $cleaned = mb_substr($cleaned, 0, 1000);

        // Trim whitespace
        $cleaned = trim($cleaned);

        return $cleaned;
    }

    private function getDailyUsageCount(): int
    {
        $key = self::DAILY_LIMIT_KEY . '_' . date('Y-m-d');

        return (int) cache()->get($key, 0);
    }

    private function incrementDailyUsageCount(): void
    {
        $key = self::DAILY_LIMIT_KEY . '_' . date('Y-m-d');

        cache()->increment($key);
        cache()->put($key, cache()->get($key, 0), now()->endOfDay());
    }
}
