<?php

namespace Modules\HelpdeskEmailLog\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Alerta a manager/super-admin cuando email-logs:process-bounces lleva
 * varias ejecuciones seguidas fallando para un buzón concreto (conexión IMAP
 * caída, credenciales revocadas...). No se dispara en el primer fallo
 * aislado — ver BounceMailboxesRepository::recordHealth() y el umbral en
 * ProcessEmailBouncesCommand — para no generar ruido por un blip transitorio
 * de red.
 *
 * Puerto de Modules\Document\Notifications\BounceProcessingFailedNotification
 * (que solo cubría el buzón fijo de Document), ahora parametrizado por buzón.
 */
class BounceProcessingFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $mailboxLabel,
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
            'type' => 'email_bounce_processing_failed',
            'title' => 'Rebotes de email: fallo repetido',
            'message' => "El buzón de rebotes \"{$this->mailboxLabel}\" lleva {$this->consecutiveFailures} ejecuciones fallando seguidas. Último error: {$this->lastError}",
            'mailbox_label' => $this->mailboxLabel,
            'consecutive_failures' => $this->consecutiveFailures,
            'last_error' => $this->lastError,
        ];
    }

    public function toBroadcast(mixed $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
