<?php

namespace Modules\HelpdeskTickets\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\HelpdeskTickets\Models\Ticket;

class TicketCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $ticket;

    /**
     * Create a new event instance.
     */
    public function __construct(Ticket $ticket)
    {
        $this->ticket = $ticket;

        // Ensure relationships are loaded
        $this->ticket->load(['customer', 'status', 'category', 'assignee', 'items', 'slaPolicy']);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            // Broadcast to all helpdesk agents
            new PrivateChannel('helpdesk.tickets'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'ticket.created';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'ticket' => [
                'id' => $this->ticket->id,
                'ticket_number' => $this->ticket->ticket_number,
                'subject' => $this->ticket->subject,
                'priority' => $this->ticket->priority,
                'source' => $this->ticket->source,
                'status' => $this->ticket->status ? [
                    'id' => $this->ticket->status->id,
                    'name' => $this->ticket->status->name,
                    'is_open' => $this->ticket->status->is_open,
                    'color' => $this->ticket->status->color,
                ] : null,
                'category' => $this->ticket->category ? [
                    'id' => $this->ticket->category->id,
                    'name' => $this->ticket->category->name,
                    'icon' => $this->ticket->category->icon,
                    'color' => $this->ticket->category->color,
                ] : null,
                'customer' => $this->ticket->customer ? [
                    'id' => $this->ticket->customer->id,
                    'name' => $this->ticket->customer->name,
                    'email' => $this->ticket->customer->email,
                    'avatar_url' => $this->ticket->customer->getAvatarUrl(),
                ] : null,
                'assignee' => $this->ticket->assignee ? [
                    'id' => $this->ticket->assignee->id,
                    'name' => $this->ticket->assignee->name,
                ] : null,
                'sla_policy' => $this->ticket->slaPolicy ? [
                    'id' => $this->ticket->slaPolicy->id,
                    'name' => $this->ticket->slaPolicy->name,
                ] : null,
                'sla_first_response_due_at' => $this->ticket->sla_first_response_due_at?->toIso8601String(),
                'sla_resolution_due_at' => $this->ticket->sla_resolution_due_at?->toIso8601String(),
                'sla_first_response_breached' => $this->ticket->sla_first_response_breached,
                'sla_resolution_breached' => $this->ticket->sla_resolution_breached,
                'message_count' => $this->ticket->items->count(),
                'created_at' => $this->ticket->created_at->toIso8601String(),
                'last_message_at' => $this->ticket->last_message_at?->toIso8601String(),
            ],
        ];
    }
}
