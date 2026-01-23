<?php

namespace Modules\HelpdeskChat\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\HelpdeskChat\Models\Conversations\Conversation;

class ConversationAssignedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Conversation $conversation
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.'.$this->conversation->id),
            new PrivateChannel('inbox.'.$this->conversation->inbox_id),
            new PrivateChannel('account.'.$this->conversation->account_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'conversation.assigned';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'conversation' => [
                'id' => $this->conversation->id,
                'status' => $this->conversation->status,
                'inbox_id' => $this->conversation->inbox_id,
                'contact_id' => $this->conversation->contact_id,
                'assignee' => $this->conversation->assignee ? [
                    'id' => $this->conversation->assignee->id,
                    'name' => $this->conversation->assignee->name,
                    'email' => $this->conversation->assignee->email,
                ] : null,
            ],
        ];
    }
}
