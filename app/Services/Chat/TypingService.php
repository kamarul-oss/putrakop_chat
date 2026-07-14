<?php

declare(strict_types=1);

namespace App\Services\Chat;

use App\Events\Chat\TypingStarted;
use App\Events\Chat\TypingStopped;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Manages typing indicators for real-time chat.
 *
 * Uses Redis cache for temporary typing status with automatic expiration.
 * Each typing entry expires after TYPING_TIMEOUT seconds if not refreshed,
 * which handles cases where a user navigates away without explicitly stopping.
 *
 * Tracking keys maintain a per-conversation index of active typers so that
 * getTypingUsers() can enumerate them without scanning the entire cache.
 */
final class TypingService
{
    private const TYPING_TIMEOUT = 10; // seconds before auto-stop
    private const CACHE_PREFIX = 'typing_';
    private const TRACK_SUFFIX = '_track';

    /**
     * Start typing indicator for a user in a conversation.
     *
     * Stores the user's typing data with a TTL so it auto-expires
     * even if the client disconnects without sending a stop event.
     */
    public function startTyping(Conversation $conversation, User $user): void
    {
        $userKey = $this->getUserKey($conversation, $user->id);
        $trackKey = $this->getTrackKey($conversation);

        // Store typing status with TTL
        $typingData = [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'started_at' => now()->toISOString(),
        ];

        Cache::put($userKey, $typingData, now()->addSeconds(self::TYPING_TIMEOUT));

        // Add user to conversation tracking index
        $this->addToTrack($trackKey, $user->id);

        // Broadcast typing started event
        event(new TypingStarted(
            userId: $user->id,
            userName: $user->name,
            conversation: $conversation,
        ));

        Log::debug('Typing started', [
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
        ]);
    }

    /**
     * Stop typing indicator for a user in a conversation.
     *
     * Removes the typing data and the tracking entry immediately.
     */
    public function stopTyping(Conversation $conversation, User $user): void
    {
        $userKey = $this->getUserKey($conversation, $user->id);
        $trackKey = $this->getTrackKey($conversation);

        // Remove typing status
        Cache::forget($userKey);

        // Remove user from tracking index
        $this->removeFromTrack($trackKey, $user->id);

        // Broadcast typing stopped event
        event(new TypingStopped(
            userId: $user->id,
            conversation: $conversation,
        ));

        Log::debug('Typing stopped', [
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
        ]);
    }

    /**
     * Get all users currently typing in a conversation.
     *
     * Reads the tracking index, then validates each entry still exists
     * in the cache (entries may have expired via TTL).
     *
     * @return array<int, array{user_id: int, user_name: string, started_at: string}>
     */
    public function getTypingUsers(Conversation $conversation): array
    {
        $trackKey = $this->getTrackKey($conversation);
        $trackedUserIds = Cache::get($trackKey, []);

        $typingUsers = [];
        $stillActive = [];

        foreach ($trackedUserIds as $userId) {
            $userKey = $this->getUserKey($conversation, $userId);
            $typingData = Cache::get($userKey);

            if ($typingData !== null) {
                $typingUsers[] = [
                    'user_id' => $userId,
                    'user_name' => $typingData['user_name'],
                    'started_at' => $typingData['started_at'],
                ];
                $stillActive[] = $userId;
            }
        }

        // Prune expired entries from the tracking index
        if (count($stillActive) !== count($trackedUserIds)) {
            Cache::put($trackKey, $stillActive, now()->addSeconds(self::TYPING_TIMEOUT));
        }

        return $typingUsers;
    }

    /**
     * Check if a specific user is typing in a conversation.
     */
    public function isTyping(Conversation $conversation, int $userId): bool
    {
        $key = $this->getUserKey($conversation, $userId);

        return Cache::has($key);
    }

    /**
     * Refresh typing indicator (extend TTL).
     *
     * Called when a client sends repeated key-press events to keep
     * the indicator alive beyond the initial timeout window.
     */
    public function refreshTyping(Conversation $conversation, User $user): void
    {
        $key = $this->getUserKey($conversation, $user->id);
        $existing = Cache::get($key);

        if ($existing !== null) {
            Cache::put($key, $existing, now()->addSeconds(self::TYPING_TIMEOUT));

            // Also refresh the track key TTL
            $trackKey = $this->getTrackKey($conversation);
            $trackedUserIds = Cache::get($trackKey, []);

            if (in_array($user->id, $trackedUserIds, true)) {
                Cache::put($trackKey, $trackedUserIds, now()->addSeconds(self::TYPING_TIMEOUT));
            }
        }
    }

    /**
     * Clear all typing indicators for a conversation.
     *
     * Useful when a conversation is closed or transferred.
     */
    public function clearConversation(Conversation $conversation): void
    {
        $trackKey = $this->getTrackKey($conversation);
        $trackedUserIds = Cache::get($trackKey, []);

        foreach ($trackedUserIds as $userId) {
            $key = $this->getUserKey($conversation, $userId);
            Cache::forget($key);
        }

        Cache::forget($trackKey);
    }

    // ─── Private helpers ────────────────────────────────────────

    private function getUserKey(Conversation $conversation, int $userId): string
    {
        return self::CACHE_PREFIX . $conversation->id . '_' . $userId;
    }

    private function getTrackKey(Conversation $conversation): string
    {
        return self::CACHE_PREFIX . $conversation->id . self::TRACK_SUFFIX;
    }

    /**
     * Add a user ID to the conversation tracking index.
     */
    private function addToTrack(string $trackKey, int $userId): void
    {
        $trackedUserIds = Cache::get($trackKey, []);

        if (! in_array($userId, $trackedUserIds, true)) {
            $trackedUserIds[] = $userId;
        }

        Cache::put($trackKey, $trackedUserIds, now()->addSeconds(self::TYPING_TIMEOUT));
    }

    /**
     * Remove a user ID from the conversation tracking index.
     */
    private function removeFromTrack(string $trackKey, int $userId): void
    {
        $trackedUserIds = Cache::get($trackKey, []);
        $trackedUserIds = array_values(array_filter(
            $trackedUserIds,
            static fn (int $id): bool => $id !== $userId,
        ));

        if ($trackedUserIds === []) {
            Cache::forget($trackKey);
        } else {
            Cache::put($trackKey, $trackedUserIds, now()->addSeconds(self::TYPING_TIMEOUT));
        }
    }
}
