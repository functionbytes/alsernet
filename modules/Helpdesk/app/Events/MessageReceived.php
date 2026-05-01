<?php

namespace Modules\Helpdesk\Events;

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
            // Public channel for the Helpdesk widget (no auth required)
            new Channel('helpdesk-widget-conversation.'.$this->conversation->id),

            // Backwards-compatible public channel
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
        $isAgent = method_exists($this->message, 'isFromAgent') ? (bool) $this->message->isFromAgent() : ! is_null($this->message->user_id);

        $attachments = $this->normalizeAttachments();

        return [
            // ── Widget-friendly flat shape (consumed by Helpdesk React widget) ──
            'id' => $this->message->id,
            'conversation_id' => $this->conversation->id,
            'content' => $this->message->body,
            'content_type' => $this->resolveContentType($attachments),
            'message_type' => $isAgent ? 'outgoing' : 'incoming',
            'created_at' => $this->message->created_at->toIso8601String(),
            'sender' => [
                'id' => $isAgent ? $this->message->user_id : $this->message->author_id,
                'type' => $isAgent ? 'User' : 'Customer',
                'name' => $this->message->sender_name ?? ($isAgent ? 'Agent' : 'Visitor'),
            ],
            'attachments' => $attachments,

            // ── Legacy nested shape (preserved for existing listeners) ──
            'message' => [
                'id' => $this->message->id,
                'type' => $this->message->type,
                'body' => $this->message->body,
                'html_body' => $this->message->html_body,
                'is_from_customer' => method_exists($this->message, 'isFromCustomer') ? $this->message->isFromCustomer() : ! $isAgent,
                'is_from_agent' => $isAgent,
                'is_internal' => $this->message->is_internal,
                'sender_name' => $this->message->sender_name ?? null,
                'sender_avatar' => $this->message->sender_avatar ?? null,
                'created_at' => $this->message->created_at->toIso8601String(),
            ],
            'conversation' => [
                'id' => $this->conversation->id,
                'subject' => $this->conversation->subject,
                'last_message_at' => $this->conversation->last_message_at?->toIso8601String(),
            ],
        ];
    }

    /**
     * @return array<int, array{id: int, name: string, url: string, size: int, mime_type: string}>
     */
    private function normalizeAttachments(): array
    {
        $raw = $this->message->attachment_urls ?? [];
        if (! is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $i => $a) {
            if (is_string($a)) {
                $out[] = [
                    'id' => $i,
                    'name' => basename($a),
                    'url' => $a,
                    'size' => 0,
                    'mime_type' => '',
                ];

                continue;
            }
            $out[] = [
                'id' => $i,
                'name' => (string) ($a['name'] ?? basename($a['url'] ?? '')),
                'url' => (string) ($a['url'] ?? ''),
                'size' => (int) ($a['size'] ?? 0),
                'mime_type' => (string) ($a['mime_type'] ?? ''),
            ];
        }

        return $out;
    }

    /**
     * @param  array<int, array{mime_type?: string}>  $attachments
     */
    private function resolveContentType(array $attachments): string
    {
        if (empty($attachments)) {
            return 'text';
        }
        $mime = $attachments[0]['mime_type'] ?? '';

        return match (true) {
            str_starts_with($mime, 'image/') => 'image',
            str_starts_with($mime, 'audio/') => 'audio',
            str_starts_with($mime, 'video/') => 'video',
            default => 'file',
        };
    }
}
