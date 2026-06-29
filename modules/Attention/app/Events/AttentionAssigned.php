<?php

namespace Modules\Attention\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Attention\Models\Attention;

class AttentionAssigned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Attention $attention,
        public ?int $oldUserId,
        public ?int $newUserId
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            new Channel('attentions.assigned'),
        ];

        // Add private channel for new assigned user
        if ($this->newUserId) {
            $channels[] = new PrivateChannel('attentions.'.$this->newUserId);
        }

        // Add private channel for old assigned user (for removal notification)
        if ($this->oldUserId && $this->oldUserId !== $this->newUserId) {
            $channels[] = new PrivateChannel('attentions.'.$this->oldUserId);
        }

        return $channels;
    }
}
