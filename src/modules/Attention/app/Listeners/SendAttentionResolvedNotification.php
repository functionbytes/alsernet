<?php

namespace Modules\Attention\Listeners;

use Illuminate\Broadcasting\Channel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Modules\Attention\Events\AttentionResolved;

class SendAttentionResolvedNotification
{
    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle(AttentionResolved $event): void
    {
        $attention = $event->attention;

        // Load relationships
        $attention->load(['type', 'category', 'assignedUser']);

        // Si no hay usuario asignado, no enviar notificación
        if (! $attention->assigned_user_id) {
            return;
        }

        $user = $attention->assignedUser;

        if (! $user) {
            return;
        }

        // Crear notificación inline para enviar
        $notification = new class extends Notification implements ShouldBroadcast, ShouldQueue
        {
            use Queueable;

            public ?int $recipientUserId = null;

            public function __construct(
                public $attention
            ) {}

            public function via($notifiable)
            {
                $this->recipientUserId = $notifiable->id;

                return ['database', 'broadcast'];
            }

            public function toDatabase($notifiable)
            {
                return [
                    'title' => '✅ peticiones resuelto',
                    'message' => "El peticiones #{$this->attention->radicado} ha sido marcado como resuelto. ".
                                 "Cliente: {$this->attention->full_name}.",
                    'icon' => 'fa-duotone fas fa-check-circle',
                    'color' => 'success',
                    'action_url' => url("/attentions/show/{$this->attention->uid}"),
                    'action_text' => 'Ver detalles',
                    'priority' => 'normal',
                    'attention_id' => $this->attention->id,
                    'attention_uid' => $this->attention->uid,
                    'attention_radicado' => $this->attention->radicado,
                ];
            }

            public function toBroadcast($notifiable)
            {
                return new BroadcastMessage([
                    'title' => '✅ peticiones resuelto',
                    'message' => "peticiones #{$this->attention->radicado} ha sido resuelto",
                    'icon' => 'fa-duotone fas fa-check-circle',
                    'color' => 'success',
                    'action_url' => url("/attentions/show/{$this->attention->uid}"),
                    'action_text' => 'Ver detalles',
                    'priority' => 'normal',
                    'attention_id' => $this->attention->id,
                    'attention_uid' => $this->attention->uid,
                    'attention_radicado' => $this->attention->radicado,
                ]);
            }

            public function broadcastOn(): array
            {
                return [
                    new Channel('attentions.resolved'),
                ];
            }

            public function broadcastType(): string
            {
                return 'attention.resolved.notification';
            }
        };

        $user->notify($notification);
    }
}
