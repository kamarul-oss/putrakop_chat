<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Authorization policy for User management.
 *
 * Admins have full access. Users can view their own profile.
 * Only admins can create, delete users, or toggle account status.
 */
final class UserPolicy
{
    /**
     * Determine whether the user can view any users.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the given user.
     */
    public function view(User $user, User $target): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Users can always view their own profile
        return $user->id === $target->id;
    }

    /**
     * Determine whether the user can create users.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the given user.
     *
     * Admins can update any user. Users can update their own profile
     * but only limited fields (name, phone, language_preference).
     */
    public function update(User $user, User $target): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Users can update their own limited fields
        return $user->id === $target->id;
    }

    /**
     * Determine whether the user can delete the given user.
     */
    public function delete(User $user, User $target): bool
    {
        if ($user->isAdmin()) {
            // Prevent self-deletion
            return $user->id !== $target->id;
        }

        return false;
    }

    /**
     * Determine whether the user can toggle the given user's active status.
     */
    public function toggleStatus(User $user, User $target): bool
    {
        if ($user->isAdmin()) {
            // Prevent self-deactivation
            return $user->id !== $target->id;
        }

        return false;
    }
}
