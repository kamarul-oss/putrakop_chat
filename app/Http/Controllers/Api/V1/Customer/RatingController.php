<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Enums\ConversationStatus;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Rating;
use App\Services\Chat\RatingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Customer-facing rating controller.
 *
 * Allows customers to submit satisfaction ratings for closed conversations
 * and retrieve their rating history.
 */
final class RatingController extends Controller
{
    public function __construct(
        private readonly RatingService $ratingService,
    ) {}

    /**
     * Submit a rating for a closed conversation.
     *
     * POST /api/v1/customer/conversations/{conversation}/rating
     *
     * Validates that the conversation is closed, belongs to the
     * authenticated user, and has not already been rated.
     */
    public function submitRating(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        if ($conversation->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        if ($conversation->status !== ConversationStatus::Closed->value) {
            return response()->json([
                'success' => false,
                'message' => 'Rating can only be submitted for closed conversations.',
            ], 422);
        }

        if ($this->ratingService->hasRated($conversation)) {
            return response()->json([
                'success' => false,
                'message' => 'This conversation has already been rated.',
            ], 422);
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'feedback' => 'nullable|string|max:1000',
            'complaint' => 'nullable|string|max:2000',
        ]);

        $rating = $this->ratingService->submitRating(
            conversation: $conversation,
            rating: $validated['rating'],
            feedback: $validated['feedback'] ?? null,
            complaint: $validated['complaint'] ?? null,
            userId: $user->id,
        );

        return response()->json([
            'success' => true,
            'data' => [
                'rating' => $rating,
            ],
            'message' => 'Rating submitted successfully.',
        ], 201);
    }

    /**
     * Get the rating for a conversation, if it exists.
     *
     * GET /api/v1/customer/conversations/{conversation}/rating
     */
    public function getRating(Request $request, Conversation $conversation): JsonResponse
    {
        if ($conversation->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $rating = $conversation->ratings()
            ->with('creator:id,name,avatar')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'rating' => $rating,
            ],
        ]);
    }

    /**
     * Get all ratings submitted by the authenticated user.
     *
     * GET /api/v1/customer/ratings
     */
    public function getMyRatings(Request $request): JsonResponse
    {
        $ratings = Rating::where('created_by', $request->user()->id)
            ->with([
                'conversation:id,uuid,department_id,agent_id,status,ended_at',
                'conversation.department:id,name',
            ])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'ratings' => $ratings,
            ],
        ]);
    }
}
