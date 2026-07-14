<?php

use App\Http\Controllers\Agent\FAQController as AgentFAQController;
use App\Http\Controllers\Manager\FAQController as ManagerFAQController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — FAQ Management
|--------------------------------------------------------------------------
|
| Agent routes   : CRUD for own entries (pending approval)
| Manager routes : Full CRUD + approve / reject + delete
|
| SECURITY: Rate limiting applied to prevent abuse.
|   - Agent routes: 30 requests per minute
|   - Manager routes: 60 requests per minute (higher for approval workflows)
|
*/

// ─── Agent Routes ──────────────────────────────────────────────
Route::prefix('agent')
    ->middleware(['auth', 'verified', 'throttle:agent-faq'])
    ->name('agent.')
    ->group(function () {
        Route::get('faq', [AgentFAQController::class, 'index'])->name('faq.index');
        Route::post('faq', [AgentFAQController::class, 'store'])->name('faq.store');
        Route::put('faq/{response}', [AgentFAQController::class, 'update'])->name('faq.update');
    });

// ─── Manager Routes ────────────────────────────────────────────
Route::prefix('manager')
    ->middleware(['auth', 'verified', 'throttle:manager-faq'])
    ->name('manager.')
    ->group(function () {
        Route::get('faq', [ManagerFAQController::class, 'index'])->name('faq.index');
        Route::post('faq', [ManagerFAQController::class, 'store'])->name('faq.store');
        Route::put('faq/{response}', [ManagerFAQController::class, 'update'])->name('faq.update');
        Route::delete('faq/{response}', [ManagerFAQController::class, 'destroy'])->name('faq.destroy');
        Route::put('faq/{response}/approve', [ManagerFAQController::class, 'approve'])->name('faq.approve');
    });
