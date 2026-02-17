<?php

namespace Modules\Attention\Listeners;

use Illuminate\Notifications\Notification;
use Modules\Attention\Events\AttentionAssigned;

class SendAttentionAssignedNotification
{
    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle(AttentionAssigned $event): void
    {
        $attention = $event->attention;
        $newUserId = $event->newUserId;

        // Si no hay nuevo usuario asignado, no enviar notificación
        if (! $newUserId) {
            return;
        }

        $user = \App\Models\User::find($newUserId);

        if (! $user) {
            return;
        }

        // Load relationships
        $attention->load(['type', 'category']);

        // Crear notificación inline para enviar
        $notification = new class extends Notification implements \Illuminate\Contracts\Broadcasting\ShouldBroadcast, \Illuminate\Contracts\Queue\ShouldQueue
        {
            use \Illuminate\Bus\Queueable;

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
                    'title' => '👤 PQRSF asignado a ti',
                    'message' => "Se te ha asignado el PQRSF #{$this->attention->radicado}. ".
                                 "Cliente: {$this->attention->full_name}. ".
                                 "Tipo: {$this->attention->type->name}.",
                    'icon' => 'fa-duotone fas fa-user-check',
                    'color' => 'warning',
                    'action_url' => url("/attentions/show/{$this->attention->uid}"),
                    'action_text' => 'Ver detalles',
                    'priority' => 'high',
                    'attention_id' => $this->attention->id,
                    'attention_uid' => $this->attention->uid,
                    'attention_radicado' => $this->attention->radicado,
                ];
            }

            public function toBroadcast($notifiable)
            {
                return new \Illuminate\Notifications\Messages\BroadcastMessage([
                    'title' => '👤 PQRSF asignado a ti',
                    'message' => "PQRSF #{$this->attention->radicado} requiere tu atención",
                    'icon' => 'fa-duotone fas fa-user-check',
                    'color' => 'warning',
                    'action_url' => url("/attentions/show/{$this->attention->uid}"),
                    'action_text' => 'Ver detalles',
                    'priority' => 'high',
                    'attention_id' => $this->attention->id,
                    'attention_uid' => $this->attention->uid,
                    'attention_radicado' => $this->attention->radicado,
                ]);
            }

            public function broadcastOn(): array
            {
                return [
                    new \Illuminate\Broadcasting\Channel('attentions.assigned'),
                ];
            }

            public function broadcastType(): string
            {
                return 'attention.assigned.notification';
            }
        };

        $user->notify($notification);
    }
}
