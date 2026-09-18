<?php

namespace Modules\Auth\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notify a user when an administrator impersonates their account.
 * Transparency / GDPR requirement.
 */
class ImpersonatedByAdminNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly User $impersonator,
        public readonly string $ipAddress,
    ) {}

    public function via(mixed $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $adminName = trim("{$this->impersonator->firstname} {$this->impersonator->lastname}") ?: $this->impersonator->email;

        return (new MailMessage)
            ->subject('Acceso administrativo a tu cuenta')
            ->greeting("Hola {$notifiable->firstname},")
            ->line("Un administrador ({$adminName}) accedió a tu cuenta con fines de soporte o mantenimiento.")
            ->line('IP del acceso: '.$this->ipAddress)
            ->line('Fecha: '.now()->format('d M Y H:i'))
            ->line('Esta sesión queda registrada en los logs de auditoría del sistema.')
            ->line('Si no reconoces este acceso o sospechas de uso indebido, contacta al soporte inmediatamente.');
    }

    public function toArray(mixed $notifiable): array
    {
        return [
            'type' => 'auth_impersonated',
            'title' => 'Acceso administrativo',
            'message' => 'Un administrador accedió a tu cuenta',
            'impersonator_id' => $this->impersonator->id,
            'ip_address' => $this->ipAddress,
        ];
    }
}
