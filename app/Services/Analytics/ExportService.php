<?php
declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\Conversation;
use App\Models\Rating;
use App\Models\Message;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Handles exporting analytics data to CSV and JSON formats.
 */
final class ExportService
{
    /**
     * Export conversations to a CSV file.
     *
     * @param  int  $departmentId
     * @param  string  $startDate  Date string in Y-m-d format.
     * @param  string  $endDate    Date string in Y-m-d format.
     * @return string  The storage path of the exported file.
     */
    public function exportConversationsCsv(int $departmentId, string $startDate, string $endDate): string
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $conversations = Conversation::where('department_id', $departmentId)
            ->whereBetween('created_at', [$start, $end])
            ->with(['customer', 'agent'])
            ->get();

        $filename = 'conversations_' . $departmentId . '_' . $start->format('Ymd') . '_' . $end->format('Ymd') . '.csv';
        $path = 'exports/' . $filename;

        $headers = [
            'ID',
            'Customer Name',
            'Customer Email',
            'Agent Name',
            'Status',
            'Priority',
            'Created At',
            'Updated At',
            'Closed At',
            'Duration (seconds)',
            'Message Count',
        ];

        $rows = [];
        foreach ($conversations as $conversation) {
            $duration = $conversation->closed_at
                ? $conversation->created_at->diffInSeconds($conversation->closed_at)
                : null;

            $rows[] = [
                $conversation->id,
                $conversation->customer?->name ?? 'N/A',
                $conversation->customer?->email ?? 'N/A',
                $conversation->agent?->name ?? 'Unassigned',
                $conversation->status,
                $conversation->priority ?? 'normal',
                $conversation->created_at->toDateTimeString(),
                $conversation->updated_at->toDateTimeString(),
                $conversation->closed_at?->toDateTimeString() ?? '',
                $duration ?? '',
                $conversation->messages_count ?? $conversation->messages()->count(),
            ];
        }

        return $this->writeCsvFile($path, $headers, $rows);
    }

    /**
     * Export conversations to a JSON file.
     *
     * @param  int  $departmentId
     * @param  string  $startDate
     * @param  string  $endDate
     * @return string  The storage path of the exported file.
     */
    public function exportConversationsJson(int $departmentId, string $startDate, string $endDate): string
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $conversations = Conversation::where('department_id', $departmentId)
            ->whereBetween('created_at', [$start, $end])
            ->with(['customer', 'agent', 'messages'])
            ->get()
            ->map(fn(Conversation $conversation) => [
                'id' => $conversation->id,
                'customer' => [
                    'id' => $conversation->customer?->id,
                    'name' => $conversation->customer?->name,
                    'email' => $conversation->customer?->email,
                ],
                'agent' => [
                    'id' => $conversation->agent?->id,
                    'name' => $conversation->agent?->name,
                ],
                'status' => $conversation->status,
                'priority' => $conversation->priority,
                'created_at' => $conversation->created_at->toDateTimeString(),
                'updated_at' => $conversation->updated_at->toDateTimeString(),
                'closed_at' => $conversation->closed_at?->toDateTimeString(),
                'messages' => $conversation->messages->map(fn(Message $message) => [
                    'id' => $message->id,
                    'sender_type' => $message->sender_type,
                    'sender_id' => $message->sender_id,
                    'content' => $message->content,
                    'created_at' => $message->created_at->toDateTimeString(),
                ])->toArray(),
            ])
            ->toArray();

        $filename = 'conversations_' . $departmentId . '_' . $start->format('Ymd') . '_' . $end->format('Ymd') . '.json';
        $path = 'exports/' . $filename;

        $data = [
            'export_info' => [
                'department_id' => $departmentId,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'total_records' => count($conversations),
                'generated_at' => Carbon::now()->toDateTimeString(),
            ],
            'conversations' => $conversations,
        ];

        Storage::disk('local')->put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $path;
    }

    /**
     * Export ratings to a CSV file.
     *
     * @param  int  $departmentId
     * @param  string  $startDate
     * @param  string  $endDate
     * @return string  The storage path of the exported file.
     */
    public function exportRatingsCsv(int $departmentId, string $startDate, string $endDate): string
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $ratings = Rating::whereHas('conversation', function ($q) use ($departmentId) {
            $q->where('department_id', $departmentId);
        })
            ->whereBetween('created_at', [$start, $end])
            ->with(['conversation.customer', 'conversation.agent'])
            ->get();

        $filename = 'ratings_' . $departmentId . '_' . $start->format('Ymd') . '_' . $end->format('Ymd') . '.csv';
        $path = 'exports/' . $filename;

        $headers = [
            'Rating ID',
            'Conversation ID',
            'Customer Name',
            'Agent Name',
            'Rating',
            'Comment',
            'Created At',
        ];

        $rows = [];
        foreach ($ratings as $rating) {
            $rows[] = [
                $rating->id,
                $rating->conversation_id,
                $rating->conversation?->customer?->name ?? 'N/A',
                $rating->conversation?->agent?->name ?? 'N/A',
                $rating->rating,
                $rating->comment ?? '',
                $rating->created_at->toDateTimeString(),
            ];
        }

        return $this->writeCsvFile($path, $headers, $rows);
    }

    /**
     * Export agent performance to a CSV file.
     *
     * @param  int  $departmentId
     * @param  string  $startDate
     * @param  string  $endDate
     * @return string  The storage path of the exported file.
     */
    public function exportAgentPerformanceCsv(int $departmentId, string $startDate, string $endDate): string
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $agents = User::where('department_id', $departmentId)
            ->where('role', 'agent')
            ->where('is_active', true)
            ->get();

        $filename = 'agent_performance_' . $departmentId . '_' . $start->format('Ymd') . '_' . $end->format('Ymd') . '.csv';
        $path = 'exports/' . $filename;

        $headers = [
            'Agent ID',
            'Agent Name',
            'Email',
            'Status',
            'Total Conversations',
            'Closed Conversations',
            'Completion Rate (%)',
            'Total Messages',
            'Average Rating',
            'Total Ratings',
        ];

        $rows = [];
        foreach ($agents as $agent) {
            $conversations = Conversation::where('agent_id', $agent->id)
                ->whereBetween('created_at', [$start, $end]);

            $totalConversations = (clone $conversations)->count();
            $closedConversations = (clone $conversations)
                ->where('status', 'closed')
                ->count();

            $totalMessages = Message::where('sender_id', $agent->id)
                ->where('sender_type', 'agent')
                ->whereBetween('created_at', [$start, $end])
                ->count();

            $ratings = Rating::whereHas('conversation', function ($q) use ($agent) {
                $q->where('agent_id', $agent->id);
            })->whereBetween('created_at', [$start, $end]);

            $ratingsCount = $ratings->count();
            $averageRating = $ratingsCount > 0
                ? round($ratings->avg('rating'), 2)
                : 0;

            $completionRate = $totalConversations > 0
                ? round(($closedConversations / $totalConversations) * 100, 2)
                : 0;

            $rows[] = [
                $agent->id,
                $agent->name,
                $agent->email,
                $agent->status,
                $totalConversations,
                $closedConversations,
                $completionRate,
                $totalMessages,
                $averageRating,
                $ratingsCount,
            ];
        }

        return $this->writeCsvFile($path, $headers, $rows);
    }

    /**
     * Get a signed URL for downloading an exported file.
     *
     * @param  string  $path  The storage path of the file.
     * @param  int  $expirationMinutes  URL expiration time in minutes.
     * @return string  The signed download URL.
     */
    public function getExportUrl(string $path, int $expirationMinutes = 60): string
    {
        return Storage::disk('local')->temporaryUrl(
            $path,
            Carbon::now()->addMinutes($expirationMinutes)
        );
    }

    // ─── Private Helper Methods ──────────────────────────────────────────

    /**
     * Write data to a CSV file and return the storage path.
     *
     * @param  string  $path
     * @param  array  $headers
     * @param  array  $rows
     * @return string
     */
    private function writeCsvFile(string $path, array $headers, array $rows): string
    {
        $callback = function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');

            // Write headers
            fputcsv($handle, $headers);

            // Write data rows
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        };

        $csvContent = $this->captureCsvOutput($callback);
        Storage::disk('local')->put($path, $csvContent);

        return $path;
    }

    /**
     * Capture CSV output from a callback.
     *
     * @param  callable  $callback
     * @return string
     */
    private function captureCsvOutput(callable $callback): string
    {
        ob_start();
        $callback();
        return ob_get_clean();
    }
}
