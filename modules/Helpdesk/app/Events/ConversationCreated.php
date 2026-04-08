<?php

namespace App\Events\Helpdesk;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Helpdesk\Models\Conversation;

class ConversationCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $conversation;

    /**
     * Create a new event instance.
     */
    public function __construct(Conversation $conversation)
    {
        $this->conversation = $conversation;

        // Ensure relationships are loaded
        $this->conversation->load(['customer', 'status', 'assignee', 'items']);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            // Broadcast to all helpdesk agents
            new PrivateChannel('helpdesk.conversations'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'conversation.created';
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
                'subject' => $this->conversation->subject,
                'priority' => $this->conversation->priority,
                'status' => [
                    'id' => $this->conversation->status->id,
                    'name' => $this->conversation->status->name,
                    'is_open' => $this->conversation->status->is_open,
                ],
                'customer' => [
                    'id' => $this->conversation->customer->id,
                    'name' => $this->conversation->customer->name,
                    'email' => $this->conversation->customer->email,
                    'avatar_url' => $this->conversation->customer->getAvatarUrl(),
                ],
                'assignee' => $this->conversation->assignee ? [
                    'id' => $this->conversation->assignee->id,
                    'name' => $this->conversation->assignee->name,
                ] : null,
                'message_count' => $this->conversation->items->count(),
                'created_at' => $this->conversation->created_at->toIso8601String(),
                'last_message_at' => $this->conversation->last_message_at?->toIso8601String(),
            ],
        ];
    }
}
