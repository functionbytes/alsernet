<?php

namespace Modules\Attention\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AttentionExportReadyNotification extends Notification
{
    public function __construct(
        private readonly string $token,
        private readonly string $format
    ) {}

    public function via(mixed $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(mixed $notifiable): array
    {
        return [
            'title' => 'Exportación peticiones lista',
            'message' => 'La exportación de peticiones en formato '.strtoupper($this->format).' está lista para descargar.',
            'url' => route('attention.export.download', ['token' => $this->token]),
            'icon' => 'fas fa-file-export',
            'type' => 'success',
        ];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Exportación peticiones lista')
            ->line('La exportación de peticiones en formato '.strtoupper($this->format).' está lista.')
            ->action('Descargar', route('attention.export.download', ['token' => $this->token]));
    }
}
