<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Services\BusinessHoursService;
use Illuminate\Http\JsonResponse;

/**
 * Public-facing department controller for customers.
 *
 * Provides read-only access to active departments with their current
 * business hours status — used by the chat widget to display department
 * availability before initiating a conversation.
 */
final class DepartmentController extends Controller
{
    public function __construct(
        private readonly BusinessHoursService $businessHoursService,
    ) {}

    /**
     * List all active departments with business hours status.
     *
     * Returns departments ordered by priority (ascending), each enriched
     * with a `business_status` object indicating whether the department
     * is currently open.
     *
     * GET /api/v1/customer/departments
     */
    public function index(): JsonResponse
    {
        $departments = Department::query()
            ->where('is_active', true)
            ->orderBy('priority', 'asc')
            ->orderBy('name_en', 'asc')
            ->get()
            ->map(function (Department $department): array {
                $status = $this->businessHoursService->getStatus($department);

                return [
                    'id' => $department->id,
                    'name' => $department->name_en,
                    'name_bm' => $department->name_bm,
                    'description' => $department->description_en,
                    'description_bm' => $department->description_bm,
                    'color' => $department->color,
                    'icon' => $department->icon,
                    'priority' => $department->priority,
                    'business_status' => $status,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $departments,
        ]);
    }

    /**
     * Get a single department with full details and business hours status.
     *
     * Includes the complete business hours configuration alongside the
     * computed status and next open time (when closed).
     *
     * GET /api/v1/customer/departments/{department}
     */
    public function show(Department $department): JsonResponse
    {
        $businessStatus = $this->businessHoursService->getStatus($department);
        $nextOpenTime = $this->businessHoursService->getNextOpenTime($department);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $department->id,
                'name' => $department->name_en,
                'name_bm' => $department->name_bm,
                'description' => $department->description_en,
                'description_bm' => $department->description_bm,
                'color' => $department->color,
                'icon' => $department->icon,
                'priority' => $department->priority,
                'business_hours' => [
                    'config' => $department->business_hours,
                    'status' => $businessStatus,
                    'next_open_time' => $nextOpenTime,
                ],
            ],
        ]);
    }
}
