<?php

namespace Modules\Attention\Notifications;

use Illuminate\Broadcasting\Channel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Modules\Attention\Enums\AttentionStatus;
use Modules\Attention\Models\Attention;

class AttentionStatusChangedNotification extends Notification implements ShouldBroadcast, ShouldQueue
{
    use Queueable;

    public ?int $recipientUserId = null;

    public function __construct(
        public Attention $attention,
        public AttentionStatus $oldStatus,
        public AttentionStatus $newStatus
    ) {}

    public function via($notifiable): array
    {
        $this->recipientUserId = $notifiable->id;

        $channels = array_values(array_filter(
            ['database', 'broadcast'],
            fn ($ch) => $notifiable->canReceiveNotification($ch, 'attention.status_changed')
        ));

        return $channels ?: ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => '🔄 Estado de peticiones actualizado',
            'message' => "El peticiones #{$this->attention->radicado} cambió de estado: ".
                         "{$this->oldStatus->label()} → {$this->newStatus->label()}",
            'icon' => 'fa-duotone fas fa-exchange-alt',
            'color' => $this->newStatus->color(),
            'action_url' => url("/attentions/show/{$this->attention->uid}"),
            'action_text' => 'Ver detalles',
            'priority' => 'medium',
            'attention_id' => $this->attention->id,
            'attention_uid' => $this->attention->uid,
            'attention_radicado' => $this->attention->radicado,
            'old_status' => $this->oldStatus->value,
            'new_status' => $this->newStatus->value,
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'title' => '🔄 Estado actualizado',
            'message' => "peticiones #{$this->attention->radicado}: {$this->newStatus->label()}",
            'icon' => 'fa-duotone fas fa-exchange-alt',
            'color' => $this->newStatus->color(),
            'action_url' => url("/attentions/show/{$this->attention->uid}"),
            'action_text' => 'Ver detalles',
            'priority' => 'medium',
            'attention_id' => $this->attention->id,
            'attention_uid' => $this->attention->uid,
            'attention_radicado' => $this->attention->radicado,
        ]);
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('attentions.status'),
        ];
    }

    public function broadcastType(): string
    {
        return 'attention.status.changed.notification';
    }
}
