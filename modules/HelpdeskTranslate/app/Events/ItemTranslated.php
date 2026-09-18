<?php

namespace Modules\HelpdeskTranslate\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Notifies the open agent inbox that a queued auto-translation finished, so
 * the already-rendered bubble can be patched in place instead of requiring a
 * page reload (translation runs async, after the item's own broadcast).
 */
class ItemTranslated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $itemId,
        public readonly int $conversationId,
        public readonly string $field,
        public readonly string $translatedBody,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('helpdesk.conversation.'.$this->conversationId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'item.translated';
    }

    public function broadcastWith(): array
    {
        return [
            'item_id' => $this->itemId,
            'conversation_id' => $this->conversationId,
            'field' => $this->field,
            'translated_body' => $this->translatedBody,
        ];
    }
}
