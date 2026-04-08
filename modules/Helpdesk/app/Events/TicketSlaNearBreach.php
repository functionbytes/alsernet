<?php

namespace App\Events\Helpdesk;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Helpdesk\Models\Ticket;

class TicketSlaNearBreach implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $ticket;

    public $breachType;

    public $remainingMinutes;

    /**
     * Create a new event instance.
     */
    public function __construct(Ticket $ticket, string $breachType, int $remainingMinutes)
    {
        $this->ticket = $ticket;
        $this->breachType = $breachType;
        $this->remainingMinutes = $remainingMinutes;

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
        return 'ticket.sla.warning';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $dueAt = $this->breachType === 'first_response'
            ? $this->ticket->sla_first_response_due_at
            : $this->ticket->sla_resolution_due_at;

        return [
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'warning' => [
                'breach_type' => $this->breachType,
                'breach_type_label' => $this->breachType === 'first_response' ? 'Primera Respuesta' : 'Resolución',
                'remaining_minutes' => $this->remainingMinutes,
                'due_at' => $dueAt?->toIso8601String(),
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
