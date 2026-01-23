<?php

namespace Modules\Notification\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Trait HasNotificationSystem
 *
 * Gestiona el sistema de notificaciones del usuario, incluyendo preferencias,
 * tokens de push notifications y formateo de notificaciones.
 */
trait HasNotificationSystem
{
    /**
     * Preferencias de notificaciones del usuario
     */
    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(\App\Models\Notifications\NotificationPreference::class);
    }

    /**
     * Tokens de push notifications del usuario
     */
    public function pushTokens(): HasMany
    {
        return $this->hasMany(\App\Models\Notifications\NotificationPushToken::class);
    }

    /**
     * Verificar si el usuario puede recibir notificaciones en un canal específico
     */
    public function canReceiveNotification(string $channel, string $type): bool
    {
        return \App\Models\Notifications\NotificationPreference::isEnabled($this->id, $channel, $type);
    }

    /**
     * Obtener tokens activos de push notifications
     */
    public function getActivePushTokens()
    {
        return \App\Models\Notifications\NotificationPushToken::activeForUser($this->id);
    }

    /**
     * Obtener notificaciones no leídas con datos formateados
     */
    public function getFormattedUnreadNotifications()
    {
        return $this->unreadNotifications->map(function ($notification) {
            $data = $notification->data;

            return [
                'id' => $notification->id,
                'type' => $notification->type,
                'title' => $data['title'] ?? 'Notificación',
                'message' => $data['message'] ?? '',
                'icon' => $data['icon'] ?? 'fas fa-bell',
                'color' => $data['color'] ?? 'primary',
                'action_url' => $data['action_url'] ?? null,
                'action_text' => $data['action_text'] ?? 'Ver',
                'priority' => $data['priority'] ?? 'normal',
                'created_at' => $notification->created_at->diffForHumans(),
                'is_read' => false,
            ];
        });
    }

    /**
     * Obtener todas las notificaciones con datos formateados
     */
    public function getFormattedNotifications(int $limit = 50)
    {
        return $this->notifications()
            ->take($limit)
            ->get()
            ->map(function ($notification) {
                $data = $notification->data;

                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $data['title'] ?? 'Notificación',
                    'message' => $data['message'] ?? '',
                    'icon' => $data['icon'] ?? 'fas fa-bell',
                    'color' => $data['color'] ?? 'primary',
                    'action_url' => $data['action_url'] ?? null,
                    'action_text' => $data['action_text'] ?? 'Ver',
                    'priority' => $data['priority'] ?? 'normal',
                    'created_at' => $notification->created_at->diffForHumans(),
                    'is_read' => $notification->read_at !== null,
                    'read_at' => $notification->read_at?->toIso8601String(),
                ];
            });
    }

    /**
     * Contar notificaciones no leídas
     */
    public function unreadNotificationsCount(): int
    {
        return $this->unreadNotifications()->count();
    }

    /**
     * Marcar todas las notificaciones como leídas
     */
    public function markAllNotificationsAsRead(): void
    {
        $this->unreadNotifications()->update(['read_at' => now()]);
    }

    /**
     * Personalizar el canal de broadcast para notificaciones
     * Esto define el canal privado donde este usuario recibirá notificaciones en tiempo real
     */
    public function receivesBroadcastNotificationsOn(): string
    {
        return 'users.'.$this->id;
    }

    /**
     * Rutear notificaciones basado en configuraciones del usuario
     */
    public function routeNotificationFor($driver, $notification = null)
    {
        if ($driver === 'vonage') {
            return $this->phone ?? null;
        }

        return $this->routeNotificationForNotifiable($driver, $notification);
    }
}
