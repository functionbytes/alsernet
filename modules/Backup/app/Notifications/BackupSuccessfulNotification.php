<?php

namespace Modules\Backup\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BackupSuccessfulNotification extends Notification
{
    public function __construct(
        private ?string $size = null
    ) {}

    /** @return array<int, string> */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $appName = config('app.name', 'Sistema');

        $message = (new MailMessage)
            ->from(
                config('backup.notifications.mail.from.address', config('mail.from.address')),
                config('backup.notifications.mail.from.name', config('mail.from.name'))
            )
            ->subject("Backup completado: {$appName}")
            ->greeting('Backup exitoso')
            ->line("El backup de \"{$appName}\" se completó correctamente.");

        if ($this->size) {
            $message->line('Tamaño: '.$this->size);
        }

        return $message->action('Ver backups', route('settings.backups.index'));
    }
}
