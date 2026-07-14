<?php
declare(strict_types=1);

namespace App\Events\Chat;

use App\Models\Conversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ConversationAssigned implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Conversation $conversation,
        public readonly int $agentId,
        public readonly string $agentName,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("conversation.{$this->conversation->id}"),
            new PrivateChannel("agents.{$this->conversation->department_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'conversation.assigned';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation' => [
                'id' => $this->conversation->id,
                'uuid' => $this->conversation->uuid,
                'status' => $this->conversation->status,
                'department_id' => $this->conversation->department_id,
                'customer' => [
                    'id' => $this->conversation->user->id,
                    'name' => $this->conversation->user->name,
                    'email' => $this->conversation->user->email,
                ],
                'department' => [
                    'id' => $this->conversation->department->id,
                    'name' => $this->conversation->department->name,
                ],
            ],
            'agent_id' => $this->agentId,
            'agent_name' => $this->agentName,
        ];
    }
}
