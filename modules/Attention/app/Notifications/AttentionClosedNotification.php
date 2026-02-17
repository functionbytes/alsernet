<?php

namespace Modules\Attention\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Modules\Attention\Models\Attention;

/**
 * Notification sent when an Attention is closed
 */
class AttentionClosedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public Attention $attention;

    /**
     * Create a new notification instance.
     */
    public function __construct(Attention $attention)
    {
        $this->attention = $attention;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'attention_closed',
            'title' => 'PQRSF Cerrado',
            'message' => "El PQRSF {$this->attention->radicado} ha sido cerrado",
            'attention_id' => $this->attention->id,
            'attention_uid' => $this->attention->uid,
            'radicado' => $this->attention->radicado,
            'subject' => $this->attention->subject,
            'closed_at' => $this->attention->closed_at?->toIso8601String(),
            'action_url' => route('attention.show', ['attention' => $this->attention->uid]),
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
