<?php

namespace Modules\Auth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TwoFactorChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $action, // 'enabled' | 'disabled' | 'recovery_regenerated'
        public readonly ?string $ipAddress = null,
    ) {}

    public function via(mixed $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $label = match ($this->action) {
            'enabled' => 'activada',
            'disabled' => 'desactivada',
            'recovery_regenerated' => 'regenerada con nuevos códigos de recuperación',
            default => 'modificada',
        };

        $subject = match ($this->action) {
            'enabled' => 'Autenticación de dos factores activada',
            'disabled' => 'Autenticación de dos factores desactivada',
            'recovery_regenerated' => 'Nuevos códigos de recuperación generados',
            default => 'Cambio en tu autenticación de dos factores',
        };

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting("Hola {$notifiable->firstname},")
            ->line("La autenticación de dos factores en tu cuenta ha sido {$label}.")
            ->line('IP del cambio: '.($this->ipAddress ?: 'desconocida'))
            ->line('Fecha: '.now()->format('d M Y H:i'))
            ->line('Si no reconoces este cambio, cambia tu contraseña inmediatamente y contacta al administrador.');

        if ($this->action === 'disabled') {
            $mail->action('Revisar seguridad', route('settings.auth.two-factor'));
        }

        return $mail;
    }

    public function toArray(mixed $notifiable): array
    {
        return [
            'type' => 'auth_two_factor_'.$this->action,
            'title' => 'Cambio en 2FA',
            'message' => "2FA {$this->action} en tu cuenta",
            'ip_address' => $this->ipAddress,
            'action_url' => route('settings.auth.two-factor'),
        ];
    }
}
