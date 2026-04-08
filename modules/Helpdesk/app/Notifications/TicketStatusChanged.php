<?php

namespace Modules\Helpdesk\Notifications;

use App\Models\Notifications\NotificationPreference;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class TicketStatusChanged extends Notification implements ShouldBroadcast, ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public mixed $ticket,
        public string $oldStatus,
        public string $newStatus
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        if (NotificationPreference::isEnabled($notifiable->id, 'in_app', 'ticket.status_changed')) {
            $channels[] = 'database';
        }

        if (NotificationPreference::isEnabled($notifiable->id, 'push', 'ticket.status_changed')) {
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
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'title' => 'Estado de ticket actualizado',
            'message' => "El ticket #{$ticketNumber} cambió de {$this->oldStatus} a {$this->newStatus}",
            'icon' => 'fa-duotone fas fa-exchange-alt',
            'color' => $this->getColorByStatus($this->newStatus),
            'action_url' => route('manager.helpdesk.tickets.show', $this->ticket->id ?? 1),
            'action_text' => 'Ver ticket',
            'priority' => 'normal',
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
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'title' => 'Estado de ticket actualizado',
            'message' => "El ticket #{$ticketNumber} cambió a {$this->newStatus}",
            'icon' => 'fa-duotone fas fa-exchange-alt',
            'color' => $this->getColorByStatus($this->newStatus),
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
        return 'ticket.status_changed';
    }

    /**
     * Determinar color según el estado del ticket
     */
    protected function getColorByStatus(string $status): string
    {
        return match (strtolower($status)) {
            'resolved', 'closed', 'completed' => 'success',
            'open', 'pending', 'waiting' => 'warning',
            'cancelled', 'rejected' => 'danger',
            default => 'info',
        };
    }
}
