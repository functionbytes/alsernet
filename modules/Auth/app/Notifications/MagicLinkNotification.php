<?php

namespace Modules\Auth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MagicLinkNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $signedUrl,
        public readonly int $expiresInMinutes,
    ) {}

    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Enlace de inicio de sesión')
            ->greeting("Hola {$notifiable->firstname},")
            ->line('Solicitaste un enlace para iniciar sesión sin contraseña. Haz clic en el botón:')
            ->action('Iniciar sesión', $this->signedUrl)
            ->line("Este enlace expira en {$this->expiresInMinutes} minutos y solo puede usarse una vez.")
            ->line('Si no solicitaste este correo, puedes ignorarlo — no se hará ningún cambio en tu cuenta.');
    }
}
