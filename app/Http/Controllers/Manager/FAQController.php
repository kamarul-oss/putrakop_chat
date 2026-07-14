<?php

declare(strict_types=1);

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\ManagerStoreFAQRequest;
use App\Http\Requests\ManagerUpdateFAQRequest;
use App\Models\DepartmentResponse;
use App\Models\User;
use App\Services\DepartmentResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class FAQController extends Controller
{
    public function __construct(
        private readonly DepartmentResponseService $faqService,
    ) {}

    /**
     * List all FAQ entries within the manager's department.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        Gate::authorize('viewAny', DepartmentResponse::class);

        $responses = DepartmentResponse::query()
            ->byDepartment($user->department_id)
            ->ordered()
            ->paginate(perPage: 20);

        return response()->json($responses);
    }

    /**
     * Store a new FAQ entry with immediate approval (managers create approved entries).
     *
     * SECURITY: Uses FormRequest for input validation.
     * Mass assignment is prevented via $guarded on the model.
     */
    public function store(ManagerStoreFAQRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validated();

        // Set server-side fields (not mass-assignable)
        $response = new DepartmentResponse();
        $response->department_id = $user->department_id;
        $response->created_by = $user->id;
        $response->is_active = $validated['is_active'] ?? true;
        $response->is_approved = $validated['is_approved'] ?? true;
        $response->response_key = $validated['response_key'];
        $response->content_en = $validated['content_en'];
        $response->content_bm = $validated['content_bm'];
        $response->trigger_keywords = $validated['trigger_keywords'] ?? [];
        $response->priority = $validated['priority'] ?? 0;
        $response->save();

        return response()->json([
            'message' => 'FAQ entry created successfully.',
            'data' => $response,
        ], JsonResponse::HTTP_CREATED);
    }

    /**
     * Update any FAQ entry within the manager's department.
     *
     * SECURITY: Uses FormRequest for input validation.
     * Only entries within own department can be updated (enforced by Policy).
     */
    public function update(ManagerUpdateFAQRequest $request, DepartmentResponse $response): JsonResponse
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
        if (array_key_exists('is_active', $validated)) {
            $response->is_active = $validated['is_active'];
        }

        $response->save();

        return response()->json([
            'message' => 'FAQ entry updated successfully.',
            'data' => $response->fresh(),
        ]);
    }

    /**
     * Delete an FAQ entry within the manager's department.
     */
    public function destroy(DepartmentResponse $response): JsonResponse
    {
        Gate::authorize('delete', $response);

        $response->delete();

        return response()->json([
            'message' => 'FAQ entry deleted successfully.',
        ]);
    }

    /**
     * Approve or reject an FAQ entry.
     *
     * PUT /manager/faq/{response}/approve
     * Body: { "is_approved": true|false }
     */
    public function approve(Request $request, DepartmentResponse $response): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        Gate::authorize('approve', $response);

        $validated = $request->validate([
            'is_approved' => 'required|boolean',
        ]);

        $response->update([
            'is_approved' => $validated['is_approved'],
            'is_active' => $validated['is_approved'] ? true : $response->is_active,
            'updated_by' => $user->id,
        ]);

        $statusText = $validated['is_approved'] ? 'approved' : 'rejected';

        return response()->json([
            "message" => "FAQ entry {$statusText} successfully.",
            'data' => $response->fresh(),
        ]);
    }
}
