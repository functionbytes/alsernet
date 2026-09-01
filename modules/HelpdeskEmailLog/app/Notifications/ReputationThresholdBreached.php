<?php

namespace Modules\HelpdeskEmailLog\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Alerta a manager/super-admin cuando la tasa de rebote o de queja cruza el
 * umbral crítico configurado — ver CheckEmailReputationCommand, que aplica
 * el mismo debounce (solo notifica en la transición a "roto", no cada día
 * mientras sigue roto) que BounceProcessingFailedNotification.
 */
class ReputationThresholdBreached extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $metric,
        public readonly string $label,
        public readonly float $rate,
        public readonly float $threshold,
    ) {
        $this->onQueue('notifications');
    }

    public function via(mixed $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(mixed $notifiable): array
    {
        return [
            'type' => 'email_reputation_threshold_breached',
            'title' => 'Reputación de email: umbral superado',
            'message' => "La tasa de {$this->label} es {$this->rate}%, por encima del umbral crítico de {$this->threshold}%.",
            'metric' => $this->metric,
            'rate' => $this->rate,
            'threshold' => $this->threshold,
        ];
    }

    public function toBroadcast(mixed $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
