<?php

namespace Modules\Helpdesk\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Helpdesk\Models\Conversation;

/**
 * Event fired when a conversation is updated
 */
class ConversationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Conversation $conversation) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversations.'.$this->conversation->id),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'subject' => $this->conversation->subject,
            'priority' => $this->conversation->priority,
            'status' => [
                'id' => $this->conversation->status->id,
                'name' => $this->conversation->status->name,
            ],
            'updated_at' => $this->conversation->updated_at->toIso8601String(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'conversation.updated';
    }
}
