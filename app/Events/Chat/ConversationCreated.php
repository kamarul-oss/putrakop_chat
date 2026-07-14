<?php
declare(strict_types=1);

namespace App\Events\Chat;

use App\Models\Conversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ConversationCreated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Conversation $conversation,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("agents.{$this->conversation->department_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'conversation.created';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation' => [
                'id' => $this->conversation->id,
                'uuid' => $this->conversation->uuid,
                'status' => $this->conversation->status,
                'subject' => $this->conversation->subject,
                'priority' => $this->conversation->priority,
                'created_at' => $this->conversation->created_at->toISOString(),
                'customer' => [
                    'id' => $this->conversation->user->id,
                    'name' => $this->conversation->user->name,
                    'email' => $this->conversation->user->email,
                ],
            ],
            'department_id' => $this->conversation->department_id,
        ];
    }
}
