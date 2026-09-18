<?php

namespace Modules\Helpdesk\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Helpdesk\Concerns\BroadcastsToWidgetConversation;
use Modules\Helpdesk\Models\Conversation;

class CustomerLanguageDetected implements ShouldBroadcast
{
    use BroadcastsToWidgetConversation, Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Conversation $conversation,
        public readonly string $language,
    ) {}

    public function broadcastOn(): array
    {
        return array_values(array_filter([
            $this->widgetConversationChannel($this->conversation),
        ]));
    }

    public function broadcastAs(): string
    {
        return 'language.detected';
    }

    public function broadcastWith(): array
    {
        return [
            'language' => $this->language,
            'conversation_id' => $this->conversation->id,
        ];
    }
}
