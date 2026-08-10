<?php

namespace Modules\Document\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Alerta a manager/super-admin cuando documents:process-bounces lleva varias
 * ejecuciones seguidas fallando (conexión IMAP caída, credenciales revocadas...).
 * No se dispara en el primer fallo aislado — ver umbral en
 * ProcessEmailBouncesCommand::CONSECUTIVE_FAILURES_THRESHOLD — para no generar
 * ruido por un blip transitorio de red.
 */
class BounceProcessingFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $consecutiveFailures,
        public readonly string $lastError,
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
            'type' => 'document_bounce_processing_failed',
            'title' => 'Rebotes de documentos: fallo repetido',
            'message' => "documents:process-bounces lleva {$this->consecutiveFailures} ejecuciones fallando seguidas. Último error: {$this->lastError}",
            'consecutive_failures' => $this->consecutiveFailures,
            'last_error' => $this->lastError,
        ];
    }

    public function toBroadcast(mixed $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
