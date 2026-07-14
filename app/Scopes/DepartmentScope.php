<?php

declare(strict_types=1);

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Global scope that automatically filters queries by the authenticated user's department.
 *
 * SECURITY: Ensures department isolation at the model level.
 * Admin users can bypass this scope when needed via withoutGlobalScope().
 */
final class DepartmentScope implements Scope
{
    /**
     * Apply the scope to a query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        // Admin users see all departments (bypass scope)
        if ($user && $user->role === 'admin') {
            return;
        }

        // All other users see only their department
        if ($user && $user->department_id) {
            $builder->where('department_id', $user->department_id);
        } else {
            // Fallback: no department = see nothing
            $builder->whereRaw('1 = 0');
        }
    }

    /**
     * Check if the scope is applied to the given builder.
     */
    public function isApplied(Builder $builder): bool
    {
        return $builder->hasMacro('withoutDepartmentScope') === false;
    }
}
