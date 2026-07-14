<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AuditController extends Controller
{
    /**
     * List audit logs with filtering and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'auditable_type' => 'nullable|string|max:255',
            'auditable_id'   => 'nullable|integer',
            'event'          => 'nullable|string|max:50',
            'user_id'        => 'nullable|exists:users,id',
            'start_date'     => 'nullable|date',
            'end_date'       => 'nullable|date|after_or_equal:start_date',
        ]);

        $query = AuditLog::query()->with('user');

        if ($request->filled('auditable_type')) {
            $query->where('auditable_type', $validated['auditable_type']);
        }

        if ($request->filled('auditable_id')) {
            $query->where('auditable_id', $validated['auditable_id']);
        }

        if ($request->filled('event')) {
            $query->where('event', $validated['event']);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $validated['user_id']);
        }

        if ($request->filled('start_date')) {
            $query->where('created_at', '>=', Carbon::parse($validated['start_date'])->startOfDay());
        }

        if ($request->filled('end_date')) {
            $query->where('created_at', '<=', Carbon::parse($validated['end_date'])->endOfDay());
        }

        $logs = $query->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => $logs->items(),
            'meta'    => [
                'current_page' => $logs->currentPage(),
                'last_page'    => $logs->lastPage(),
                'per_page'     => $logs->perPage(),
                'total'        => $logs->total(),
            ],
        ]);
    }

    /**
     * Get a single audit log with its auditable model and user.
     */
    public function show(AuditLog $auditLog): JsonResponse
    {
        $auditLog->load(['auditable', 'user']);

        return response()->json([
            'success' => true,
            'data'    => $auditLog,
        ]);
    }

    /**
     * Get all audit logs for a specific model instance.
     */
    public function getByModel(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'auditable_type' => 'required|string|max:255',
            'auditable_id'   => 'required|integer',
        ]);

        $logs = AuditLog::where('auditable_type', $validated['auditable_type'])
            ->where('auditable_id', $validated['auditable_id'])
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $logs,
        ]);
    }

    /**
     * Get audit logs filtered by a specific user.
     */
    public function getUserActivity(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'days'    => 'nullable|integer|min:1|max:90',
        ]);

        $days = $validated['days'] ?? 30;

        $logs = AuditLog::where('user_id', $validated['user_id'])
            ->where('created_at', '>=', Carbon::now()->subDays($days))
            ->with('auditable')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $logs,
        ]);
    }

    /**
     * Get audit log statistics over a given period.
     */
    public function getStats(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'days' => 'nullable|integer|min:1|max:90',
        ]);

        $days      = $validated['days'] ?? 30;
        $startDate = Carbon::now()->subDays($days);

        $baseQuery = AuditLog::where('created_at', '>=', $startDate);

        // Counts by event type
        $byEvent = (clone $baseQuery)
            ->select('event', \DB::raw('COUNT(*) as count'))
            ->groupBy('event')
            ->pluck('count', 'event');

        // Counts by user
        $byUser = (clone $baseQuery)
            ->join('users', 'audit_logs.user_id', '=', 'users.id')
            ->select('users.id', 'users.name', \DB::raw('COUNT(*) as count'))
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Counts by model type
        $byModel = (clone $baseQuery)
            ->select('auditable_type', \DB::raw('COUNT(*) as count'))
            ->groupBy('auditable_type')
            ->pluck('count', 'auditable_type');

        // Total count
        $total = (clone $baseQuery)->count();

        return response()->json([
            'success' => true,
            'data'    => [
                'total'     => $total,
                'by_event'  => $byEvent,
                'by_user'   => $byUser,
                'by_model'  => $byModel,
                'period'    => [
                    'start_date' => $startDate->toDateString(),
                    'end_date'   => Carbon::now()->toDateString(),
                    'days'       => $days,
                ],
            ],
        ]);
    }

    /**
     * Export audit logs to CSV or JSON and return a download URL.
     */
    public function export(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'format'     => 'nullable|in:csv,json',
        ]);

        $format     = $validated['format'] ?? 'csv';
        $startDate  = Carbon::parse($validated['start_date'])->startOfDay();
        $endDate    = Carbon::parse($validated['end_date'])->endOfDay();

        $logs = AuditLog::where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'audit_logs_' . $startDate->format('Y-m-d') . '_to_' . $endDate->format('Y-m-d');

        if ($format === 'json') {
            $content = $logs->toJson();
            $fullFilename = "{$filename}.json";
            $mimeType = 'application/json';
        } else {
            $content = $this->buildCsv($logs);
            $fullFilename = "{$filename}.csv";
            $mimeType = 'text/csv';
        }

        $path = "exports/{$fullFilename}";
        Storage::disk('local')->put($path, $content);

        $downloadUrl = Storage::disk('local')->temporaryUrl(
            $path,
            Carbon::now()->addHours(2)
        );

        return response()->json([
            'success' => true,
            'data'    => [
                'download_url' => $downloadUrl,
                'filename'     => $fullFilename,
                'format'       => $format,
                'record_count' => $logs->count(),
            ],
        ]);
    }

    /**
     * Build a CSV string from the audit log collection.
     */
    private function buildCsv(\Illuminate\Support\Collection $logs): string
    {
        $handle = fopen('php://temp', 'r+');

        // Header row
        fputcsv($handle, [
            'ID',
            'Event',
            'User ID',
            'User Name',
            'Auditable Type',
            'Auditable ID',
            'Old Values',
            'New Values',
            'IP Address',
            'User Agent',
            'Created At',
        ]);

        foreach ($logs as $log) {
            fputcsv($handle, [
                $log->id,
                $log->event,
                $log->user_id,
                $log->user?->name ?? 'N/A',
                $log->auditable_type,
                $log->auditable_id,
                is_array($log->old_values) ? json_encode($log->old_values) : $log->old_values,
                is_array($log->new_values) ? json_encode($log->new_values) : $log->new_values,
                $log->ip_address,
                $log->user_agent,
                $log->created_at?->toDateTimeString(),
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }
}
