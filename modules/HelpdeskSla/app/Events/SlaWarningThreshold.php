<?php

namespace Modules\HelpdeskSla\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Helpdesk\Models\Conversation;

/**
 * Fired when an open conversation crosses its SLA warning threshold (a configurable
 * percentage of the first-response or resolution window) before actually breaching.
 */
class SlaWarningThreshold implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Conversation $conversation,
        public readonly string $slaType,
        public readonly int $percentUsed,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('helpdesk.inbox')];
    }

    public function broadcastAs(): string
    {
        return 'sla.warning';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'channel' => $this->conversation->channel,
            'sla_type' => $this->slaType,
            'percent_used' => $this->percentUsed,
        ];
    }
}
