<?php

namespace Modules\Helpdesk\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\ConversationItem;

class ConversationItemCreated implements ShouldBroadcastNow
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
            'message' => [
                'id' => $this->item->id,
                'conversation_id' => $this->item->conversation_id,
                'user_id' => $this->item->user_id,
                'author_id' => $this->item->author_id,
                'type' => $this->item->type,
                'body' => $this->item->body,
                'html_body' => $this->item->html_body,
                'is_internal' => (bool) $this->item->is_internal,
                'attachment_urls' => $this->item->attachment_urls ?? [],
                'attachments' => $this->item->metadata['attachments'] ?? [],
                'created_at' => $this->item->created_at?->toIso8601String(),
                'sender_name' => $this->item->user?->name ?? $this->item->author?->name,
                'sender_avatar' => null,
                'reply_to' => $replyTo,
            ],
        ];
    }
}
