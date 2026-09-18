<?php

namespace Modules\Helpdesk\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;

class StatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Conversation $conversation,
        public readonly ConversationStatus $newStatus,
    ) {
        $this->onQueue('notifications');
    }

    public function via(mixed $notifiable): array
    {
        // Customer receives email; agents receive database + broadcast
        if ($notifiable instanceof Customer) {
            return ['mail'];
        }

        return ['database', 'broadcast'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Tu conversacion #{$this->conversation->id} ha cambiado de estado")
            ->greeting("Hola {$notifiable->name},")
            ->line("El estado de tu conversacion \"{$this->conversation->subject}\" ha cambiado a: {$this->newStatus->name}.")
            ->action('Ver conversacion', route('manager.helpdesk.conversations.show', $this->conversation))
            ->line('Gracias por contactarnos.');
    }

    public function toArray(mixed $notifiable): array
    {
        return [
            'type' => 'helpdesk_status_changed',
            'title' => 'Estado de conversacion cambiado',
            'message' => "La conversacion #{$this->conversation->id} cambio a estado: {$this->newStatus->name}",
            'entity_id' => $this->conversation->id,
            'action_url' => route('manager.helpdesk.conversations.show', $this->conversation),
        ];
    }

    public function toBroadcast(mixed $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
