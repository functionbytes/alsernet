<?php

namespace Modules\Attention\Notifications;

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

        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => '🔄 Estado de PQRSF actualizado',
            'message' => "El PQRSF #{$this->attention->radicado} cambió de estado: ".
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
            'message' => "PQRSF #{$this->attention->radicado}: {$this->newStatus->label()}",
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
            new \Illuminate\Broadcasting\Channel('attentions.status'),
        ];
    }

    public function broadcastType(): string
    {
        return 'attention.status.changed.notification';
    }
}
