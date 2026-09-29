<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Alerta de `php artisan security:watch` para los super-admin (29-sep-2026).
 * Síncrona a propósito: debe llegar aunque la cola esté parada.
 */
class SecurityAlertNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<int, string>  $lines  Hallazgos en texto plano.
     */
    public function __construct(
        public readonly string $summary,
        public readonly array $lines,
        public readonly bool $withMail = false,
    ) {}

    public function via(mixed $notifiable): array
    {
        return $this->withMail && ! empty($notifiable->email) ? ['database', 'mail'] : ['database'];
    }

    public function toArray(mixed $notifiable): array
    {
        // El desplegable de notificaciones pinta el mensaje como HTML: solo texto.
        $message = strip_tags($this->summary);
        if ($this->lines !== []) {
            $message .= ' — '.strip_tags(implode(' | ', array_slice($this->lines, 0, 5)));
        }

        return [
            'type' => 'security_alert',
            'title' => 'Alerta de seguridad',
            'message' => mb_substr($message, 0, 1000),
            'action_url' => url('/panel/dashboard'),
        ];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('[webadmin] Alerta de seguridad: '.mb_substr(strip_tags($this->summary), 0, 120))
            ->line(strip_tags($this->summary));

        foreach ($this->lines as $line) {
            $mail->line('• '.strip_tags($line));
        }

        return $mail->line('Detalle: storage/logs/security-'.now()->format('Y-m-d').'.log en el servidor.');
    }
}
