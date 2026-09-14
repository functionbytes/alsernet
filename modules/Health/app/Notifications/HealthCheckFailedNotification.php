<?php

namespace Modules\Health\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HealthCheckFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $checkName,
        private readonly string $status,
        private readonly string $summary,
        private readonly Carbon $checkedAt,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("⚠️ Health Check: {$this->checkName} — {$this->status}")
            ->greeting('Alerta de sistema')
            ->line("El health check **{$this->checkName}** reportó estado: **{$this->status}**")
            ->line("**Resumen**: {$this->summary}")
            ->line("**Detectado**: {$this->checkedAt->format('d/m/Y H:i:s')}")
            ->action('Ver historial', route('settings.health.history'))
            ->salutation('Sistema de monitoreo — '.config('app.name'));
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'check_name' => $this->checkName,
            'status' => $this->status,
            'summary' => $this->summary,
            'checked_at' => $this->checkedAt->toISOString(),
        ];
    }
}
