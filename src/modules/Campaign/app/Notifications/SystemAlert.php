<?php

namespace Modules\Campaign\Notifications;

use App\Models\Notifications\NotificationPreference;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class SystemAlert extends Notification implements ShouldBroadcast, ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $title,
        public string $message,
        public string $severity = 'info',
        public ?string $actionUrl = null,
        public ?string $actionText = null
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        if (NotificationPreference::isEnabled($notifiable->id, 'in_app', 'system.alert')) {
            $channels[] = 'database';
        }

        if (NotificationPreference::isEnabled($notifiable->id, 'push', 'system.alert')) {
            $channels[] = 'broadcast';
        }

        return $channels;
    }

    /**
     * Get the database representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'severity' => $this->severity,
            'title' => $this->title,
            'message' => $this->message,
            'icon' => $this->getIconBySeverity($this->severity),
            'color' => $this->getColorBySeverity($this->severity),
            'action_url' => $this->actionUrl,
            'action_text' => $this->actionText ?? 'Ver detalles',
            'priority' => $this->severity === 'critical' ? 'high' : 'normal',
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'severity' => $this->severity,
            'title' => $this->title,
            'message' => $this->message,
            'icon' => $this->getIconBySeverity($this->severity),
            'color' => $this->getColorBySeverity($this->severity),
            'action_url' => $this->actionUrl,
            'created_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    /**
     * Determinar el tipo de notificación para broadcasting
     */
    public function broadcastType(): string
    {
        return 'system.alert';
    }

    /**
     * Determinar icono según severidad
     */
    protected function getIconBySeverity(string $severity): string
    {
        return match ($severity) {
            'critical' => 'fas fa-exclamation-triangle',
            'warning' => 'fas fa-exclamation-circle',
            'success' => 'fas fa-check-circle',
            default => 'fas fa-info-circle',
        };
    }

    /**
     * Determinar color según severidad
     */
    protected function getColorBySeverity(string $severity): string
    {
        return match ($severity) {
            'critical' => 'danger',
            'warning' => 'warning',
            'success' => 'success',
            default => 'info',
        };
    }
}
