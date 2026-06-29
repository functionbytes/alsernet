<?php

namespace Modules\HelpdeskTickets\Notifications;

use App\Models\Notifications\NotificationPreference;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketSlaBreach;

class TicketSlaBreached extends Notification implements ShouldBroadcast, ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly TicketSlaBreach $breach,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        if (NotificationPreference::isEnabled($notifiable->id, 'in_app', 'ticket.sla.breached')) {
            $channels[] = 'database';
        }

        if (NotificationPreference::isEnabled($notifiable->id, 'push', 'ticket.sla.breached')) {
            $channels[] = 'broadcast';
        }

        return $channels ?: ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'helpdesk_sla_breached',
            'title' => 'SLA incumplido',
            'message' => "El ticket #{$this->ticket->ticket_number} ha incumplido el SLA de {$this->breach->breach_type_label}",
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'breach_id' => $this->breach->id,
            'breach_type' => $this->breach->breach_type,
            'breached_at' => $this->breach->breached_at?->toIso8601String(),
            'icon' => 'fas fa-exclamation-triangle',
            'color' => 'danger',
            'action_url' => route('manager.helpdesk.tickets.show', $this->ticket->id),
            'action_text' => 'Ver ticket',
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    public function broadcastType(): string
    {
        return 'ticket.sla.breached';
    }
}
