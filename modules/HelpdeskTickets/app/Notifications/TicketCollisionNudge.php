<?php

namespace Modules\HelpdeskTickets\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Modules\HelpdeskTickets\Models\Ticket;

/**
 * Modal 23 "Bandeja compartida": "Avisar a X" cuando dos agentes coinciden
 * en el mismo ticket. Un aviso puntual, no una asignación ni un mensaje del
 * hilo -- el destinatario decide si cede el ticket o sigue.
 */
class TicketCollisionNudge extends Notification implements ShouldBroadcast, ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly User $from,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        $fromName = trim((string) $this->from->full_name) ?: 'Un agente';

        return [
            'type' => 'ticket_collision_nudge',
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'title' => 'Coincidencia en un ticket',
            'message' => "{$fromName} también está en el ticket #{$this->ticket->ticket_number} y quiere coordinarse contigo.",
            'icon' => 'fas fa-users',
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
        return 'ticket.collision_nudge';
    }
}
