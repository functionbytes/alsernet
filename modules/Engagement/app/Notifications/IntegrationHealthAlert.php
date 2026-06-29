<?php

namespace Modules\Engagement\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Engagement\Models\PlatformIntegration;

class IntegrationHealthAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly PlatformIntegration $integration,
        private readonly string $issue,
        private readonly array $details = [],
    ) {}

    public function via(mixed $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(mixed $notifiable): array
    {
        return [
            'type' => 'engagement_integration_health',
            'title' => 'Alerta de integración: '.$this->integration->platform,
            'message' => $this->issue,
            'integration_id' => $this->integration->id,
            'platform' => $this->integration->platform,
            'inbox_id' => $this->integration->inbox_id,
            'details' => $this->details,
            'action_url' => url('/panel/settings/engagement/platforms/page'),
        ];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $brand = config('engagement.brand.name', 'Engagement');
        $msg = (new MailMessage)
            ->subject('['.$brand.'] Alerta integración '.$this->integration->platform)
            ->greeting('Alerta de salud en integración')
            ->line($this->issue)
            ->line('Plataforma: **'.$this->integration->platform.'** (id #'.$this->integration->id.')');

        foreach ($this->details as $key => $value) {
            $msg->line(ucfirst((string) $key).': '.(is_scalar($value) ? (string) $value : json_encode($value)));
        }

        return $msg
            ->action('Ver integraciones', url('/panel/settings/engagement/platforms/page'))
            ->line('Revisa los logs de webhook si la alerta persiste.');
    }
}
