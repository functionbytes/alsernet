<?php

namespace App\Events\Helpdesk;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Helpdesk\Models\Ticket;
use Modules\Helpdesk\Models\TicketStatus;

class TicketStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $ticket;

    public $previousStatus;

    public $newStatus;

    /**
     * Create a new event instance.
     */
    public function __construct(Ticket $ticket, TicketStatus $previousStatus, TicketStatus $newStatus)
    {
        $this->ticket = $ticket;
        $this->previousStatus = $previousStatus;
        $this->newStatus = $newStatus;

        // Ensure relationships are loaded
        $this->ticket->load(['customer', 'status', 'category', 'assignee']);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            // Broadcast to specific ticket channel
            new PrivateChannel('ticket.'.$this->ticket->id),

            // Broadcast to all helpdesk agents
            new PrivateChannel('helpdesk.tickets'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'ticket.status.changed';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'previous_status' => [
                'id' => $this->previousStatus->id,
                'name' => $this->previousStatus->name,
                'is_open' => $this->previousStatus->is_open,
                'color' => $this->previousStatus->color,
                'stops_sla_timer' => $this->previousStatus->stops_sla_timer,
            ],
            'new_status' => [
                'id' => $this->newStatus->id,
                'name' => $this->newStatus->name,
                'is_open' => $this->newStatus->is_open,
                'color' => $this->newStatus->color,
                'stops_sla_timer' => $this->newStatus->stops_sla_timer,
            ],
            'ticket' => [
                'id' => $this->ticket->id,
                'ticket_number' => $this->ticket->ticket_number,
                'subject' => $this->ticket->subject,
                'priority' => $this->ticket->priority,
                'category' => [
                    'id' => $this->ticket->category->id,
                    'name' => $this->ticket->category->name,
                    'color' => $this->ticket->category->color,
                ],
                'customer' => [
                    'id' => $this->ticket->customer->id,
                    'name' => $this->ticket->customer->name,
                ],
                'assignee' => $this->ticket->assignee ? [
                    'id' => $this->ticket->assignee->id,
                    'name' => $this->ticket->assignee->name,
                ] : null,
                'sla_paused' => $this->ticket->sla_paused,
                'updated_at' => $this->ticket->updated_at->toIso8601String(),
            ],
        ];
    }
}
