<?php

declare(strict_types=1);

namespace App\Services\Chat;

use App\Events\Chat\MessagesRead;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Manages read receipts for chat messages.
 *
 * Tracks which messages have been read by which users, provides unread
 * counts, and broadcasts read receipt events for real-time UI updates.
 *
 * Uses the Message.is_read column for persistence and an optional cache
 * layer for fast unread count lookups.
 */
final class ReadReceiptService
{
    private const UNREAD_CACHE_PREFIX = 'unread_';
    private const UNREAD_CACHE_TTL = 300; // 5 minutes

    /**
     * Mark all unread messages in a conversation as read for a specific user.
     *
     * Updates the is_read flag on all messages sent by OTHER users that
     * haven't been read yet. Returns the number of messages marked and
     * broadcasts a MessagesRead event so the sender sees read indicators.
     */
    public function markAsRead(Conversation $conversation, int $userId): int
    {
        $messageIds = $conversation->messages()
            ->where('is_read', false)
            ->where('sender_id', '!=', $userId)
            ->pluck('id')
            ->toArray();

        if ($messageIds === []) {
            return 0;
        }

        DB::transaction(function () use ($messageIds) {
            Message::withoutTimestamps(function () use ($messageIds) {
                Message::whereIn('id', $messageIds)
                    ->update(['is_read' => true]);
            });
        });

        $count = count($messageIds);

        // Invalidate unread count cache
        $this->clearUnreadCache($conversation->id, $userId);

        // Broadcast read receipt
        event(new MessagesRead(
            userId: $userId,
            conversation: $conversation,
            messageIds: $messageIds,
        ));

        Log::debug('Messages marked as read', [
            'conversation_id' => $conversation->id,
            'user_id' => $userId,
            'count' => $count,
        ]);

        return $count;
    }

    /**
     * Get count of unread messages for a user in a conversation.
     *
     * Uses a short-lived cache to avoid repeated COUNT queries on
     * the same conversation/user pair during rapid UI interactions.
     */
    public function getUnreadCount(Conversation $conversation, int $userId): int
    {
        $cacheKey = self::UNREAD_CACHE_PREFIX . $conversation->id . '_' . $userId;

        return (int) Cache::remember(
            $cacheKey,
            now()->addSeconds(self::UNREAD_CACHE_TTL),
            fn () => $conversation->messages()
                ->where('is_read', false)
                ->where('sender_id', '!=', $userId)
                ->count(),
        );
    }

    /**
     * Get read status for a specific message.
     *
     * Returns a structured array indicating whether the message has been
     * read and by whom. For now, read_by tracks the conversation participants
     * who have seen the message (simplified to boolean based on is_read).
     *
     * @return array{is_read: bool, read_by: list<int>, read_at: ?string}
     */
    public function getReadStatus(Message $message): array
    {
        $conversation = $message->conversation;

        // Determine who might have read this message
        // If sender is the customer (user_id), check if agent read it
        // If sender is the agent, check if customer read it
        $readByUserIds = [];

        if ((int) $message->sender_id === (int) $conversation->user_id) {
            // Customer sent this — agent may have read it
            if ($conversation->agent_id !== null && $message->is_read) {
                $readByUserIds[] = $conversation->agent_id;
            }
        } else {
            // Agent (or system) sent this — customer may have read it
            if ($message->is_read) {
                $readByUserIds[] = $conversation->user_id;
            }
        }

        return [
            'is_read' => (bool) $message->is_read,
            'read_by' => $readByUserIds,
            'read_at' => $message->is_read ? $message->updated_at?->toISOString() : null,
        ];
    }

    /**
     * Mark specific messages as read for a user.
     *
     * Useful when a client scrolls through history and wants to mark
     * a batch of messages as read without marking ALL unread messages.
     *
     * @param  int[]  $messageIds  Specific message IDs to mark as read.
     */
    public function markMultipleAsRead(Conversation $conversation, array $messageIds, int $userId): int
    {
        if ($messageIds === []) {
            return 0;
        }

        $messageIds = array_map('intval', $messageIds);

        $affectedIds = $conversation->messages()
            ->whereIn('id', $messageIds)
            ->where('is_read', false)
            ->where('sender_id', '!=', $userId)
            ->pluck('id')
            ->toArray();

        if ($affectedIds === []) {
            return 0;
        }

        DB::transaction(function () use ($affectedIds) {
            Message::withoutTimestamps(function () use ($affectedIds) {
                Message::whereIn('id', $affectedIds)
                    ->update(['is_read' => true]);
            });
        });

        $count = count($affectedIds);

        // Invalidate unread count cache
        $this->clearUnreadCache($conversation->id, $userId);

        // Broadcast read receipt
        event(new MessagesRead(
            userId: $userId,
            conversation: $conversation,
            messageIds: $affectedIds,
        ));

        Log::debug('Specific messages marked as read', [
            'conversation_id' => $conversation->id,
            'user_id' => $userId,
            'count' => $count,
        ]);

        return $count;
    }

    /**
     * Get the last message read by a user in a conversation.
     *
     * Returns the most recent message that has is_read = true and was
     * NOT sent by the specified user (i.e., a message they received).
     * Returns null if no messages have been read.
     */
    public function getLastReadMessage(Conversation $conversation, int $userId): ?Message
    {
        return $conversation->messages()
            ->where('is_read', true)
            ->where('sender_id', '!=', $userId)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Get read receipts for all messages in a conversation.
     *
     * Returns an array keyed by message_id, each containing the reader
     * info for that message. Useful for rendering read indicators on
     * the entire message history.
     *
     * @return array<int, array{message_id: int, is_read: bool, read_by: list<int>, read_at: ?string}>
     */
    public function getReadReceipts(Conversation $conversation): array
    {
        $messages = $conversation->messages()
            ->orderByAsc('id')
            ->get();

        $receipts = [];

        foreach ($messages as $message) {
            $receipts[$message->id] = [
                'message_id' => $message->id,
                ...$this->getReadStatus($message),
            ];
        }

        return $receipts;
    }

    // ─── Private helpers ────────────────────────────────────────

    /**
     * Clear the unread count cache for a user in a conversation.
     */
    private function clearUnreadCache(int $conversationId, int $userId): void
    {
        $cacheKey = self::UNREAD_CACHE_PREFIX . $conversationId . '_' . $userId;
        Cache::forget($cacheKey);
    }
}
