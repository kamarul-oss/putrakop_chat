<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentController extends Controller
{
    /**
     * List all departments with pagination, search, and filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Department::query();

        // Search by name (en or bm)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name_en', 'like', "%{$search}%")
                  ->orWhere('name_bm', 'like', "%{$search}%");
            });
        }

        // Filter by is_active
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Order by priority then name
        $query->orderBy('priority', 'asc')->orderBy('name_en', 'asc');

        $departments = $query->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => $departments->items(),
            'meta'    => [
                'current_page' => $departments->currentPage(),
                'last_page'    => $departments->lastPage(),
                'per_page'     => $departments->perPage(),
                'total'        => $departments->total(),
            ],
        ]);
    }

    /**
     * Create a new department.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name_en'        => 'required|string|max:255',
            'name_bm'        => 'required|string|max:255',
            'description_en' => 'nullable|string',
            'description_bm' => 'nullable|string',
            'color'          => 'nullable|string|max:7',
            'icon'           => 'nullable|string',
            'is_active'      => 'boolean',
            'priority'       => 'integer',
            'max_queue_size' => 'integer',
            'max_agents'     => 'integer',
            'business_hours' => 'nullable|json',
            'ai_config'      => 'nullable|json',
        ]);

        $department = Department::create($validated);

        return response()->json([
            'success' => true,
            'data'    => $department,
            'message' => 'Department created',
        ], 201);
    }

    /**
     * Get a single department.
     */
    public function show(Department $department): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $department,
        ]);
    }

    /**
     * Update an existing department.
     */
    public function update(Request $request, Department $department): JsonResponse
    {
        $validated = $request->validate([
            'name_en'        => 'sometimes|required|string|max:255',
            'name_bm'        => 'sometimes|required|string|max:255',
            'description_en' => 'nullable|string',
            'description_bm' => 'nullable|string',
            'color'          => 'nullable|string|max:7',
            'icon'           => 'nullable|string',
            'is_active'      => 'boolean',
            'priority'       => 'integer',
            'max_queue_size' => 'integer',
            'max_agents'     => 'integer',
            'business_hours' => 'nullable|json',
            'ai_config'      => 'nullable|json',
        ]);

        $department->update($validated);

        return response()->json([
            'success' => true,
            'data'    => $department->fresh(),
            'message' => 'Department updated',
        ]);
    }

    /**
     * Soft delete a department (only if no active conversations exist).
     */
    public function destroy(Department $department): JsonResponse
    {
        // Check for active conversations
        $hasActiveConversations = DB::table('conversations')
            ->where('department_id', $department->id)
            ->whereIn('status', ['active', 'pending', 'transferred'])
            ->exists();

        if ($hasActiveConversations) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete department with active conversations',
            ], 422);
        }

        $department->delete();

        return response()->json([
            'success' => true,
            'message' => 'Department deleted',
        ]);
    }
}
