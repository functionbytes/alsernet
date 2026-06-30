<?php

namespace Modules\Helpdesk\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Helpdesk\Concerns\BroadcastsToWidgetConversation;
use Modules\Helpdesk\Models\Conversation;

class ConversationUserTyping implements ShouldBroadcast
{
    use BroadcastsToWidgetConversation, Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Conversation $conversation,
        public User $user,
        public bool $isTyping = true
    ) {}

    public function broadcastOn(): array
    {
        return array_values(array_filter([
            new PrivateChannel('conversations.'.$this->conversation->id),
            $this->widgetConversationChannel($this->conversation),
        ]));
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
            'is_typing' => $this->isTyping,
        ];
    }

    public function broadcastAs(): string
    {
        return 'UserTyping';
    }
}
