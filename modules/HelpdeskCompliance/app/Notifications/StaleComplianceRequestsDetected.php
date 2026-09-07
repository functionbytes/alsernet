<?php

namespace Modules\HelpdeskCompliance\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Alerta a manager/super-admin cuando hay ComplianceRequest en 'pending' con
 * más antigüedad que la umbral configurada (helpdeskcompliance.stale_pending_hours)
 * — ver CheckStaleComplianceRequestsCommand. El borrado core (irreversible) ya
 * se aplicó cuando se creó la solicitud; que siga 'pending' significa que el
 * worker de la cola 'helpdeskcompliance' no la ha recogido (o el job murió sin
 * completar failed()), y tickets/chatflow/email logs pueden seguir con PII.
 */
class StaleComplianceRequestsDetected extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<int, int>  $requestIds
     */
    public function __construct(
        public readonly array $requestIds,
        public readonly int $thresholdHours,
    ) {
        $this->onQueue('notifications');
    }

    public function via(mixed $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(mixed $notifiable): array
    {
        $count = count($this->requestIds);

        return [
            'type' => 'compliance_requests_stale',
            'title' => 'Solicitudes GDPR estancadas',
            'message' => "{$count} solicitud(es) de borrado GDPR llevan más de {$this->thresholdHours}h en 'pending' sin completar la cascada.",
            'request_ids' => $this->requestIds,
            'threshold_hours' => $this->thresholdHours,
        ];
    }

    public function toBroadcast(mixed $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
