<?php

namespace Modules\HelpdeskChat\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\HelpdeskChat\Models\Conversations\Conversation;

class ConversationAssigned implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Conversation $conversation,
        public ?User $assignedAgent
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('conversation.'.$this->conversation->id),
            new PrivateChannel('account.'.$this->conversation->account_id),
        ];

        // Also broadcast to assigned agent's private channel
        if ($this->assignedAgent) {
            $channels[] = new PrivateChannel('user.'.$this->assignedAgent->id);
        }

        return $channels;
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'assigned_agent' => $this->assignedAgent ? [
                'id' => $this->assignedAgent->id,
                'name' => $this->assignedAgent->name,
                'email' => $this->assignedAgent->email,
            ] : null,
            'conversation' => [
                'id' => $this->conversation->id,
                'status' => $this->conversation->status,
                'priority' => $this->conversation->priority,
            ],
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'conversation.assigned';
    }
}
