<?php

namespace Modules\HelpdeskChat\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\HelpdeskChat\Models\Conversations\Conversation;

class ConversationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Conversation $conversation,
        public string $action = 'updated'
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('account.'.$this->conversation->account_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'conversation.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->conversation->id,
            'action' => $this->action,
            'status' => $this->conversation->status,
            'priority' => $this->conversation->priority,
            'assignee_id' => $this->conversation->assignee_id,
            'team_id' => $this->conversation->team_id,
            'unread_count' => $this->conversation->messages()->where('private', false)->count(),
            'last_activity_at' => $this->conversation->last_activity_at?->toISOString(),
        ];
    }
}
