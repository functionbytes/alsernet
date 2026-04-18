<?php

namespace Modules\Attention\Listeners;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
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

        $user = User::find($newUserId);

        if (! $user) {
            return;
        }

        // Load relationships
        $attention->load(['type', 'category']);

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
                    'title' => '👤 peticiones asignado a ti',
                    'message' => "Se te ha asignado el peticiones #{$this->attention->radicado}. ".
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
                return new BroadcastMessage([
                    'title' => '👤 peticiones asignado a ti',
                    'message' => "peticiones #{$this->attention->radicado} requiere tu atención",
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
                    new Channel('attentions.assigned'),
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
