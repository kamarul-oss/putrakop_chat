<?php

declare(strict_types=1);

namespace App\Services\Chat;

use App\Enums\AgentStatus;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Routes conversations to the most suitable available agent.
 *
 * Considers agent online status, current workload (active conversation count),
 * and department assignment when selecting an agent. Supports both automatic
 * routing and manual lookups for the routing dashboard.
 */
final class RoutingService
{
    /** @var int Default maximum concurrent conversations per agent. */
    private const DEFAULT_MAX_CONVERSATIONS = 5;

    public function __construct(
        private readonly ConversationService $conversationService,
    ) {}

    /**
     * Find and assign the best available agent for a conversation.
     *
     * Returns the assigned Agent User model, or null if no agent is
     * currently available (the conversation stays in the queue).
     */
    public function routeToAgent(Conversation $conversation): ?User
    {
        $agent = $this->getLeastLoadedAgent($conversation->department_id);

        if ($agent === null) {
            return null;
        }

        $this->conversationService->assign($conversation, $agent->id);

        return $agent;
    }

    /**
     * Get all agents in a department who are eligible to receive chats.
     *
     * Criteria:
     * - role = 'agent'
     * - department_id matches
     * - status = 'online'
     * - active conversation count < max allowed
     *
     * @return Collection<int, User>
     */
    public function getAvailableAgents(int $departmentId): Collection
    {
        return User::where('department_id', $departmentId)
            ->where('role', 'agent')
            ->where('status', AgentStatus::Online->value)
            ->where('is_active', true)
            ->get()
            ->filter(fn (User $agent) => $this->calculateLoad($agent) < $this->getMaxConversations($agent))
            ->values();
    }

    /**
     * Find the agent with the fewest active conversations.
     *
     * Returns null when no agents are available. When multiple agents
     * share the same lowest load, the first one found is returned.
     */
    public function getLeastLoadedAgent(int $departmentId): ?User
    {
        return $this->getAvailableAgents($departmentId)
            ->sortBy(fn (User $agent) => $this->calculateLoad($agent))
            ->first();
    }

    /**
     * Count active conversations assigned to an agent.
     */
    public function calculateLoad(User $agent): int
    {
        return Conversation::where('agent_id', $agent->id)
            ->where('status', 'active')
            ->count();
    }

    /**
     * Get the maximum concurrent conversations an agent can handle.
     *
     * Reads from a config value. Falls back to the class constant if
     * the config key is missing.
     */
    private function getMaxConversations(User $agent): int
    {
        return (int) config('chat.max_conversations_per_agent', self::DEFAULT_MAX_CONVERSATIONS);
    }
}
