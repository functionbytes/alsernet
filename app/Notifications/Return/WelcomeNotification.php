<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Determinar los canales de entrega
     */
    public function via($notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->canReceiveNotification('mail', self::class)) {
            $channels[] = 'mail';
        }

        if ($notifiable->canReceiveNotification('push', self::class)) {
            $channels[] = 'broadcast';
        }

        return $channels;
    }

    /**
     * Obtener la representación de correo
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('¡Bienvenido a A-alvarez!')
            ->greeting('¡Hola ' . $notifiable->name . '!')
            ->line('Te damos la bienvenida a nuestra plataforma.')
            ->line('Estamos emocionados de tenerte con nosotros.')
            ->action('Explorar la plataforma', url('/dashboard'))
            ->line('¡Gracias por unirte a nosotros!');
    }

    /**
     * Obtener la representación de base de datos
     */
    public function toArray($notifiable): array
    {
        return [
            'title' => '¡Bienvenido a A-alvarez!',
            'message' => 'Te damos la bienvenida a nuestra plataforma.',
            'action_url' => url('/dashboard'),
            'action_text' => 'Explorar',
            'type' => 'welcome',
            'data' => $this->data,
        ];
    }

    /**
     * Obtener la representación de broadcast
     */
    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'title' => '¡Bienvenido a A-alvarez!',
            'message' => 'Te damos la bienvenida a nuestra plataforma.',
            'type' => 'welcome',
            'data' => $this->data,
        ]);
    }

    /**
     * Obtener la representación para notificaciones push
     */
    public function toPush($notifiable): array
    {
        return [
            'title' => '¡Bienvenido a A-alvarez!',
            'body' => 'Te damos la bienvenida a nuestra plataforma.',
            'icon' => 'welcome',
            'data' => [
                'type' => 'welcome',
                'action_url' => url('/dashboard'),
                'user_id' => $notifiable->id,
            ],
        ];
    }

    /**
     * Obtener la representación para SMS
     */
    public function toSms($notifiable): string
    {
        return "¡Hola {$notifiable->name}! Te damos la bienvenida a A-alvarez. Explora tu nueva cuenta en: " . url('/dashboard');
    }
}
