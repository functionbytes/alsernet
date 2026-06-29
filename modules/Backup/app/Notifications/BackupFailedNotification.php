<?php

namespace Modules\Backup\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BackupFailedNotification extends Notification
{
    public function __construct(
        private string $errorMessage
    ) {}

    /** @return array<int, string> */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $appName = config('app.name', 'Sistema');

        return (new MailMessage)
            ->error()
            ->from(
                config('backup.notifications.mail.from.address', config('mail.from.address')),
                config('backup.notifications.mail.from.name', config('mail.from.name'))
            )
            ->subject("Error en el backup: {$appName}")
            ->greeting('Error en el backup')
            ->line("El backup de \"{$appName}\" ha fallado.")
            ->line('Error: '.$this->errorMessage)
            ->line('Revisa los logs del sistema para obtener más detalles.')
            ->action('Ver logs', url('/settings/activity/logs'));
    }
}
