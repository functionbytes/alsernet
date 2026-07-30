<?php

namespace Modules\HelpdeskTickets\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Modules\Notification\Models\NotificationPreference;

class TicketAssigned extends Notification implements ShouldBroadcast, ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public mixed $ticket,
        public mixed $assignedBy
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        if (NotificationPreference::isEnabled($notifiable->id, 'in_app', 'ticket.assigned')) {
            $channels[] = 'database';
        }

        if (NotificationPreference::isEnabled($notifiable->id, 'push', 'ticket.assigned')) {
            $channels[] = 'broadcast';
        }

        return $channels;
    }

    /**
     * Get the database representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $ticketNumber = $this->ticket->number ?? ($this->ticket->id ?? 'N/A');

        return [
            'ticket_id' => $this->ticket->id ?? null,
            'ticket_number' => $ticketNumber,
            'assigned_by' => $this->assignedBy->name ?? 'Sistema',
            'title' => 'Ticket asignado',
            'message' => "Se te ha asignado el ticket #{$ticketNumber}",
            'icon' => 'fa-duotone fas fa-user-check',
            'color' => 'warning',
            'action_url' => route('manager.helpdesk.tickets.show', $this->ticket->id ?? 1),
            'action_text' => 'Ver ticket',
            'priority' => 'high',
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        $ticketNumber = $this->ticket->number ?? ($this->ticket->id ?? 'N/A');

        return new BroadcastMessage([
            'ticket_id' => $this->ticket->id ?? null,
            'ticket_number' => $ticketNumber,
            'title' => 'Ticket asignado',
            'message' => "Se te ha asignado el ticket #{$ticketNumber}",
            'icon' => 'fa-duotone fas fa-user-check',
            'color' => 'warning',
            'action_url' => route('manager.helpdesk.tickets.show', $this->ticket->id ?? 1),
            'created_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    /**
     * Determinar el tipo de notificación para broadcasting
     */
    public function broadcastType(): string
    {
        return 'ticket.assigned';
    }
}
