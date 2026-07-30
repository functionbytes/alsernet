<?php

namespace Modules\HelpdeskTickets\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class TicketTyping implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public readonly int $ticketId,
        public readonly int $userId,
        public readonly string $userName,
        public readonly bool $isTyping,
    ) {}

    public function broadcastOn(): array
    {
        return [new PresenceChannel('ticket.'.$this->ticketId)];
    }

    public function broadcastAs(): string
    {
        return 'typing';
    }
}
