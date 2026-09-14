<?php

namespace Modules\Notification\Notifications;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotificationDigestNotification extends Notification
{
    public function __construct(private readonly Collection $unreadNotifications) {}

    /** @return array<int, string> */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $count = $this->unreadNotifications->count();

        $mail = (new MailMessage)
            ->subject("Resumen de notificaciones — {$count} sin leer")
            ->greeting('Hola '.$notifiable->firstname.',')
            ->line("Tienes {$count} notificación(es) sin leer de las últimas 24 horas.");

        foreach ($this->unreadNotifications as $notification) {
            $title = $notification->data['title'] ?? $notification->data['message'] ?? 'Notificación';
            $mail->line('• '.$title);
        }

        return $mail
            ->line('')
            ->action('Ver todas las notificaciones', route('notifications.index'));
    }
}
