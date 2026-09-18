<?php

namespace Modules\HelpdeskTickets\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketItem;

class TicketMessageReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $ticket;

    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct(Ticket $ticket, TicketItem $message)
    {
        $this->ticket = $ticket;
        $this->message = $message;

        // Ensure relationships are loaded
        $this->message->load(['author', 'user']);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            // Public channel for customer widget (no auth required)
            new Channel('ticket.'.$this->ticket->id),

            // Private channel for helpdesk agents (auth required)
            new PrivateChannel('helpdesk.tickets'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.received';
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
            'message' => [
                'id' => $this->message->id,
                'type' => $this->message->type,
                'body' => $this->message->body,
                'html_body' => $this->message->html_body,
                'is_from_customer' => $this->message->isFromCustomer(),
                'is_from_agent' => $this->message->isFromAgent(),
                'is_internal' => $this->message->is_internal,
                'sender_name' => $this->message->sender_name,
                'sender_avatar' => $this->message->sender_avatar,
                'metadata' => $this->message->metadata,
                'created_at' => $this->message->created_at->toIso8601String(),
            ],
            'ticket' => [
                'id' => $this->ticket->id,
                'ticket_number' => $this->ticket->ticket_number,
                'subject' => $this->ticket->subject,
                'priority' => $this->ticket->priority,
                'last_message_at' => $this->ticket->last_message_at?->toIso8601String(),
            ],
        ];
    }
}
