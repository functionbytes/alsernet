<?php

namespace Modules\HelpdeskTickets\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\Notification\Models\NotificationPreference;

class TicketMentionNotification extends Notification implements ShouldBroadcast, ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly User $mentionedBy
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (NotificationPreference::isEnabled($notifiable->id, 'push', 'ticket.mention')) {
            $channels[] = 'broadcast';
        }

        return array_unique($channels);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'ticket_mention',
            'title' => 'Te mencionaron en un ticket',
            'message' => "{$this->mentionedBy->name} te menciono en el ticket #{$this->ticket->ticket_number}",
            'icon' => 'fas fa-at',
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
        return 'ticket.mention';
    }
}
