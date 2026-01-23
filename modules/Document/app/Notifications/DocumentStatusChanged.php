<?php

namespace Modules\Document\Notifications;

use Illuminate\Broadcasting\Channel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class DocumentStatusChanged extends Notification implements ShouldBroadcast, ShouldQueue
{
    use Queueable;

    public ?int $recipientUserId = null;

    public function __construct(
        public object $document,
        public string $oldStatus = '',
        public string $newStatus = ''
    ) {}

    public function via(object $notifiable): array
    {
        $this->recipientUserId = $notifiable->id;

        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        $orderRef = $this->document->order_reference ?? $this->document->order_id;

        return [
            'document_id' => $this->document->id,
            'document_uid' => $this->document->uid,
            'order_id' => $this->document->order_id,
            'order_reference' => $orderRef,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'title' => '🔄 Estado del Documento Actualizado',
            'message' => "El documento #{$orderRef} cambió de estado: {$this->oldStatus} → {$this->newStatus}",
            'icon' => 'fa-duotone fas fa-sync-alt',
            'color' => 'info',
            'action_url' => route('documents.show', $this->document->uid),
            'action_text' => 'Ver documento',
            'priority' => 'medium',
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        $orderRef = $this->document->order_reference ?? $this->document->order_id;

        return new BroadcastMessage([
            'id' => $this->id ?? uniqid(),
            'document_id' => $this->document->id,
            'document_uid' => $this->document->uid,
            'order_reference' => $orderRef,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'title' => '🔄 Estado Actualizado',
            'message' => "Documento #{$orderRef}: {$this->oldStatus} → {$this->newStatus}",
            'icon' => 'fa-duotone fas fa-sync-alt',
            'color' => 'info',
            'action_url' => route('documents.show', $this->document->uid),
            'action_text' => 'Ver documento',
            'priority' => 'medium',
        ]);
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    public function broadcastOn(): array
    {
        if (! $this->recipientUserId) {
            return [];
        }

        return [new Channel('public-notifications.'.$this->recipientUserId)];
    }

    public function broadcastType(): string
    {
        return 'document.status.changed';
    }
}
