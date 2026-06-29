<?php

namespace Modules\Auth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Auth\Models\TrustedDevice;

class NewDeviceLoginNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly TrustedDevice $device,
    ) {}

    public function via(mixed $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $platform = $this->device->platform ?: 'desconocida';
        $browser = $this->device->browser ?: 'desconocido';
        $ip = $this->device->ip_address ?: 'n/d';

        return (new MailMessage)
            ->subject('Nuevo inicio de sesión detectado')
            ->greeting("Hola {$notifiable->firstname},")
            ->line('Hemos detectado un inicio de sesión en tu cuenta desde un dispositivo nuevo:')
            ->line("Plataforma: {$platform}")
            ->line("Navegador: {$browser}")
            ->line("IP: {$ip}")
            ->line('Si fuiste tú, puedes ignorar este mensaje.')
            ->line('Si no reconoces este acceso, cambia tu contraseña inmediatamente y revisa tus sesiones activas.')
            ->action('Revisar sesiones', route('settings.auth.sessions'));
    }

    public function toArray(mixed $notifiable): array
    {
        return [
            'type' => 'auth_new_device',
            'title' => 'Nuevo dispositivo detectado',
            'message' => sprintf(
                'Acceso desde %s (%s) — IP %s',
                $this->device->browser ?: 'Navegador desconocido',
                $this->device->platform ?: 'Plataforma desconocida',
                $this->device->ip_address ?: 'n/d',
            ),
            'device_id' => $this->device->id,
            'action_url' => route('settings.auth.sessions'),
        ];
    }
}
