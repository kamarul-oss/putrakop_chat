<?php

declare(strict_types=1);

namespace App\Services\Chat;

use App\Enums\ConversationStatus;
use App\Models\Conversation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Manages the full lifecycle of chat conversations.
 *
 * Covers creation, agent assignment, transfers, and closure —
 * coordinating with the QueueService and RoutingService to ensure
 * conversations flow through the system correctly.
 */
final class ConversationService
{
    public function __construct(
        private readonly QueueService $queueService,
        private readonly MessageService $messageService,
    ) {}

    /**
     * Create a new conversation for a customer.
     *
     * Inserts the conversation in pending status and places it
     * at the end of the department queue.
     */
    public function create(
        int $customerId,
        int $departmentId,
        string $language = 'en',
    ): Conversation {
        return DB::transaction(function () use ($customerId, $departmentId, $language) {
            $conversation = Conversation::create([
                'uuid' => Str::uuid()->toString(),
                'customer_id' => $customerId,
                'department_id' => $departmentId,
                'status' => ConversationStatus::Pending->value,
                'language' => $language,
                'started_at' => now(),
            ]);

            $this->queueService->enqueue($conversation);

            return $conversation;
        });
    }

    /**
     * Assign a conversation to an agent.
     *
     * Transitions the conversation to active status and removes it
     * from the waiting queue. A welcome system message is sent to
     * notify the customer that an agent has joined.
     */
    public function assign(Conversation $conversation, int $agentId): Conversation
    {
        return DB::transaction(function () use ($conversation, $agentId) {
            $conversation->update([
                'agent_id' => $agentId,
                'status' => ConversationStatus::Active->value,
            ]);

            $this->queueService->dequeue($conversation);

            $this->messageService->sendSystemMessage(
                $conversation,
                'An agent has been assigned to your conversation.',
            );

            return $conversation->fresh();
        });
    }

    /**
     * Transfer a conversation to another department or agent.
     *
     * When transferring to a new department the conversation is
     * re-enqueued. A system message records the transfer details.
     */
    public function transfer(
        Conversation $conversation,
        ?int $toDepartmentId = null,
        ?int $toAgentId = null,
        ?string $reason = null,
    ): Conversation {
        return DB::transaction(function () use ($conversation, $toDepartmentId, $toAgentId, $reason) {
            $oldAgentId = $conversation->agent_id;
            $oldDepartmentId = $conversation->department_id;

            $update = [
                'status' => ConversationStatus::Transferred->value,
            ];

            if ($toDepartmentId !== null) {
                $update['department_id'] = $toDepartmentId;
                $update['agent_id'] = null;
            } elseif ($toAgentId !== null) {
                $update['agent_id'] = $toAgentId;
            }

            $conversation->update($update);

            if ($toDepartmentId !== null && $toDepartmentId !== $oldDepartmentId) {
                $this->queueService->enqueue($conversation);
            }

            $this->logTransfer(
                $conversation,
                $oldAgentId,
                $oldDepartmentId,
                $toAgentId,
                $toDepartmentId,
                $reason,
            );

            return $conversation->fresh();
        });
    }

    /**
     * Close a conversation.
     *
     * Marks the conversation as closed, records the end timestamp,
     * removes it from any queue, and notifies via a system message.
     */
    public function close(Conversation $conversation, ?string $reason = null): Conversation
    {
        return DB::transaction(function () use ($conversation, $reason) {
            $conversation->update([
                'status' => ConversationStatus::Closed->value,
                'ended_at' => now(),
            ]);

            $this->queueService->dequeue($conversation);

            $this->messageService->sendSystemMessage(
                $conversation,
                $reason ?? 'Conversation has been closed.',
                ['action' => 'close'],
            );

            return $conversation->fresh();
        });
    }

    /**
     * Get all active conversations assigned to a specific agent.
     */
    public function getAgentConversations(int $agentId): Collection
    {
        return Conversation::where('agent_id', $agentId)
            ->where('status', ConversationStatus::Active->value)
            ->with(['customer', 'department'])
            ->orderByDesc('updated_at')
            ->get();
    }

    /**
     * Get conversations filtered by department and optionally by status.
     */
    public function getDepartmentConversations(
        int $departmentId,
        ?ConversationStatus $status = null,
    ) {
        $query = Conversation::where('department_id', $departmentId);

        if ($status !== null) {
            $query->where('status', $status->value);
        }

        return $query;
    }

    /**
     * Get a conversation by its public UUID.
     */
    public function getByUuid(string $uuid): ?Conversation
    {
        return Conversation::where('uuid', $uuid)->first();
    }

    // ─── Private helpers ────────────────────────────────────────

    /**
     * Record a system message documenting a conversation transfer.
     */
    private function logTransfer(
        Conversation $conversation,
        ?int $fromAgentId,
        ?int $fromDepartmentId,
        ?int $toAgentId,
        ?int $toDepartmentId,
        ?string $reason,
    ): void {
        $this->messageService->sendSystemMessage(
            $conversation,
            $reason ?? 'Conversation transferred',
            [
                'action' => 'transfer',
                'from_agent_id' => $fromAgentId,
                'from_department_id' => $fromDepartmentId,
                'to_agent_id' => $toAgentId,
                'to_department_id' => $toDepartmentId,
            ],
        );
    }
}
