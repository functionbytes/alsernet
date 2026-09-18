<?php

namespace Modules\Helpdesk\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Helpdesk\Concerns\BroadcastsToWidgetConversation;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationStatus;

class ConversationStatusChanged implements ShouldBroadcast
{
    use BroadcastsToWidgetConversation, Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Conversation $conversation,
        public readonly ConversationStatus $newStatus,
        public readonly ?int $byUserId = null,
    ) {}

    public function broadcastOn(): array
    {
        return array_values(array_filter([
            $this->widgetConversationChannel($this->conversation),
        ]));
    }

    public function broadcastAs(): string
    {
        return 'conversation.status.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'status_slug' => $this->newStatus->slug ?? null,
            'status_name' => $this->newStatus->name ?? null,
            'is_open' => $this->newStatus->is_open ?? false,
            'is_closed' => ! ($this->newStatus->is_open ?? false),
        ];
    }
}
