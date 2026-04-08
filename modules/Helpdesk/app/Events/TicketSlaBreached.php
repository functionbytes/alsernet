<?php

namespace App\Events\Helpdesk;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Helpdesk\Models\Ticket;
use Modules\Helpdesk\Models\TicketSlaBreach;

class TicketSlaBreached implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $ticket;

    public $breach;

    /**
     * Create a new event instance.
     */
    public function __construct(Ticket $ticket, TicketSlaBreach $breach)
    {
        $this->ticket = $ticket;
        $this->breach = $breach;

        // Ensure relationships are loaded
        $this->ticket->load(['customer', 'status', 'category', 'assignee', 'slaPolicy']);
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

            // Broadcast to SLA monitoring dashboard
            new PrivateChannel('helpdesk.sla'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'ticket.sla.breached';
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
            'breach' => [
                'id' => $this->breach->id,
                'breach_type' => $this->breach->breach_type,
                'breach_type_label' => $this->breach->breach_type_label,
                'due_at' => $this->breach->due_at->toIso8601String(),
                'breached_at' => $this->breach->breached_at->toIso8601String(),
                'breach_duration_minutes' => $this->breach->breach_duration_minutes,
                'formatted_duration' => $this->breach->formatted_duration,
                'is_critical' => $this->breach->isCritical(),
            ],
            'ticket' => [
                'id' => $this->ticket->id,
                'ticket_number' => $this->ticket->ticket_number,
                'subject' => $this->ticket->subject,
                'priority' => $this->ticket->priority,
                'status' => [
                    'id' => $this->ticket->status->id,
                    'name' => $this->ticket->status->name,
                    'color' => $this->ticket->status->color,
                ],
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
            ],
        ];
    }
}
