<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DepartmentResponse;
use App\Models\User;

final class DepartmentResponsePolicy
{
    /**
     * Determine whether the user can view any department responses.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'manager', 'agent'], true);
    }

    /**
     * Determine whether the user can view the given department response.
     */
    public function view(User $user, DepartmentResponse $response): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'manager') {
            return $user->department_id === $response->department_id;
        }

        // Agent: can only view their own entries or entries in their department
        if ($user->role === 'agent') {
            return $user->department_id === $response->department_id
                && $response->created_by === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create department responses.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'manager', 'agent'], true);
    }

    /**
     * Determine whether the user can update the given department response.
     */
    public function update(User $user, DepartmentResponse $response): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'manager') {
            return $user->department_id === $response->department_id;
        }

        // Agent: can only update their own entries
        if ($user->role === 'agent') {
            return $user->department_id === $response->department_id
                && $response->created_by === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the given department response.
     */
    public function delete(User $user, DepartmentResponse $response): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'manager') {
            return $user->department_id === $response->department_id;
        }

        // Agents cannot delete
        return false;
    }

    /**
     * Determine whether the user can approve/reject the given department response.
     */
    public function approve(User $user, DepartmentResponse $response): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'manager') {
            return $user->department_id === $response->department_id;
        }

        return false;
    }
}
