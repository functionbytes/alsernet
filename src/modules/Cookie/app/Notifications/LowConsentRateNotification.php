<?php

namespace Modules\Cookie\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class LowConsentRateNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly float $rate,
        private readonly int $threshold
    ) {}

    public function via(mixed $notifiable): array
    {
        return ['database'];
    }

    public function toArray(mixed $notifiable): array
    {
        return [
            'type' => 'cookie_low_rate',
            'title' => 'Tasa de consentimiento baja',
            'message' => "La tasa de aceptación de cookies fue {$this->rate}% en las últimas 24h (umbral: {$this->threshold}%)",
            'action_url' => route('settings.cookie.logs'),
        ];
    }
}
