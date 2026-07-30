<?php

namespace Modules\HelpdeskTickets\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Modules\HelpdeskTickets\Models\TicketFollowup;

class TicketFollowupDueNotification extends Notification implements ShouldBroadcast, ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly TicketFollowup $followup
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        $ticket = $this->followup->ticket;

        return [
            'type' => 'ticket_followup',
            'ticket_id' => $ticket?->id,
            'ticket_number' => $ticket?->ticket_number,
            'followup_id' => $this->followup->id,
            'title' => 'Recordatorio de seguimiento',
            'message' => $this->followup->note
                ? "Seguimiento del ticket #{$ticket?->ticket_number}: {$this->followup->note}"
                : "Toca hacer seguimiento del ticket #{$ticket?->ticket_number}",
            'icon' => 'fas fa-bell',
            'color' => 'warning',
            'action_url' => $ticket ? route('manager.helpdesk.tickets.show', $ticket->id) : null,
            'action_text' => 'Ver ticket',
            'priority' => 'normal',
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage(array_merge(
            $this->toDatabase($notifiable),
            ['created_at' => now()->toIso8601String()]
        ));
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    public function broadcastType(): string
    {
        return 'ticket.followup';
    }
}
