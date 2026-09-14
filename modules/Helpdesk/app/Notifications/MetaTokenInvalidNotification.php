<?php

namespace Modules\Helpdesk\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Avisa a los managers de que el token de un canal de Meta (WhatsApp/Facebook/
 * Instagram) caducó o es inválido (error 190) y hay que re-autenticar.
 */
class MetaTokenInvalidNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $channel,
    ) {
        $this->onQueue('notifications-high');
    }

    public function via(mixed $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(mixed $notifiable): array
    {
        return [
            'type' => 'helpdesk_meta_token_invalid',
            'title' => 'Token de Meta caducado',
            'message' => "El token del canal {$this->channel} caducó o es inválido. Revisa la integración y vuelve a autenticar.",
            'entity_id' => null,
            'action_url' => url('/panel/helpdesk'),
        ];
    }

    public function toBroadcast(mixed $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
