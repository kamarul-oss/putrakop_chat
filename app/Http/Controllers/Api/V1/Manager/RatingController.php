<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Manager;

use App\Http\Controllers\Controller;
use App\Models\Rating;
use App\Models\User;
use App\Services\Chat\RatingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Manager-facing rating controller.
 *
 * Provides department-level rating analytics including averages,
 * distribution breakdowns, trends, and detailed rating inspection.
 */
final class RatingController extends Controller
{
    public function __construct(
        private readonly RatingService $ratingService,
    ) {}

    /**
     * Get aggregated rating data for the manager's department.
     *
     * GET /api/v1/manager/ratings?days=30
     *
     * Returns average rating, total count, rating distribution (1–5),
     * and the most recent individual ratings.
     */
    public function getDepartmentRatings(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'days' => 'nullable|integer|min:1|max:90',
        ]);

        $days = $validated['days'] ?? 30;
        $departmentId = $user->department_id;

        $average = $this->ratingService->getAverageRating($departmentId, $days);
        $distribution = $this->ratingService->getRatingDistribution($departmentId, $days);
        $recent = $this->ratingService->getRecentRatings($departmentId, 10);

        $total = array_sum($distribution);

        return response()->json([
            'success' => true,
            'data' => [
                'average' => $average,
                'total' => $total,
                'distribution' => $distribution,
                'recent' => $recent,
            ],
        ]);
    }

    /**
     * Get detailed information for a specific rating.
     *
     * GET /api/v1/manager/ratings/{rating}
     *
     * Includes the full conversation context (messages, participants,
     * timestamps) for the manager to review.
     */
    public function getRatingDetails(Request $request, Rating $rating): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Ensure the rating belongs to a conversation in the manager's department
        $conversation = $rating->conversation;

        if ($conversation === null || $conversation->department_id !== $user->department_id) {
            return response()->json([
                'success' => false,
                'message' => 'Rating not found in your department.',
            ], 404);
        }

        $rating->load([
            'creator:id,name,avatar,email',
            'conversation' => function ($query) use ($user): void {
                $query->with([
                    'customer:id,name,avatar,email',
                    'agent:id,name,avatar,email',
                    'department:id,name',
                ]);
            },
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'rating' => $rating,
                'conversation' => $conversation,
            ],
        ]);
    }
}
