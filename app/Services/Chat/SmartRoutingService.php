<?php
declare(strict_types=1);

namespace App\Services\Chat;

use App\Models\Conversation;
use App\Models\User;
use App\Models\Department;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * Smart routing algorithm that assigns conversations to the best available agent.
 *
 * Routing strategies:
 * 1. Round Robin   – Cycle through available agents in order.
 * 2. Least Loaded  – Assign to agent with fewest active conversations.
 * 3. Skill-Based   – Match agent skills to conversation needs.
 * 4. Priority-Based – Route based on conversation queue priority score.
 *
 * The strategy is read from the department's `ai_config.routing_strategy`
 * column. When absent, least-loaded is used as the default.
 */
final class SmartRoutingService
{
    public function __construct(
        private readonly RoutingService $routingService,
        private readonly QueueService $queueService,
    ) {}

    /**
     * Find the best agent for a conversation using the configured strategy.
     */
    public function routeConversation(Conversation $conversation): ?User
    {
        $department = Department::find($conversation->department_id);

        if ($department === null) {
            Log::warning('Department not found for routing', [
                'conversation_uuid' => $conversation->uuid,
                'department_id' => $conversation->department_id,
            ]);

            return null;
        }

        // Get routing strategy from department config or default
        $strategy = $department->ai_config['routing_strategy'] ?? 'least_loaded';

        return match ($strategy) {
            'round_robin'   => $this->roundRobin($conversation),
            'skill_based'   => $this->skillBased($conversation),
            'priority_based' => $this->priorityBased($conversation),
            default         => $this->leastLoaded($conversation),
        };
    }

    // ─── Strategy implementations ───────────────────────────────

    /**
     * Round Robin – Cycle through available agents.
     *
     * Remembers the last assigned agent in the cache and picks the
     * next one in the list, wrapping around to the first when needed.
     */
    private function roundRobin(Conversation $conversation): ?User
    {
        $availableAgents = $this->routingService->getAvailableAgents($conversation->department_id);

        if ($availableAgents->isEmpty()) {
            return null;
        }

        // Get last assigned agent from cache
        $cacheKey = "routing_round_robin_{$conversation->department_id}";
        $lastAgentId = Cache::get($cacheKey);

        // Find next agent after last assigned
        $nextAgent = null;
        $foundLast = false;

        foreach ($availableAgents as $agent) {
            if ($foundLast) {
                $nextAgent = $agent;
                break;
            }
            if ($agent->id === $lastAgentId) {
                $foundLast = true;
            }
        }

        // If we didn't find one after the last, wrap around to first
        if ($nextAgent === null) {
            $nextAgent = $availableAgents->first();
        }

        // Update cache
        Cache::put($cacheKey, $nextAgent->id, now()->addHours(1));

        return $nextAgent;
    }

    /**
     * Least Loaded – Assign to agent with fewest active conversations.
     */
    private function leastLoaded(Conversation $conversation): ?User
    {
        return $this->routingService->getLeastLoadedAgent($conversation->department_id);
    }

    /**
     * Skill-Based – Match agent skills to conversation needs.
     *
     * Scores each available agent on a weighted combination of skill
     * match (70 %) and current load (30 %). The highest-scoring agent
     * wins.
     */
    private function skillBased(Conversation $conversation): ?User
    {
        $availableAgents = $this->routingService->getAvailableAgents($conversation->department_id);

        if ($availableAgents->isEmpty()) {
            return null;
        }

        // Analyze conversation for skill requirements
        $requiredSkills = $this->analyzeConversationSkills($conversation);

        // Score each agent based on skill match
        $scoredAgents = $availableAgents->map(function (User $agent) use ($requiredSkills) {
            $agentSkills = $this->getAgentSkills($agent);
            $matchScore = $this->calculateSkillMatch($requiredSkills, $agentSkills);
            $loadScore = 1 / ($this->routingService->calculateLoad($agent) + 1);

            return [
                'agent' => $agent,
                'score' => ($matchScore * 0.7) + ($loadScore * 0.3),
            ];
        });

        // Sort by score descending
        $scoredAgents = $scoredAgents->sortByDesc('score');

        return $scoredAgents->first()['agent'];
    }

    /**
     * Priority-Based – Route based on conversation queue priority.
     *
     * - High priority (≥ 80) → most experienced agent
     * - Medium priority (≥ 50) → least loaded agent
     * - Low priority  (< 50) → round robin
     */
    private function priorityBased(Conversation $conversation): ?User
    {
        $availableAgents = $this->routingService->getAvailableAgents($conversation->department_id);

        if ($availableAgents->isEmpty()) {
            return null;
        }

        $priority = $this->getConversationPriority($conversation);

        // High priority conversations go to most experienced agents
        if ($priority >= 80) {
            return $availableAgents
                ->sortByDesc(fn (User $agent) => $this->getAgentExperience($agent))
                ->first();
        }

        // Medium priority – least loaded
        if ($priority >= 50) {
            return $this->routingService->getLeastLoadedAgent($conversation->department_id);
        }

        // Low priority – round robin
        return $this->roundRobin($conversation);
    }

    // ─── Skill analysis helpers ─────────────────────────────────

    /**
     * Analyze conversation content to determine required skills.
     *
     * Examines the department name and recent message content for
     * domain-specific keywords (technical, billing, insurance).
     */
    private function analyzeConversationSkills(Conversation $conversation): array
    {
        $skills = [];

        // Check conversation metadata for department-specific needs
        $department = Department::find($conversation->department_id);

        if ($department !== null) {
            $deptName = mb_strtolower($department->name_en ?? '');

            if (str_contains($deptName, 'insurance')) {
                $skills[] = 'insurance';
            }
        }

        // Analyze recent messages for keywords
        $recentMessages = $conversation->messages()
            ->latest()
            ->limit(5)
            ->pluck('content')
            ->implode(' ');

        $lowerMessages = mb_strtolower($recentMessages);

        // Check for technical keywords
        $technicalKeywords = ['error', 'bug', 'not working', 'broken', 'technical', 'masalah', 'rosak'];
        foreach ($technicalKeywords as $keyword) {
            if (str_contains($lowerMessages, $keyword)) {
                $skills[] = 'technical';
                break;
            }
        }

        // Check for billing keywords
        $billingKeywords = ['payment', 'bill', 'invoice', 'refund', 'bayar', 'wang'];
        foreach ($billingKeywords as $keyword) {
            if (str_contains($lowerMessages, $keyword)) {
                $skills[] = 'billing';
                break;
            }
        }

        return $skills;
    }

    /**
     * Get agent skills from profile/department.
     */
    private function getAgentSkills(User $agent): array
    {
        // Default skills based on department
        $department = $agent->department;

        $skills = [$department->name_en ?? 'general'];

        // Add any additional skills from agent profile
        // This could be extended with a dedicated skills table
        return $skills;
    }

    /**
     * Calculate skill match score between required and available skills.
     *
     * Returns a float between 0.0 (no match) and 1.0 (all required
     * skills are covered). Returns 1.0 when no specific skills are
     * required.
     */
    private function calculateSkillMatch(array $required, array $available): float
    {
        if (empty($required)) {
            return 1.0;
        }

        $matches = array_intersect($required, $available);

        return count($matches) / count($required);
    }

    // ─── Experience / priority helpers ──────────────────────────

    /**
     * Get agent experience level (number of completed conversations).
     *
     * Result is cached for 5 minutes to avoid repeated COUNT queries.
     */
    private function getAgentExperience(User $agent): int
    {
        return Cache::remember(
            "agent_experience_{$agent->id}",
            now()->addMinutes(5),
            fn (): int => Conversation::where('agent_id', $agent->id)
                ->where('status', 'closed')
                ->count(),
        );
    }

    /**
     * Get the priority score for a conversation.
     *
     * Reads from the queue entry's `priority_score` column. Falls back
     * to 0 when no queue entry exists.
     */
    private function getConversationPriority(Conversation $conversation): int
    {
        $queueEntry = $conversation->queue()
            ->where('status', 'waiting')
            ->first();

        return (int) ($queueEntry->priority_score ?? 0);
    }

    // ─── Public statistics ──────────────────────────────────────

    /**
     * Get routing statistics for a department.
     */
    public function getRoutingStats(int $departmentId): array
    {
        $availableAgents = $this->routingService->getAvailableAgents($departmentId);

        return [
            'available_agents' => $availableAgents->count(),
            'agents' => $availableAgents->map(fn (User $agent) => [
                'id' => $agent->id,
                'name' => $agent->name,
                'status' => $agent->status,
                'active_conversations' => $this->routingService->calculateLoad($agent),
                'experience' => $this->getAgentExperience($agent),
            ])->values(),
            'queue_size' => $this->queueService->getQueueStats($departmentId)['total_waiting'] ?? 0,
        ];
    }
}
