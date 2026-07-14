<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Manager;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeBase;
use App\Services\AI\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Manager-level AI administration controller.
 *
 * Provides AI usage statistics, a testing endpoint for verifying Gemini
 * responses, and knowledge base analytics to give managers visibility
 * into the AI subsystem's health and content coverage.
 */
final class AIController extends Controller
{
    public function __construct(
        private readonly GeminiService $geminiService,
    ) {}

    /**
     * Get AI usage statistics for the current billing period.
     *
     * Returns daily and per-minute usage against configured limits,
     * allowing managers to monitor quota consumption.
     *
     * GET /api/v1/manager/ai/usage-stats
     */
    public function getUsageStats(Request $request): JsonResponse
    {
        $stats = $this->geminiService->getUsageStats();

        return response()->json([
            'success' => true,
            'data' => [
                'daily_used' => $stats['daily_used'],
                'daily_limit' => $stats['daily_limit'],
                'rpm_used' => $stats['rpm_used'],
                'rpm_limit' => $stats['rpm_limit'],
            ],
        ]);
    }

    /**
     * Test AI response generation with a custom prompt.
     *
     * Sends the given prompt to Gemini and returns the generated
     * response along with token usage for diagnostics.
     *
     * POST /api/v1/manager/ai/test
     */
    public function testAI(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'prompt' => 'required|string|max:2000',
            'language' => 'nullable|in:en,bm',
        ]);

        $language = $validated['language'] ?? 'en';

        try {
            $response = $this->geminiService->generate(
                prompt: $validated['prompt'],
                language: $language,
                purpose: 'general_chat',
            );

            // Estimate token count (rough approximation: 1 token ~ 4 chars English, ~2 chars BM)
            $estimatedTokens = (int) ceil(mb_strlen($response) / ($language === 'bm' ? 2 : 4));

            return response()->json([
                'success' => true,
                'data' => [
                    'response' => $response,
                    'tokens_used' => $estimatedTokens,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'AI test failed: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get knowledge base statistics for the manager's view.
     *
     * Returns total article count, active count, and a per-department
     * breakdown to identify coverage gaps.
     *
     * GET /api/v1/manager/ai/kb-stats
     */
    public function getKBStats(Request $request): JsonResponse
    {
        $total = KnowledgeBase::count();

        $active = KnowledgeBase::where('is_active', true)->count();

        $byDepartment = KnowledgeBase::select('department_id', DB::raw('count(*) as total'))
            ->groupBy('department_id')
            ->get()
            ->map(fn (object $row) => [
                'department_id' => $row->department_id,
                'total' => (int) $row->total,
                'active' => KnowledgeBase::where('department_id', $row->department_id)
                    ->where('is_active', true)
                    ->count(),
            ])
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'active' => $active,
                'by_department' => $byDepartment,
            ],
        ]);
    }
}
