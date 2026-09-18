<?php

namespace Modules\Forms\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FormExportReadyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        private readonly string $filePath,
        private readonly string $formName
    ) {
        $this->onQueue('notifications');
    }

    public function via(mixed $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(mixed $notifiable): array
    {
        return [
            'title' => 'Exportación lista: '.$this->formName,
            'message' => 'La exportación de respuestas del formulario "'.$this->formName.'" está lista.',
            'url' => route('settings.forms.submissions.export-download', ['file' => basename($this->filePath)]),
            'icon' => 'fas fa-file-excel',
            'type' => 'success',
        ];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Exportación lista: '.$this->formName)
            ->line('La exportación de respuestas del formulario "'.$this->formName.'" está lista para descargar.')
            ->action('Descargar', route('settings.forms.submissions.export-download', ['file' => basename($this->filePath)]));
    }
}
