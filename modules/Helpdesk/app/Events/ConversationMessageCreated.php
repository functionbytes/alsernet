<?php

namespace App\Events\Helpdesk;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Helpdesk\Models\ConversationItem;

class ConversationMessageCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ConversationItem $item) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversations.'.$this->item->conversation_id),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->item->id,
                'conversation_id' => $this->item->conversation_id,
                'author_id' => $this->item->author_id,
                'user_id' => $this->item->user_id,
                'type' => $this->item->type,
                'body' => $this->item->body,
                'html_body' => $this->item->html_body,
                'is_internal' => $this->item->is_internal,
                'attachment_urls' => $this->item->attachment_urls,
                'created_at' => $this->item->created_at,
                'sender_name' => $this->item->getSenderNameAttribute(),
                'sender_avatar' => $this->item->getSenderAvatarAttribute(),
            ],
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'ConversationMessageCreated';
    }
}
