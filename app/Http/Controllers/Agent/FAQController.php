<?php

declare(strict_types=1);

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFAQRequest;
use App\Http\Requests\UpdateFAQRequest;
use App\Models\DepartmentResponse;
use App\Models\User;
use App\Policies\DepartmentResponsePolicy;
use App\Services\DepartmentResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class FAQController extends Controller
{
    public function __construct(
        private readonly DepartmentResponseService $faqService,
    ) {}

    /**
     * List FAQ entries visible to the authenticated agent.
     *
     * Agents see only their own entries within their department.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        Gate::authorize('viewAny', DepartmentResponse::class);

        $responses = DepartmentResponse::query()
            ->byDepartment($user->department_id)
            ->where('created_by', $user->id)
            ->ordered()
            ->paginate(perPage: 15);

        return response()->json($responses);
    }

    /**
     * Store a new FAQ entry created by the agent.
     *
     * New entries are created as inactive and unapproved by default.
     * They require manager/admin approval before becoming visible to the AI.
     *
     * SECURITY: Uses FormRequest for input validation.
     * Mass assignment is prevented via $guarded on the model.
     */
    public function store(StoreFAQRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validated();

        // Set server-side fields (not mass-assignable)
        $response = new DepartmentResponse();
        $response->department_id = $user->department_id;
        $response->created_by = $user->id;
        $response->is_active = false;   // Pending approval
        $response->is_approved = false; // Pending approval
        $response->response_key = $validated['response_key'];
        $response->content_en = $validated['content_en'];
        $response->content_bm = $validated['content_bm'];
        $response->trigger_keywords = $validated['trigger_keywords'] ?? [];
        $response->priority = $validated['priority'] ?? 0;
        $response->save();

        return response()->json([
            'message' => 'FAQ entry created successfully. Pending approval.',
            'data' => $response,
        ], JsonResponse::HTTP_CREATED);
    }

    /**
     * Update an existing FAQ entry owned by the agent.
     *
     * SECURITY: Uses FormRequest for input validation.
     * Only own entries can be updated (enforced by Policy).
     */
    public function update(UpdateFAQRequest $request, DepartmentResponse $response): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validated();

        // Set server-side fields (not mass-assignable)
        $response->updated_by = $user->id;

        // Only update provided fields
        if (isset($validated['response_key'])) {
            $response->response_key = $validated['response_key'];
        }
        if (isset($validated['content_en'])) {
            $response->content_en = $validated['content_en'];
        }
        if (isset($validated['content_bm'])) {
            $response->content_bm = $validated['content_bm'];
        }
        if (array_key_exists('trigger_keywords', $validated)) {
            $response->trigger_keywords = $validated['trigger_keywords'];
        }
        if (isset($validated['priority'])) {
            $response->priority = $validated['priority'];
        }

        $response->save();

        return response()->json([
            'message' => 'FAQ entry updated successfully.',
            'data' => $response->fresh(),
        ]);
    }
}
