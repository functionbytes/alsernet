<?php

namespace Modules\Health\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Modules\Health\Models\AlertThreshold;

class AlertThresholdNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly AlertThreshold $threshold,
        private readonly array $data,
    ) {}

    public function via(object $notifiable): array
    {
        return array_values(array_filter(
            ['mail', 'database'],
            fn ($ch) => $notifiable->canReceiveNotification($ch, 'alerts.threshold_triggered')
        )) ?: ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'alert_threshold',
            'threshold_id' => $this->threshold->id,
            'threshold_name' => $this->threshold->name,
            'threshold_type' => $this->threshold->type,
            'description' => $this->data['description'] ?? '',
            'count' => $this->data['count'] ?? null,
            'triggered_at' => now()->toDateTimeString(),
        ];
    }
}
