<?php

namespace Modules\HelpdeskTickets\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\HelpdeskTickets\Models\Ticket;

class TicketUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     *                                                                 Per-field diff: ['status_id' => ['old' => 1, 'new' => 2], ...].
     *                                                                 Empty when the event is dispatched without explicit diff context.
     */
    public function __construct(
        public readonly Ticket $ticket,
        public readonly array $changes = []
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('helpdesk.tickets'),
            new PrivateChannel('helpdesk.ticket.'.$this->ticket->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ticket.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'ticket' => [
                'id' => $this->ticket->id,
                'ticket_number' => $this->ticket->ticket_number,
                'subject' => $this->ticket->subject,
                'status_id' => $this->ticket->status_id,
                'priority' => $this->ticket->priority,
                'assigned_user_id' => $this->ticket->assigned_user_id,
                'updated_at' => $this->ticket->updated_at?->toIso8601String(),
            ],
            'changes' => $this->changes,
        ];
    }
}
