<?php

namespace Modules\HelpdeskSocial\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SocialAccountHealthAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly array $issues)
    {
        $this->onQueue('notifications');
    }

    public function via(mixed $notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toArray(mixed $notifiable): array
    {
        return [
            'type' => 'helpdesk_social_health_alert',
            'title' => 'Alerta de salud: cuentas sociales',
            'message' => count($this->issues).' problema(s) detectado(s) en las cuentas sociales.',
            'issues' => $this->issues,
            'action_url' => route('helpdesksocial.accounts.index'),
        ];
    }

    public function toBroadcast(mixed $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    /**
     * @return array<string, mixed>
     */
    public function toWebPush(mixed $notifiable): array
    {
        $array = $this->toArray($notifiable);

        return [
            'title' => $array['title'],
            'body' => $array['message'],
            'url' => $array['action_url'],
            'tag' => $array['type'],
            'requireInteraction' => false,
            'data' => [
                'type' => $array['type'],
                'issues_count' => count($this->issues),
            ],
        ];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $url = route('helpdesksocial.accounts.index');
        $name = trim(($notifiable->firstname ?? '').' '.($notifiable->lastname ?? '')) ?: 'Hola';

        return (new MailMessage)
            ->subject('[Alerta] Problemas detectados en cuentas sociales')
            ->markdown('helpdesksocial::emails.social-account-health-alert', [
                'issues' => $this->issues,
                'url' => $url,
                'name' => $name,
            ]);
    }
}
