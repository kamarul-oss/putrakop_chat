<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Manager;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Analytics\ExportService;
use App\Services\Analytics\ReportGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Manager report controller — generates department-level analytics reports.
 *
 * Provides daily, weekly, monthly, and per-agent performance reports,
 * plus CSV export functionality for conversations, ratings, and agent data.
 */
final class ReportController extends Controller
{
    public function __construct(
        private readonly ReportGenerator $reportGenerator,
        private readonly ExportService $exportService,
    ) {}

    /**
     * Generate a daily report for the manager's department.
     *
     * Returns a snapshot of key metrics for a single day including
     * total conversations, resolution rate, average response time,
     * and customer satisfaction score.
     *
     * GET /api/v1/manager/reports/daily?date=2024-01-15
     */
    public function dailyReport(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'date' => 'nullable|date|date_format:Y-m-d',
        ]);

        $date = $validated['date'] ?? now()->toDateString();

        $report = $this->reportGenerator->dailyReport(
            departmentId: $user->department_id,
            date: $date,
        );

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Generate a weekly report for the manager's department.
     *
     * Returns aggregated metrics for the current week (Monday–Sunday)
     * with daily breakdowns and trend comparisons to the previous week.
     *
     * GET /api/v1/manager/reports/weekly
     */
    public function weeklyReport(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $report = $this->reportGenerator->weeklyReport(
            departmentId: $user->department_id,
        );

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Generate a monthly report for the manager's department.
     *
     * Returns comprehensive metrics for the specified month including
     * weekly breakdowns, agent performance rankings, peak hours analysis,
     * and satisfaction trends.
     *
     * GET /api/v1/manager/reports/monthly?month=1&year=2024
     */
    public function monthlyReport(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2030',
        ]);

        $report = $this->reportGenerator->monthlyReport(
            departmentId: $user->department_id,
            month: $validated['month'],
            year: $validated['year'],
        );

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Generate a performance report for a specific agent.
     *
     * Returns per-agent metrics including conversations handled,
     * average resolution time, satisfaction ratings, and comparison
     * to department averages. The agent must belong to the manager's
     * department.
     *
     * GET /api/v1/manager/reports/agent/{agentId}?days=30
     */
    public function agentReport(Request $request, User $agent): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'days' => 'nullable|integer|min:1|max:90',
        ]);

        $days = $validated['days'] ?? 30;

        // Ensure the agent belongs to the manager's department
        if ($agent->department_id !== $user->department_id) {
            return response()->json([
                'success' => false,
                'message' => 'Agent does not belong to your department.',
            ], 403);
        }

        if (!$agent->isAgent()) {
            return response()->json([
                'success' => false,
                'message' => 'The specified user is not an agent.',
            ], 422);
        }

        $report = $this->reportGenerator->agentReport(
            departmentId: $user->department_id,
            agentId: $agent->id,
            days: $days,
        );

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Export department data to a CSV file.
     *
     * Generates a CSV download for conversations, ratings, or agent
     * activity within the specified date range. The export is scoped
     * to the manager's department.
     *
     * GET /api/v1/manager/reports/export
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'type' => 'required|in:conversations,ratings,agents',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $filename = $this->exportService->generate(
            departmentId: $user->department_id,
            type: $validated['type'],
            startDate: $validated['start_date'],
            endDate: $validated['end_date'],
        );

        $filePath = storage_path("app/exports/{$filename}");

        return response()->streamDownload(function () use ($filePath) {
            echo file_get_contents($filePath);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
