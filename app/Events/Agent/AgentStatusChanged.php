<?php
declare(strict_types=1);

namespace App\Events\Agent;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class AgentStatusChanged implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly int $agentId,
        public readonly string $agentName,
        public readonly string $status,
        public readonly int $departmentId,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("agents.{$this->departmentId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'agent.status_changed';
    }

    public function broadcastWith(): array
    {
        return [
            'agent_id' => $this->agentId,
            'agent_name' => $this->agentName,
            'status' => $this->status,
            'department_id' => $this->departmentId,
        ];
    }
}
