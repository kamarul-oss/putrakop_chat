<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Department;
use App\Models\User;

/**
 * Authorization policy for Department management.
 *
 * Only admins can create, update, or delete departments.
 * Managers and agents are restricted to viewing their own department.
 */
final class DepartmentPolicy
{
    /**
     * Determine whether the user can view any departments.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'manager', 'agent'], true);
    }

    /**
     * Determine whether the user can view the given department.
     */
    public function view(User $user, Department $department): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Managers and agents can only view their own department
        return $user->department_id === $department->id;
    }

    /**
     * Determine whether the user can create departments.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the given department.
     */
    public function update(User $user, Department $department): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the given department.
     */
    public function delete(User $user, Department $department): bool
    {
        return $user->isAdmin();
    }
}
