<?php

namespace Modules\Document\Notifications;

use Illuminate\Broadcasting\Channel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class DocumentRejected extends Notification implements ShouldBroadcast, ShouldQueue
{
    use Queueable;

    public ?int $recipientUserId = null;

    public function __construct(
        public object $document,
        public string $reason = '',
        public string $rejectedBy = 'Sistema'
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
            'reason' => $this->reason,
            'rejected_by' => $this->rejectedBy,
            'title' => '❌ Documento Rechazado',
            'message' => "El documento #{$orderRef} ha sido rechazado. Motivo: {$this->reason}",
            'icon' => 'fa-duotone fas fa-times-circle',
            'color' => 'danger',
            'action_url' => route('documents.show', $this->document->uid),
            'action_text' => 'Ver documento',
            'priority' => 'critical',
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
            'reason' => $this->reason,
            'rejected_by' => $this->rejectedBy,
            'title' => '❌ Documento Rechazado',
            'message' => "El documento #{$orderRef} ha sido rechazado. Motivo: {$this->reason}",
            'icon' => 'fa-duotone fas fa-times-circle',
            'color' => 'danger',
            'action_url' => route('documents.show', $this->document->uid),
            'action_text' => 'Ver documento',
            'priority' => 'critical',
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
        return 'document.rejected';
    }
}
