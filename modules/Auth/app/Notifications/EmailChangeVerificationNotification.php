<?php

namespace Modules\Auth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailChangeVerificationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $verifyUrl,
        public readonly string $newEmail,
        public readonly int $expiresInMinutes,
    ) {}

    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        // Deliberately send to the *new* email address, not the current one.
        return (new MailMessage)
            ->to($this->newEmail)
            ->subject('Confirma tu nuevo correo electrónico')
            ->greeting("Hola {$notifiable->firstname},")
            ->line("Recibimos una solicitud para cambiar el correo de tu cuenta a {$this->newEmail}.")
            ->action('Confirmar cambio', $this->verifyUrl)
            ->line("Este enlace expira en {$this->expiresInMinutes} minutos.")
            ->line('Si no solicitaste este cambio, ignora este correo — tu cuenta no se verá afectada.');
    }
}
