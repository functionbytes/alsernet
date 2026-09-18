<?php

namespace Modules\HelpdeskTickets\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\Notification\Models\NotificationPreference;

class AutomationTicketNotification extends Notification implements ShouldBroadcast, ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Ticket $ticket
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (NotificationPreference::isEnabled($notifiable->id, 'push', 'ticket.automation')) {
            $channels[] = 'broadcast';
        }

        return array_unique($channels);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'ticket_automation',
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'title' => 'Automatizacion aplicada',
            'message' => "Una automatizacion actualizo el ticket #{$this->ticket->ticket_number}",
            'icon' => 'fas fa-robot',
            'color' => 'info',
            'action_url' => route('manager.helpdesk.tickets.show', $this->ticket->id),
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
        return 'ticket.automation';
    }
}
