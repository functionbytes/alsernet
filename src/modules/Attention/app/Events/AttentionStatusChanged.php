<?php

namespace Modules\Attention\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Attention\Enums\AttentionStatus;
use Modules\Attention\Models\Attention;

class AttentionStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Attention $attention,
        public AttentionStatus $oldStatus,
        public AttentionStatus $newStatus
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('attentions.status'),
            new PrivateChannel('attentions.'.$this->attention->assigned_user_id),
        ];
    }
}
