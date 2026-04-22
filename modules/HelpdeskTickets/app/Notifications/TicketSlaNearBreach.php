<?php

namespace Modules\HelpdeskTickets\Notifications;

use App\Models\Notifications\NotificationPreference;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Modules\HelpdeskTickets\Models\Ticket;

class TicketSlaNearBreach extends Notification implements ShouldBroadcast, ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly string $breachType,
        public readonly int $remainingMinutes,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        if (NotificationPreference::isEnabled($notifiable->id, 'in_app', 'ticket.sla.warning')) {
            $channels[] = 'database';
        }

        if (NotificationPreference::isEnabled($notifiable->id, 'push', 'ticket.sla.warning')) {
            $channels[] = 'broadcast';
        }

        return $channels ?: ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $label = $this->breachType === 'first_response' ? 'primera respuesta' : 'resolución';

        return [
            'type' => 'helpdesk_sla_warning',
            'title' => 'Alerta SLA',
            'message' => "El ticket #{$this->ticket->ticket_number} vence en {$this->remainingMinutes} minutos ({$label})",
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'breach_type' => $this->breachType,
            'remaining_minutes' => $this->remainingMinutes,
            'icon' => 'fas fa-clock',
            'color' => 'warning',
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
        return 'ticket.sla.warning';
    }
}
