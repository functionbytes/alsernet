<?php

namespace Modules\Helpdesk\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\ConversationItem;

class ConversationItemCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly ConversationItem $item) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('helpdesk.conversation.'.$this->item->conversation_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'item.created';
    }

    public function broadcastWith(): array
    {
        $replyToId = $this->item->metadata['reply_to_id'] ?? null;
        $replyTo = null;
        if ($replyToId) {
            $original = ConversationItem::find($replyToId);
            if ($original) {
                $replyTo = [
                    'id' => $original->id,
                    'author' => $original->user?->name ?? 'Cliente',
                    'body' => Str::limit((string) $original->body, 80),
                ];
            }
        }

        return [
            'id' => $this->item->id,
            'conversation_id' => $this->item->conversation_id,
            'user_id' => $this->item->user_id,
            'type' => $this->item->type,
            'body' => $this->item->body,
            'is_internal' => (bool) $this->item->is_internal,
            'is_outgoing' => ! empty($this->item->user_id),
            'author' => $this->item->user?->name ?? $this->item->author?->name,
            'time' => $this->item->created_at?->format('H:i'),
            'created_at' => $this->item->created_at?->toIso8601String(),
            'attachment_urls' => $this->item->attachment_urls ?? [],
            'attachments' => $this->item->metadata['attachments'] ?? [],
            'reply_to' => $replyTo,
        ];
    }
}
