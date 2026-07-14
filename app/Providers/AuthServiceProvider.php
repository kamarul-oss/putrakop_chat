<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Department;
use App\Models\DepartmentResponse;
use App\Models\KnowledgeBase;
use App\Models\Setting;
use App\Models\User;
use App\Observers\DepartmentResponseObserver;
use App\Policies\DepartmentPolicy;
use App\Policies\DepartmentResponsePolicy;
use App\Policies\KnowledgeBasePolicy;
use App\Policies\SettingPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

/**
 * Auth Service Provider — registers policies and authorization logic.
 *
 * SECURITY: Explicitly registers policies to ensure Gate::authorize()
 * calls work correctly in controllers and FormRequests.
 */
final class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Department::class => DepartmentPolicy::class,
        DepartmentResponse::class => DepartmentResponsePolicy::class,
        KnowledgeBase::class => KnowledgeBasePolicy::class,
        Setting::class => SettingPolicy::class,
        User::class => UserPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
        $this->registerObservers();
        $this->registerGates();
    }

    /**
     * Register model observers.
     */
    private function registerObservers(): void
    {
        DepartmentResponse::observe(DepartmentResponseObserver::class);
    }

    /**
     * Register additional gates / authorization logic.
     */
    private function registerGates(): void
    {
        // Super admin gate — bypasses all authorization
        Gate::define('is-admin', function (User $user): bool {
            return $user->role === 'admin';
        });

        // Manager or admin gate
        Gate::define('is-manager-or-admin', function (User $user): bool {
            return in_array($user->role, ['admin', 'manager'], true);
        });

        // Any authenticated staff gate
        Gate::define('is-staff', function (User $user): bool {
            return in_array($user->role, ['admin', 'manager', 'agent'], true);
        });
    }
}
