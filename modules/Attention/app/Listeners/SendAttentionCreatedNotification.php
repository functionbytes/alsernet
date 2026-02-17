<?php

namespace Modules\Attention\Listeners;

use Illuminate\Notifications\Notification;
use Modules\Attention\Events\AttentionCreated;

class SendAttentionCreatedNotification
{
    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle(AttentionCreated $event): void
    {
        $attention = $event->attention;

        // Load relationships if needed
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
                    'title' => '📋 Nuevo PQRSF asignado',
                    'message' => "Se ha creado un nuevo PQRSF #{$this->attention->radicado}. ".
                                 "Cliente: {$this->attention->full_name}. ".
                                 "Tipo: {$this->attention->type->name}.",
                    'icon' => 'fa-duotone fas fa-clipboard-check',
                    'color' => 'info',
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
                    'title' => '📋 Nuevo PQRSF asignado',
                    'message' => "PQRSF #{$this->attention->radicado} requiere tu atención",
                    'icon' => 'fa-duotone fas fa-clipboard-check',
                    'color' => 'info',
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
                    new \Illuminate\Broadcasting\Channel('attentions.created'),
                ];
            }

            public function broadcastType(): string
            {
                return 'attention.created.notification';
            }
        };

        $user->notify($notification);
    }
}
