<?php

namespace Modules\Attention\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Attention\Models\Attention;

class AttentionCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Attention $attention
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('attentions.created'),
            new PrivateChannel('attentions.'.$this->attention->assigned_user_id),
        ];
    }

    /**
     * Get the data that should be sent with the broadcasted event.
     */
    public function toBroadcast(): array
    {
        return [
            'id' => $this->attention->id,
            'uid' => $this->attention->uid,
            'radicado' => $this->attention->radicado,
            'subject' => $this->attention->subject,
            'customer_name' => $this->attention->full_name,
            'type' => $this->attention->type_id,
            'category' => $this->attention->category_id,
            'status' => $this->attention->status->value,
            'created_at' => $this->attention->created_at,
        ];
    }

    /**
     * Get the type of the broadcast event.
     */
    public function broadcastAs(): string
    {
        return 'attention.created';
    }
}
