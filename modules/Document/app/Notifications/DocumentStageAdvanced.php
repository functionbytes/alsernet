<?php

namespace Modules\Document\Notifications;

use Illuminate\Broadcasting\Channel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Modules\Document\Entities\Document;
use Modules\Notification\Models\NotificationPreference;

class DocumentStageAdvanced extends Notification implements ShouldBroadcast, ShouldQueue
{
    use Queueable;

    public ?int $recipientUserId = null;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public object $document, // Accept Document or mock objects for testing
        public int $previousStage,
        public int $currentStage,
        public string $currentValidatorGroup
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Store the recipient user ID for use in broadcastOn()
        $this->recipientUserId = $notifiable->id;

        $channels = [];

        try {
            // Check notification preferences if the system is available
            if (NotificationPreference::isEnabled($notifiable->id, 'in_app', 'document.stage.advanced')) {
                $channels[] = 'database';
            }

            if (NotificationPreference::isEnabled($notifiable->id, 'push', 'document.stage.advanced')) {
                $channels[] = 'broadcast';
            }
        } catch (\Exception $e) {
            // If notification preferences system is not available, send both types by default
            $channels = ['database', 'broadcast'];
        }

        return $channels;
    }

    /**
     * Get the database representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $stageLabel = $this->document->getCurrentStageLabel();
        $orderRef = $this->document->order_reference ?? $this->document->order_id;

        return [
            'document_id' => $this->document->id,
            'document_uid' => $this->document->uid,
            'order_id' => $this->document->order_id,
            'order_reference' => $orderRef,
            'previous_stage' => $this->previousStage,
            'current_stage' => $this->currentStage,
            'total_stages' => $this->document->total_stages,
            'current_validator_group' => $this->currentValidatorGroup,
            'stage_label' => $stageLabel,
            'title' => 'Nuevo documento asignado',
            'message' => "El documento #{$orderRef} requiere validación en etapa {$this->currentStage}/{$this->document->total_stages} - {$stageLabel}",
            'icon' => 'fa-duotone fas fa-file-check',
            'color' => 'primary',
            'action_url' => route('documents.manage', $this->document->uid),
            'action_text' => 'Ver documento',
            'priority' => 'high',
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        $stageLabel = $this->document->getCurrentStageLabel();
        $orderRef = $this->document->order_reference ?? $this->document->order_id;
        $now = now();

        return new BroadcastMessage([
            'id' => $this->id ?? uniqid(),
            'document_id' => $this->document->id,
            'document_uid' => $this->document->uid,
            'order_id' => $this->document->order_id,
            'order_reference' => $orderRef,
            'current_stage' => $this->currentStage,
            'total_stages' => $this->document->total_stages,
            'stage_label' => $stageLabel,
            'title' => 'Nuevo documento asignado',
            'message' => "El documento #{$orderRef} requiere validación en etapa {$this->currentStage}/{$this->document->total_stages} - {$stageLabel}",
            'icon' => 'fa-duotone fas fa-file-check',
            'color' => 'primary',
            'action_url' => route('documents.manage', $this->document->uid),
            'action_text' => 'Ver documento',
            'is_read' => false,
            'created_at' => $now->format('H:i'),
            'created_at_full' => $now->toIso8601String(),
            'badge' => '/favicon.ico',
            'priority' => 'high',
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

        // Use public channel instead of private to avoid WebSocket auth issues
        // Public channels don't require session auth, which WebSocket clients can't provide
        return [new Channel('public-notifications.'.$this->recipientUserId)];
    }

    public function broadcastType(): string
    {
        return 'document.stage.advanced';
    }
}
