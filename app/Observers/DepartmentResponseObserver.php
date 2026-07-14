<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\DepartmentResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Audit observer for DepartmentResponse model.
 *
 * SECURITY: Logs all create/update/delete operations for compliance and forensic analysis.
 * Supports PDPA (Malaysia Personal Data Protection Act) audit trail requirements.
 */
final class DepartmentResponseObserver
{
    /**
     * Handle the DepartmentResponse "created" event.
     */
    public function created(DepartmentResponse $response): void
    {
        $this->logAudit('created', $response, [
            'response_key' => $response->response_key,
            'department_id' => $response->department_id,
            'created_by' => $response->created_by,
            'is_approved' => $response->is_approved,
        ]);
    }

    /**
     * Handle the DepartmentResponse "updated" event.
     */
    public function updated(DepartmentResponse $response): void
    {
        $changes = $response->getChanges();

        // Remove timestamps from audit log
        unset($changes['updated_at']);

        $this->logAudit('updated', $response, $changes);
    }

    /**
     * Handle the DepartmentResponse "deleted" event.
     */
    public function deleted(DepartmentResponse $response): void
    {
        $this->logAudit('deleted', $response, [
            'response_key' => $response->response_key,
            'department_id' => $response->department_id,
        ]);
    }

    /**
     * Handle the DepartmentResponse "restored" event.
     */
    public function restored(DepartmentResponse $response): void
    {
        $this->logAudit('restored', $response, [
            'response_key' => $response->response_key,
            'department_id' => $response->department_id,
        ]);
    }

    /**
     * Handle the DepartmentResponse "forceDeleted" event.
     */
    public function forceDeleted(DepartmentResponse $response): void
    {
        $this->logAudit('force_deleted', $response, [
            'response_key' => $response->response_key,
            'department_id' => $response->department_id,
        ]);
    }

    /**
     * Log audit event with consistent structure.
     */
    private function logAudit(string $action, DepartmentResponse $response, array $context = []): void
    {
        $user = Auth::user();

        Log::info("FAQ {$action}", array_merge([
            'model' => DepartmentResponse::class,
            'model_id' => $response->id,
            'action' => $action,
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'user_role' => $user?->role,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
        ], $context));
    }
}
