<?php

declare(strict_types=1);

namespace App\Services\Chat;

use App\Enums\SenderType;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Handles all message operations within conversations.
 *
 * Responsible for creating messages (user, agent, system, AI),
 * marking messages as read, and providing paginated message retrieval.
 */
final class MessageService
{
    /**
     * Send a message in a conversation.
     *
     * Creates the message record, touches the conversation's updated_at
     * timestamp so it floats to the top of agent dashboards, and returns
     * the persisted Message model.
     */
    public function send(
        Conversation $conversation,
        int $senderId,
        string $senderType,
        string $content,
        string $messageType = 'text',
        ?array $metadata = null,
        bool $isAiGenerated = false,
    ): Message {
        return DB::transaction(function () use (
            $conversation,
            $senderId,
            $senderType,
            $content,
            $messageType,
            $metadata,
            $isAiGenerated,
        ) {
            $message = $conversation->messages()->create([
                'uuid' => Str::uuid()->toString(),
                'sender_type' => $senderType,
                'sender_id' => $senderId,
                'content' => $content,
                'message_type' => $messageType,
                'language' => $conversation->language,
                'is_ai_generated' => $isAiGenerated,
                'metadata' => $metadata,
            ]);

            // Touch conversation so it surfaces in listings
            $conversation->touch();

            return $message;
        });
    }

    /**
     * Send a system-generated message.
     *
     * System messages have no sender and are used for status changes,
     * transfer notifications, and other operational events.
     */
    public function sendSystemMessage(
        Conversation $conversation,
        string $content,
        ?array $metadata = null,
    ): Message {
        return $this->send(
            conversation: $conversation,
            senderId: 0,
            senderType: SenderType::System->value,
            content: $content,
            messageType: 'system',
            metadata: $metadata,
        );
    }

    /**
     * Mark all unread messages in a conversation as read for a specific user.
     *
     * When an agent opens a conversation, their view marks customer messages
     * as read. When a customer opens a conversation, agent messages are marked.
     */
    public function markAsRead(Conversation $conversation, int $userId): void
    {
        Conversation::withoutTimestamps(function () use ($conversation, $userId) {
            $conversation->messages()
                ->where('is_read', false)
                ->where('sender_id', '!=', $userId)
                ->update(['is_read' => true]);
        });
    }

    /**
     * Get paginated messages for a conversation.
     *
     * Supports cursor-based pagination: pass the ID of the last message
     * from the previous page as `$beforeId` to fetch the next batch.
     * Messages are returned in chronological order (oldest first).
     *
     * @param  int  $limit     Maximum number of messages to return (max 100).
     * @param  int|null  $beforeId  Cursor — only messages with ID < this value are returned.
     */
    public function getMessages(
        Conversation $conversation,
        int $limit = 50,
        ?int $beforeId = null,
    ): Collection {
        $limit = min($limit, 100);

        $query = $conversation->messages()
            ->orderByDesc('id');

        if ($beforeId !== null) {
            $query->where('id', '<', $beforeId);
        }

        // Return in chronological order
        return $query->limit($limit)->get()->reverse()->values();
    }

    /**
     * Count unread messages in a conversation.
     */
    public function countUnread(Conversation $conversation, int $userId): int
    {
        return $conversation->messages()
            ->where('is_read', false)
            ->where('sender_id', '!=', $userId)
            ->count();
    }

    /**
     * Full-text search across messages within a department.
     *
     * Uses MySQL MATCH ... AGAINST for natural-language full-text search.
     * Requires a FULLTEXT index on the messages table (content column).
     *
     * @param  int  $departmentId  Department scope to limit search.
     * @param  string  $query      Search terms.
     * @param  int  $limit         Maximum results (max 50).
     */
    public function searchMessages(
        int $departmentId,
        string $query,
        int $limit = 20,
    ): Collection {
        $limit = min($limit, 50);
        $query = trim($query);

        if ($query === '') {
            return collect();
        }

        return Message::where('department_id', $departmentId)
            ->where('message_type', '!=', 'system')
            ->whereFullText('content', $query, ['mode' => 'BOOLEAN'])
            ->with('conversation')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get the last message in a conversation.
     */
    public function getLastMessage(Conversation $conversation): ?Message
    {
        return $conversation->messages()
            ->orderByDesc('id')
            ->first();
    }
}
