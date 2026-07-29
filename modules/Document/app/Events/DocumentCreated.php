<?php

namespace Modules\Document\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Document\Entities\Document;

class DocumentCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Document $document
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('documents.created'),
            new PrivateChannel('documents.'.$this->document->user_id)
        ];
    }

    /**
     * Get the data that should be sent with the broadcasted event.
     */
    public function toBroadcast(): array
    {
        return [
            'id' => $this->document->id,
            'uid' => $this->document->uid,
            'title' => $this->document->title,
            'order_id' => $this->document->order_id,
            'order_reference' => $this->document->order_reference ?? $this->document->order_id,
            'customer_name' => $this->document->customer_firstname . ' ' . $this->document->customer_lastname,
            'type' => $this->document->document_type_id,
            'stage' => $this->document->current_stage,
            'created_at' => $this->document->created_at,
        ];
    }

    /**
     * Get the type of the broadcast event.
     */
    public function broadcastType(): string
    {
        return 'document.created';
    }
}
