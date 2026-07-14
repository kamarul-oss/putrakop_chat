<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DepartmentResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Matches incoming user messages against pre-approved FAQ responses
 * stored per department. Used as the primary response layer before
 * falling back to the AI service.
 */
final class DepartmentResponseService
{
    /**
     * Find the best matching response for a user message within a department.
     *
     * Strategy:
     * 1. Load all active & approved responses for the department.
     * 2. For each response, check if any trigger keyword appears in the user message.
     * 3. Return the highest-priority match. Falls back to a generic response if nothing matches.
     */
    public function getResponse(
        int $departmentId,
        string $userMessage,
        string $language = 'en',
    ): string {
        $responses = DepartmentResponse::query()
            ->active()
            ->approved()
            ->byDepartment($departmentId)
            ->ordered()
            ->get();

        $normalizedMessage = mb_strtolower(trim($userMessage));

        foreach ($responses as $response) {
            $keywords = $response->trigger_keywords ?? [];

            if ($this->matchKeywords($normalizedMessage, $keywords)) {
                Log::info('FAQ keyword match found', [
                    'department_id' => $departmentId,
                    'response_key' => $response->response_key,
                    'language' => $language,
                ]);

                return $response->getContent($language);
            }
        }

        // No keyword match — return the fallback
        return $this->getFallbackResponse($departmentId, $language);
    }

    /**
     * Check whether any of the given trigger keywords appear in the message.
     *
     * Keywords are case-insensitive. Supports both exact word matching and
     * substring matching (for multi-word phrases).
     */
    public function matchKeywords(string $message, array $keywords): bool
    {
        if (empty($keywords)) {
            return false;
        }

        foreach ($keywords as $keyword) {
            $normalizedKeyword = mb_strtolower(trim((string) $keyword));

            if ($normalizedKeyword === '') {
                continue;
            }

            if (str_contains($message, $normalizedKeyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return a generic fallback response when no FAQ entry matches.
     *
     * This uses a hard-coded default so the system never returns empty.
     */
    public function getFallbackResponse(int $departmentId, string $language = 'en'): string
    {
        return match ($language) {
            'bm', 'ms' => 'Terima kasih atas mesej anda. Seorang ejen akan membantu anda tidak lama lagi. Sila kekal di talian.',
            default => 'Thank you for your message. An agent will assist you shortly. Please stay on the line.',
        };
    }

    /**
     * Get all active & approved responses for a department (for display/editing).
     */
    public function getDepartmentResponses(int $departmentId): Collection
    {
        return DepartmentResponse::query()
            ->active()
            ->approved()
            ->byDepartment($departmentId)
            ->ordered()
            ->get();
    }

    /**
     * Get all responses (including inactive / unapproved) for admin/manager views.
     */
    public function getAllDepartmentResponses(int $departmentId): Collection
    {
        return DepartmentResponse::query()
            ->byDepartment($departmentId)
            ->ordered()
            ->get();
    }
}
