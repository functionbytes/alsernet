<?php

namespace Modules\Helpdesk\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Helpdesk\Models\ConversationItem;

class ConversationMessageCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ConversationItem $item,
        public bool $isNewConversation = false,
    ) {}

    /**
     * Broadcast on BOTH the conversation channel (for the open thread) and the
     * global inbox channel (so the sidebar list updates without an extra event).
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('helpdesk.conversation.'.$this->item->conversation_id),
            new PrivateChannel('helpdesk.inbox'),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        // Evita el N+1 por cada broadcast: carga en una sola pasada las relaciones
        // que consume el payload (conversación+cliente y el remitente). loadMissing
        // es no-op si quien dispara el evento ya las tenía cargadas.
        $this->item->loadMissing(['conversation.customer', 'author:id,name', 'user:id,firstname,lastname']);

        $conversation = $this->item->conversation;
        $customer = $conversation?->customer;

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
                'is_incoming' => empty($this->item->user_id) && ! empty($this->item->author_id),
                'attachment_urls' => $this->item->attachment_urls,
                'metadata' => $this->item->metadata,
                'created_at' => $this->item->created_at?->toIso8601String(),
                'sender_name' => $this->item->getSenderNameAttribute(),
                'sender_avatar' => $this->item->getSenderAvatarAttribute(),
            ],
            'conversation' => [
                'id' => $conversation?->id,
                'channel' => $conversation?->channel,
                'subject' => $conversation?->subject,
                'priority' => $conversation?->priority,
                'last_message_at' => $conversation?->last_message_at?->toIso8601String() ?? $this->item->created_at?->toIso8601String(),
                'customer' => $customer ? [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'avatar_url' => $customer->avatar_url,
                ] : null,
            ],
            'is_new_conversation' => $this->isNewConversation,
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'item.created';
    }
}
