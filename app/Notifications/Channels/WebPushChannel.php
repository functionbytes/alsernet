<?php

namespace App\Notifications\Channels;

use App\Models\PushSubscription;
use Illuminate\Notifications\Notification;
use Modules\HelpdeskSocial\Jobs\SendWebPushNotificationJob;

/**
 * Canal de Notification genérico para Web Push (VAPID) — cualquier
 * Notification del sistema puede sumar 'webpush' a via() y definir
 * toWebPush($notifiable): array{title,body,url?,tag?,requireInteraction?}.
 *
 * Reusa SendWebPushNotificationJob (ya existía en HelpdeskSocial, antes solo
 * llamado por WebPushService::sendToPermission — mismo job, ahora con dos
 * entradas) en vez de duplicar el envío real vía minishlink/web-push.
 */
class WebPushChannel
{
    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toWebPush')) {
            return;
        }

        $payload = $notification->toWebPush($notifiable);

        if (! $payload) {
            return;
        }

        $userId = is_object($notifiable) ? ($notifiable->id ?? null) : null;

        if (! $userId) {
            return;
        }

        PushSubscription::where('user_id', $userId)
            ->get()
            ->each(fn (PushSubscription $subscription) => SendWebPushNotificationJob::dispatch(
                $subscription->toArray(),
                $payload
            ));
    }
}
