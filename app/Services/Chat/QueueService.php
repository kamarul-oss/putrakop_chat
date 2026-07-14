<?php

declare(strict_types=1);

namespace App\Services\Chat;

use App\Models\Conversation;
use App\Models\Queue;
use Illuminate\Support\Facades\DB;

/**
 * Manages the department waiting queue.
 *
 * Each department maintains its own FIFO queue. Conversations are enqueued
 * when created and dequeued when assigned to an agent or closed. Position
 * and priority scoring determine the order in which agents pick up chats.
 */
final class QueueService
{
    /**
     * Add a conversation to the department queue.
     *
     * Calculates position based on the current queue depth and sets an
     * initial priority score derived from the conversation's priority
     * value (default 0). Returns the created Queue entry.
     */
    public function enqueue(Conversation $conversation): Queue
    {
        return DB::transaction(function () use ($conversation) {
            // Determine next position
            $maxPosition = Queue::where('department_id', $conversation->department_id)
                ->where('status', 'waiting')
                ->max('position') ?? 0;

            return Queue::create([
                'department_id' => $conversation->department_id,
                'conversation_id' => $conversation->id,
                'uuid' => \Illuminate\Support\Str::uuid()->toString(),
                'status' => 'waiting',
                'position' => $maxPosition + 1,
                'priority_score' => $conversation->priority ?? 0,
                'started_at' => now(),
            ]);
        });
    }

    /**
     * Remove a conversation from the queue.
     *
     * Marks the entry as assigned (or cancelled if the conversation
     * is being closed) and re-indexes the positions of remaining
     * waiting entries so there are no gaps.
     */
    public function dequeue(Conversation $conversation): void
    {
        DB::transaction(function () use ($conversation) {
            $entry = Queue::where('conversation_id', $conversation->id)
                ->where('status', 'waiting')
                ->first();

            if ($entry === null) {
                return;
            }

            $entry->update([
                'status' => 'assigned',
                'assigned_at' => now(),
            ]);

            $this->reindexPositions($conversation->department_id);
        });
    }

    /**
     * Get the next conversation in queue for a department.
     *
     * Returns the waiting conversation with the highest priority_score
     * and the lowest position (i.e. earliest arrival among equal-priority
     * entries). Returns null when the queue is empty.
     */
    public function getNext(int $departmentId): ?Conversation
    {
        $entry = Queue::where('department_id', $departmentId)
            ->where('status', 'waiting')
            ->orderByDesc('priority_score')
            ->orderBy('position')
            ->first();

        return $entry?->conversation;
    }

    /**
     * Get the current queue position for a conversation.
     *
     * Returns 1-based position or null if the conversation is not
     * currently waiting.
     */
    public function getPosition(Conversation $conversation): ?int
    {
        $entry = Queue::where('conversation_id', $conversation->id)
            ->where('status', 'waiting')
            ->first();

        return $entry?->position;
    }

    /**
     * Get aggregated statistics for a department queue.
     *
     * @return array{total_waiting: int, average_wait_seconds: int|null, longest_wait_seconds: int|null}
     */
    public function getQueueStats(int $departmentId): array
    {
        $waiting = Queue::where('department_id', $departmentId)
            ->where('status', 'waiting')
            ->get();

        $totalWaiting = $waiting->count();

        if ($totalWaiting === 0) {
            return [
                'total_waiting' => 0,
                'average_wait_seconds' => null,
                'longest_wait_seconds' => null,
            ];
        }

        $now = now();

        $waitTimes = $waiting->map(fn (Queue $entry) => (int) $entry->started_at->diffInSeconds($now));

        return [
            'total_waiting' => $totalWaiting,
            'average_wait_seconds' => (int) round($waitTimes->avg()),
            'longest_wait_seconds' => (int) $waitTimes->max(),
        ];
    }

    /**
     * Recalculate estimated wait times for all waiting entries.
     *
     * Uses a simple linear model: estimated_wait = position * avg_handle_time.
     * Avg handle time is derived from recently closed conversations in the
     * same department.
     */
    public function updateWaitTimes(int $departmentId): void
    {
        $avgHandleTime = $this->getAverageHandleTime($departmentId);

        $waitingEntries = Queue::where('department_id', $departmentId)
            ->where('status', 'waiting')
            ->orderBy('position')
            ->get();

        foreach ($waitingEntries as $entry) {
            $entry->update([
                'estimated_wait_seconds' => $entry->position * $avgHandleTime,
            ]);
        }
    }

    // ─── Private helpers ────────────────────────────────────────

    /**
     * Re-index queue positions so they are sequential (1, 2, 3, ...).
     */
    private function reindexPositions(int $departmentId): void
    {
        $entries = Queue::where('department_id', $departmentId)
            ->where('status', 'waiting')
            ->orderBy('position')
            ->get();

        foreach ($entries as $index => $entry) {
            $newPosition = $index + 1;

            if ($entry->position !== $newPosition) {
                $entry->update(['position' => $newPosition]);
            }
        }
    }

    /**
     * Calculate average handle time (in seconds) for the department.
     *
     * Looks at conversations closed in the last 24 hours. Falls back to
     * 300 seconds (5 minutes) if no data is available.
     */
    private function getAverageHandleTime(int $departmentId): int
    {
        $recentConversations = Conversation::where('department_id', $departmentId)
            ->where('status', 'closed')
            ->whereNotNull('started_at')
            ->whereNotNull('ended_at')
            ->where('ended_at', '>=', now()->subDay())
            ->get();

        if ($recentConversations->isEmpty()) {
            return 300; // Default: 5 minutes
        }

        $totalSeconds = $recentConversations->sum(fn (Conversation $c) => (int) $c->started_at->diffInSeconds($c->ended_at));

        return max(1, (int) round($totalSeconds / $recentConversations->count()));
    }
}
