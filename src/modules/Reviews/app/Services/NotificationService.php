<?php

namespace Modules\Reviews\Services;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Modules\Reviews\Models\UserNotificationPreference;

class NotificationService
{
    public const TYPE_NEW_REVIEW = 'new_review';

    public const TYPE_NEGATIVE_REVIEW = 'negative_review';

    public const TYPE_POSITIVE_REVIEW = 'positive_review';

    public const TYPE_EXPORT_READY = 'export_ready';

    public const TYPE_REPLY_PUBLISHED = 'reply_published';

    public const TYPE_CONNECTION_EXPIRING = 'connection_expiring';

    public const TYPE_DAILY_DIGEST = 'daily_digest';

    public const TYPE_URGENT_REVIEW = 'urgent_review';

    public const TYPE_WEEKLY_DIGEST = 'weekly_digest';

    public const CHANNEL_MAIL = 'mail';

    public const CHANNEL_DATABASE = 'database';

    public const CHANNEL_SLACK = 'slack';

    public static function getAvailableTypes(): array
    {
        return [
            self::TYPE_NEW_REVIEW => [
                'label' => 'Nueva reseña',
                'description' => 'Notificar cuando se recibe cualquier reseña nueva',
                'supports_filters' => true,
            ],
            self::TYPE_NEGATIVE_REVIEW => [
                'label' => 'Reseña negativa',
                'description' => 'Notificar solo reseñas negativas (1-3 estrellas)',
                'supports_filters' => true,
            ],
            self::TYPE_POSITIVE_REVIEW => [
                'label' => 'Reseña positiva',
                'description' => 'Notificar solo reseñas positivas (4-5 estrellas)',
                'supports_filters' => true,
            ],
            self::TYPE_EXPORT_READY => [
                'label' => 'Exportación lista',
                'description' => 'Notificar cuando una exportación está lista para descargar',
                'supports_filters' => false,
            ],
            self::TYPE_REPLY_PUBLISHED => [
                'label' => 'Respuesta publicada',
                'description' => 'Notificar cuando una respuesta ha sido publicada',
                'supports_filters' => false,
            ],
            self::TYPE_CONNECTION_EXPIRING => [
                'label' => 'Conexión expirando',
                'description' => 'Notificar cuando una conexión de Google está por expirar',
                'supports_filters' => false,
            ],
            self::TYPE_DAILY_DIGEST => [
                'label' => 'Resumen diario',
                'description' => 'Resumen diario de actividad de reseñas',
                'supports_filters' => false,
            ],
            self::TYPE_URGENT_REVIEW => [
                'label' => 'Reseña urgente',
                'description' => 'Notificar urgentemente reseñas de 1-2 estrellas sin respuesta',
                'supports_filters' => false,
            ],
            self::TYPE_WEEKLY_DIGEST => [
                'label' => 'Resumen semanal',
                'description' => 'Resumen semanal de actividad de reseñas (cada lunes)',
                'supports_filters' => false,
            ],
        ];
    }

    public static function getAvailableChannels(): array
    {
        return [
            self::CHANNEL_MAIL => 'Email',
            self::CHANNEL_DATABASE => 'In-App',
            self::CHANNEL_SLACK => 'Slack',
        ];
    }

    public function checkPreferences(User $user, string $notificationType, array $context = []): bool
    {
        $preference = UserNotificationPreference::query()
            ->where('user_id', $user->id)
            ->where('notification_type', $notificationType)
            ->first();

        if (! $preference) {
            return $this->getDefaultPreference($notificationType);
        }

        return $preference->shouldNotify($context);
    }

    public function getEnabledChannels(User $user, string $notificationType): array
    {
        $preference = UserNotificationPreference::query()
            ->where('user_id', $user->id)
            ->where('notification_type', $notificationType)
            ->first();

        if (! $preference || ! $preference->is_enabled) {
            return $this->getDefaultChannels($notificationType);
        }

        return $preference->getChannels();
    }

    public function sendIfEnabled(User|Collection $users, Notification $notification, string $notificationType, array $context = []): void
    {
        if ($users instanceof User) {
            $users = collect([$users]);
        }

        $users->each(function (User $user) use ($notification, $notificationType, $context) {
            if (! $this->checkPreferences($user, $notificationType, $context)) {
                return;
            }

            $channels = $this->getEnabledChannels($user, $notificationType);

            if (empty($channels)) {
                return;
            }

            $user->notify($notification);
        });
    }

    public function initializeDefaultPreferences(User $user): void
    {
        foreach (array_keys(self::getAvailableTypes()) as $type) {
            UserNotificationPreference::query()->firstOrCreate(
                [
                    'user_id' => $user->id,
                    'notification_type' => $type,
                ],
                [
                    'channels' => $this->getDefaultChannels($type),
                    'is_enabled' => $this->getDefaultPreference($type),
                    'filters' => null,
                ]
            );
        }
    }

    protected function getDefaultPreference(string $notificationType): bool
    {
        return match ($notificationType) {
            self::TYPE_NEGATIVE_REVIEW => true,
            self::TYPE_EXPORT_READY => true,
            self::TYPE_CONNECTION_EXPIRING => true,
            self::TYPE_DAILY_DIGEST => false,
            self::TYPE_URGENT_REVIEW => true,
            self::TYPE_WEEKLY_DIGEST => true,
            default => true,
        };
    }

    protected function getDefaultChannels(string $notificationType): array
    {
        return match ($notificationType) {
            self::TYPE_NEGATIVE_REVIEW => [self::CHANNEL_MAIL, self::CHANNEL_DATABASE],
            self::TYPE_EXPORT_READY => [self::CHANNEL_DATABASE],
            self::TYPE_CONNECTION_EXPIRING => [self::CHANNEL_MAIL, self::CHANNEL_DATABASE],
            self::TYPE_DAILY_DIGEST => [self::CHANNEL_MAIL],
            self::TYPE_URGENT_REVIEW => [self::CHANNEL_MAIL, self::CHANNEL_DATABASE],
            self::TYPE_WEEKLY_DIGEST => [self::CHANNEL_MAIL],
            default => [self::CHANNEL_DATABASE],
        };
    }
}
