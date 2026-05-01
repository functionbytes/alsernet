<?php

namespace Modules\Helpdesk\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Helpdesk\Models\ConversationItem;

class HelpdeskInboxUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly ConversationItem $item,
        public readonly bool $isNewConversation = false,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('helpdesk.inbox')];
    }

    public function broadcastAs(): string
    {
        return 'inbox.updated';
    }

    public function broadcastWith(): array
    {
        $conversation = $this->item->conversation;
        $customer = $conversation?->customer;

        return [
            'is_new_conversation' => $this->isNewConversation,
            'conversation' => [
                'id' => $conversation?->id,
                'channel' => $conversation?->channel,
                'subject' => $conversation?->subject,
                'priority' => $conversation?->priority,
                'is_archived' => (bool) $conversation?->is_archived,
                'last_message_at' => $conversation?->last_message_at?->toIso8601String() ?? $this->item->created_at?->toIso8601String(),
                'customer' => $customer ? [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'avatar_url' => $customer->avatar_url,
                ] : null,
            ],
            'message' => [
                'id' => $this->item->id,
                'body' => $this->item->body,
                'is_internal' => (bool) $this->item->is_internal,
                'is_incoming' => empty($this->item->user_id) && ! empty($this->item->author_id),
                'created_at' => $this->item->created_at?->toIso8601String(),
            ],
        ];
    }
}
