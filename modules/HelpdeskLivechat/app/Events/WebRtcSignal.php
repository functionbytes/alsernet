<?php

namespace Modules\HelpdeskLivechat\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WebRtcSignal implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  string  $type  one of: offer, answer, ice, end
     * @param  array  $payload  signal-specific data (sdp, candidate, etc.)
     * @param  string  $direction  'to-agent' or 'to-widget' (audience)
     * @param  string|null  $pubsubToken  widget pubsub token required for 'to-widget' channel
     */
    public function __construct(
        public readonly int $conversationId,
        public readonly string $type,
        public readonly array $payload,
        public readonly string $direction = 'to-agent',
        public readonly ?string $pubsubToken = null,
    ) {}

    public function broadcastOn(): array
    {
        if ($this->direction === 'to-agent') {
            return [new PrivateChannel("webrtc.conversation.{$this->conversationId}")];
        }

        // to-widget: emit on the per-visitor token-guarded channel so only the
        // conversation owner receives the signal. The pubsub_token is stored in
        // conversation metadata and must be passed when dispatching.
        return [new Channel("helpdesk-widget-conversation.{$this->conversationId}.{$this->pubsubToken}")];
    }

    public function broadcastAs(): string
    {
        return "webrtc.{$this->type}";
    }

    public function broadcastWith(): array
    {
        return [
            'type' => $this->type,
            'payload' => $this->payload,
            'sent_at' => now()->toIso8601String(),
        ];
    }
}
