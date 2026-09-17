<?php

namespace Modules\HelpdeskTickets\Events;

use App\Events\Concerns\BroadcastsOnServedQueue;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\HelpdeskTickets\Models\Ticket;

/**
 * Event fired when a ticket is unassigned from its agent.
 */
class TicketUnassigned implements ShouldBroadcast
{
    use BroadcastsOnServedQueue, Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Ticket $ticket) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('helpdesk.tickets')];
    }

    public function broadcastAs(): string
    {
        return 'unassigned';
    }

    public function broadcastWith(): array
    {
        return ['ticket_id' => $this->ticket->id];
    }
}
