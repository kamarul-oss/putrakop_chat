<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * List all users with pagination, search, and filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::with('department');

        // Search by name or email
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        // Filter by department_id
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }

        // Filter by is_active
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $query->orderBy('name', 'asc');

        $users = $query->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => $users->items(),
            'meta'    => [
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
                'per_page'     => $users->perPage(),
                'total'        => $users->total(),
            ],
        ]);
    }

    /**
     * Create a new user.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'                 => 'required|string|max:255',
            'email'                => 'required|email|unique:users,email',
            'password'             => 'required|string|min:8',
            'role'                 => 'required|in:customer,agent,manager,admin',
            'department_id'        => 'required_if:role,agent,manager|nullable|exists:departments,id',
            'phone'                => 'nullable|string|unique:users,phone',
            'language_preference'  => 'nullable|in:en,bm',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        return response()->json([
            'success' => true,
            'data'    => $user->load('department'),
            'message' => 'User created',
        ], 201);
    }

    /**
     * Get a single user with department.
     */
    public function show(User $user): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $user->load('department'),
        ]);
    }

    /**
     * Update an existing user.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name'                 => 'sometimes|required|string|max:255',
            'email'                => "sometimes|required|email|unique:users,email,{$user->id}",
            'password'             => 'nullable|string|min:8',
            'role'                 => 'sometimes|required|in:customer,agent,manager,admin',
            'department_id'        => 'nullable|exists:departments,id',
            'phone'                => "nullable|string|unique:users,phone,{$user->id}",
            'language_preference'  => 'nullable|in:en,bm',
            'is_active'            => 'boolean',
        ]);

        // Only hash password if provided
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'data'    => $user->fresh()->load('department'),
            'message' => 'User updated',
        ]);
    }

    /**
     * Soft delete a user (only if no active conversations).
     */
    public function destroy(User $user): JsonResponse
    {
        $hasActiveConversations = DB::table('conversations')
            ->where('agent_id', $user->id)
            ->whereIn('status', ['active', 'pending', 'transferred'])
            ->exists();

        if ($hasActiveConversations) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete user with active conversations',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted',
        ]);
    }

    /**
     * Toggle user active status.
     */
    public function toggleStatus(User $user): JsonResponse
    {
        $user->update([
            'is_active' => !$user->is_active,
        ]);

        return response()->json([
            'success' => true,
            'data'    => $user->fresh(),
            'message' => 'User status updated',
        ]);
    }
}
