<?php

namespace Modules\Chat\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Chat\Models\Conversations\ConversationMessage;

class MessageSent implements ShouldBroadcast, ShouldQueue
{
    use Dispatchable, InteractsWithSockets, Queueable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public ConversationMessage $message
    ) {
        // Dispatch to dedicated 'broadcasts' queue for priority handling
        // This prevents broadcasting from blocking webhook processing
        $this->onQueue('broadcasts');
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            // Private channel for authenticated agents
            new PrivateChannel('conversation.'.$this->message->conversation_id),
            // Public channel for widget users
            new Channel('widget-conversation.'.$this->message->conversation_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        // Get sender name safely
        $senderName = 'Unknown';
        if ($this->message->sender_id && $this->message->sender_type) {
            try {
                $sender = $this->message->sender;
                if ($sender && isset($sender->name)) {
                    $senderName = $sender->name;
                }
            } catch (\Exception $e) {
                // Sender relationship failed, use fallback
                $senderName = 'Unknown';
            }
        }

        $data = [
            'id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'content' => $this->message->content,
            'message_type' => $this->message->message_type,
            'content_type' => $this->message->content_type,
            'private' => $this->message->private,
            'status' => $this->message->status,
            'created_at' => $this->message->created_at->toISOString(),
            'sender' => [
                'id' => $this->message->sender_id,
                'name' => $senderName,
                'type' => $this->message->sender_type,
            ],
        ];

        // Include attachments if present
        if ($this->message->hasAttachments()) {
            $data['attachments'] = $this->message->getMedia('attachments')->map(function ($media) {
                return [
                    'id' => $media->id,
                    'file_name' => $media->file_name,
                    'mime_type' => $media->mime_type,
                    'size' => $media->size,
                    'url' => $media->getUrl(),
                    'thumb_url' => $media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : null,
                ];
            })->toArray();
        }

        return $data;
    }
}
