<?php

namespace Modules\Reverb\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Base event for broadcasting real-time data via Reverb.
 *
 * This event can be extended by other modules to create their own
 * broadcast events. It handles the broadcasting logic and channel management.
 */
class ReverbBroadcastEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The name of the event.
     */
    public string $eventName = 'default';

    /**
     * The channels to broadcast to.
     *
     * @var array<string>
     */
    public array $channels = [];

    /**
     * The data to broadcast.
     *
     * @var array
     */
    public array $data = [];

    /**
     * Create a new event instance.
     */
    public function __construct(
        string $eventName = 'default',
        array $channels = [],
        array $data = [],
    ) {
        $this->eventName = $eventName;
        $this->channels = $channels;
        $this->data = $data;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return array_map(function (string $channel) {
            if (str_starts_with($channel, 'presence-')) {
                return new PresenceChannel($channel);
            }

            if (str_starts_with($channel, 'private-')) {
                return new PrivateChannel($channel);
            }

            return new Channel($channel);
        }, $this->channels);
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'event' => $this->eventName,
            'data' => $this->data,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return $this->eventName;
    }
}
