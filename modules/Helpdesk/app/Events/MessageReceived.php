<?php

namespace App\Events\Helpdesk;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;

class MessageReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $conversation;

    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct(Conversation $conversation, ConversationItem $message)
    {
        $this->conversation = $conversation;
        $this->message = $message;

        // Ensure relationships are loaded
        $this->message->load(['author', 'user']);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            // Public channel for customer widget (no auth required)
            new Channel('conversation.'.$this->conversation->id),

            // Private channel for helpdesk agents (auth required)
            new PrivateChannel('helpdesk.conversations'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.received';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'message' => [
                'id' => $this->message->id,
                'type' => $this->message->type,
                'body' => $this->message->body,
                'html_body' => $this->message->html_body,
                'is_from_customer' => $this->message->isFromCustomer(),
                'is_from_agent' => $this->message->isFromAgent(),
                'is_internal' => $this->message->is_internal,
                'sender_name' => $this->message->sender_name,
                'sender_avatar' => $this->message->sender_avatar,
                'created_at' => $this->message->created_at->toIso8601String(),
            ],
            'conversation' => [
                'id' => $this->conversation->id,
                'subject' => $this->conversation->subject,
                'last_message_at' => $this->conversation->last_message_at?->toIso8601String(),
            ],
        ];
    }
}
