<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Setting;
use App\Models\User;

/**
 * Authorization policy for Application Settings management.
 *
 * All operations are restricted to admin users only.
 * Settings control critical application behavior and must not
 * be accessible to non-administrators.
 */
final class SettingPolicy
{
    /**
     * Determine whether the user can view any settings.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the given setting.
     */
    public function view(User $user, Setting $setting): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can create settings.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the given setting.
     */
    public function update(User $user, Setting $setting): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the given setting.
     */
    public function delete(User $user, Setting $setting): bool
    {
        return $user->isAdmin();
    }
}
