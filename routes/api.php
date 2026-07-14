<?php
declare(strict_types=1);

use App\Http\Controllers\Api\V1\Auth\DeviceController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Admin\DepartmentController;
use App\Http\Controllers\Api\V1\Admin\UserController;
use App\Http\Controllers\Api\V1\Admin\SettingController;
use App\Http\Controllers\Api\V1\Customer\DepartmentController as CustomerDepartmentController;
use App\Http\Controllers\Api\V1\Agent\WorkspaceController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\Manager\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — PutraKop Live Chat
|--------------------------------------------------------------------------
|
| Version: v1
| Base path: /api/v1
|
*/

Route::prefix('v1')->group(function () {

    // ─── Public Routes (No Auth) ───────────────────────────────
    Route::post('auth/register', [RegisterController::class, 'register']);
    Route::post('auth/login', [LoginController::class, 'login']);
    Route::post('device/register', [DeviceController::class, 'register']);
    Route::post('device/verify', [DeviceController::class, 'verify']);

    // ─── Health Check Routes (No Auth) ──────────────────────────
    // Used by load balancers, monitoring tools (UptimeRobot, Datadog),
    // and internal dashboards. These must remain unauthenticated.
    Route::get('health', [HealthController::class, 'index']);
    Route::get('health/detailed', [HealthController::class, 'detailed']);
    Route::get('health/status', [HealthController::class, 'status']);

    // ─── Admin-Only Health Routes ───────────────────────────────
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::get('health/security', [HealthController::class, 'security']);
        Route::post('health/warm-cache', [HealthController::class, 'warmCache']);
    });

    // ─── Customer Routes (Auth required) ───────────────────────
    Route::middleware(['auth:sanctum'])->prefix('customer')->name('customer.')->group(function () {
        Route::get('departments', [CustomerDepartmentController::class, 'index']);
        Route::get('departments/{department}', [CustomerDepartmentController::class, 'show']);
    });

    // ─── Protected Routes (Require Auth) ───────────────────────
    Route::middleware(['auth:sanctum'])->group(function () {

        // Auth
        Route::post('auth/logout', [LoginController::class, 'logout']);
        Route::get('auth/me', [LoginController::class, 'me']);

        // Device Management
        Route::post('device/trust', [DeviceController::class, 'trust']);
        Route::post('device/revoke', [DeviceController::class, 'revoke']);
        Route::get('device/list', [DeviceController::class, 'list']);

        // ─── Agent Routes ─────────────────────────────────────
        Route::middleware(['role:agent'])->prefix('agent')->name('agent.')->group(function () {
            Route::get('workspace', [WorkspaceController::class, 'index']);
            Route::patch('workspace/status', [WorkspaceController::class, 'status']);
        });

        // ─── Manager Routes ───────────────────────────────────
        Route::middleware(['role:manager'])->prefix('manager')->name('manager.')->group(function () {
            Route::get('dashboard', [DashboardController::class, 'index']);
            Route::get('dashboard/agents', [DashboardController::class, 'agents']);
        });

        // ─── Admin Routes ─────────────────────────────────────
        Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
            // Departments
            Route::apiResource('departments', DepartmentController::class);

            // Users
            Route::apiResource('users', UserController::class);
            Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus']);

            // Settings
            Route::get('settings', [SettingController::class, 'index']);
            Route::put('settings', [SettingController::class, 'update']);
            Route::get('settings/{key}', [SettingController::class, 'show']);
            Route::delete('settings/{key}', [SettingController::class, 'destroy']);
        });
    });
});
