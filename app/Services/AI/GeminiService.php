<?php

declare(strict_types=1);

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Google Gemini API integration service.
 *
 * Wraps the Gemini REST API for text generation, supporting both
 * standalone generation and context-aware generation with knowledge
 * base content. Tracks daily and per-minute usage against free-tier
 * quotas (1,500 RPD / 15 RPM).
 */
final class GeminiService
{
    private const DAILY_LIMIT_KEY = 'gemini_daily_count';
    private const RPM_LIMIT_KEY = 'gemini_rpm_count';
    private const DAILY_LIMIT = 1500;
    private const RPM_LIMIT = 15;

    /**
     * Generate a response using the Gemini API.
     */
    public function generate(string $prompt, string $language = 'en', string $purpose = 'general_chat'): string
    {
        $this->checkRateLimits();

        $systemPrompt = $this->buildSystemPrompt($language, $purpose);
        $fullPrompt = "{$systemPrompt}\n\nUser: {$prompt}";

        $response = $this->callApi($fullPrompt);

        $this->incrementCounters();

        return $response;
    }

    /**
     * Generate a response using Gemini with additional context (e.g. KB articles).
     */
    public function generateWithContext(
        string $prompt,
        string $context,
        string $language = 'en',
        string $purpose = 'answer_question',
    ): string {
        $this->checkRateLimits();

        $systemPrompt = $this->buildSystemPrompt($language, $purpose);
        $fullPrompt = "{$systemPrompt}\n\nContext:\n{$context}\n\nUser: {$prompt}";

        $response = $this->callApi($fullPrompt);

        $this->incrementCounters();

        return $response;
    }

    /**
     * Get current usage statistics.
     *
     * @return array{daily_used: int, daily_limit: int, rpm_used: int, rpm_limit: int}
     */
    public function getUsageStats(): array
    {
        return [
            'daily_used' => $this->getDailyUsageCount(),
            'daily_limit' => self::DAILY_LIMIT,
            'rpm_used' => $this->getRpmCount(),
            'rpm_limit' => self::RPM_LIMIT,
        ];
    }

    /**
     * Check if rate limits have been exceeded.
     */
    public function isDailyLimitReached(): bool
    {
        return $this->getDailyUsageCount() >= self::DAILY_LIMIT;
    }

    // ─── Internal helpers ───────────────────────────────────────

    private function buildSystemPrompt(string $language, string $purpose): string
    {
        $basePrompt = match ($purpose) {
            'answer_question' => $language === 'bm'
                ? 'Anda adalah pembantu khidmat pelanggan untuk PutraKop. Jawab soalan pengguna dengan tepat berdasarkan konteks yang diberi. Kekalkan nada yang mesra dan profesional.'
                : 'You are a customer service assistant for PutraKop. Answer the user\'s question accurately based on the provided context. Maintain a friendly and professional tone.',
            'general_chat' => $language === 'bm'
                ? 'Anda adalah pembantu khidmat pelanggan untuk PutraKop. Balas dalam Bahasa Melayu. Kekalkan nada yang mesra dan profesional. Jangan berikan maklumat yang tidak tepat.'
                : 'You are a customer service assistant for PutraKop. Reply in English. Maintain a friendly and professional tone. Do not provide inaccurate information.',
            default => $language === 'bm'
                ? 'Anda adalah pembantu khidmat pelanggan untuk PutraKop. Bantu pengguna dengan mesra.'
                : 'You are a customer service assistant for PutraKop. Help the user in a friendly manner.',
        };

        return $basePrompt;
    }

    private function callApi(string $prompt): string
    {
        $apiKey = config('services.gemini.api_key', '');
        $apiUrl = config('services.gemini.api_url', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent');

        if ($apiKey === '') {
            throw new RuntimeException('Gemini API key is not configured.');
        }

        $sanitizedPrompt = $this->sanitizeInput($prompt);

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $sanitizedPrompt],
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

    private function sanitizeInput(string $input): string
    {
        $cleaned = strip_tags($input);
        $cleaned = mb_substr($cleaned, 0, 2000);
        $cleaned = trim($cleaned);

        return $cleaned;
    }

    private function checkRateLimits(): void
    {
        if ($this->isDailyLimitReached()) {
            Log::warning('Gemini daily rate limit reached');
            throw new RuntimeException('Daily API rate limit has been reached. Please try again tomorrow.');
        }

        if ($this->getRpmCount() >= self::RPM_LIMIT) {
            Log::warning('Gemini RPM rate limit reached');
            throw new RuntimeException('Per-minute rate limit reached. Please wait a moment and try again.');
        }
    }

    private function getDailyUsageCount(): int
    {
        $key = self::DAILY_LIMIT_KEY . '_' . date('Y-m-d');

        return (int) cache()->get($key, 0);
    }

    private function getRpmCount(): int
    {
        $key = self::RPM_LIMIT_KEY . '_' . date('YmdHis');

        // Use a rolling minute window
        $minuteKey = self::RPM_LIMIT_KEY . '_' . date('YmdHi');

        return (int) cache()->get($minuteKey, 0);
    }

    private function incrementCounters(): void
    {
        // Daily counter
        $dailyKey = self::DAILY_LIMIT_KEY . '_' . date('Y-m-d');
        cache()->increment($dailyKey);
        cache()->put($dailyKey, cache()->get($dailyKey, 0), now()->endOfDay());

        // RPM counter (1 minute TTL)
        $rpmKey = self::RPM_LIMIT_KEY . '_' . date('YmdHi');
        cache()->increment($rpmKey);
        cache()->put($rpmKey, cache()->get($rpmKey, 0), now()->addMinute());
    }
}
