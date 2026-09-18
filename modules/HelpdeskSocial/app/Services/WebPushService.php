<?php

namespace Modules\HelpdeskSocial\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\HelpdeskSocial\Jobs\SendWebPushNotificationJob;

/**
 * SendWebPushNotificationJob::handle() sigue siendo un stub (el envío real vía
 * minishlink/web-push está comentado, pendiente de configurar VAPID) y la
 * tabla `push_subscriptions` no tiene migración en ningún módulo. Sin este
 * corte temprano, cada SocialCommentReceived/Replied/Escalated —o sea, cada
 * comentario— lanzaba una QueryException al consultar una tabla inexistente.
 * Cortocircuita aquí hasta que exista la tabla y las claves VAPID.
 */
class WebPushService
{
    /**
     * Send a web push notification to all subscribed users who have the given permission.
     *
     * @param  string  $permission  Spatie permission name
     * @param  object  $notification  Laravel notification instance with toWebPush() or toArray()
     */
    public function sendToPermission(string $permission, object $notification): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        $userIds = $this->getUserIdsWithPermission($permission);

        if ($userIds->isEmpty()) {
            return;
        }

        $subscriptions = $this->getSubscriptionsForUsers($userIds);

        if ($subscriptions->isEmpty()) {
            return;
        }

        $payload = method_exists($notification, 'toWebPush')
            ? $notification->toWebPush((object) [])
            : $notification->toArray((object) []);

        foreach ($subscriptions as $subscription) {
            SendWebPushNotificationJob::dispatch((array) $subscription, $payload);
        }
    }

    /**
     * Send a raw payload to all subscribed users who have the given permission.
     *
     * @param  string  $permission  Spatie permission name
     * @param  array<string, mixed>  $payload
     */
    public function sendRawToPermission(string $permission, array $payload): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        $userIds = $this->getUserIdsWithPermission($permission);

        if ($userIds->isEmpty()) {
            return;
        }

        $subscriptions = $this->getSubscriptionsForUsers($userIds);

        if ($subscriptions->isEmpty()) {
            return;
        }

        foreach ($subscriptions as $subscription) {
            SendWebPushNotificationJob::dispatch((array) $subscription, $payload);
        }
    }

    /**
     * VAPID configurado + tabla de suscripciones presente. Cacheado en memoria
     * (estático) para no repetir Schema::hasTable() en cada evento dentro del
     * mismo request/job.
     */
    private function isConfigured(): bool
    {
        static $configured = null;

        if ($configured !== null) {
            return $configured;
        }

        $hasVapidKeys = filled(config('services.webpush.public_key')) && filled(config('services.webpush.private_key'));

        return $configured = $hasVapidKeys && Schema::hasTable('push_subscriptions');
    }

    /**
     * @return Collection<int, int>
     */
    private function getUserIdsWithPermission(string $permission): Collection
    {
        return User::permission($permission)
            ->pluck('id');
    }

    /**
     * @param  Collection<int, int>  $userIds
     * @return Collection<int, \stdClass>
     */
    private function getSubscriptionsForUsers(Collection $userIds): Collection
    {
        return DB::table('push_subscriptions')
            ->whereIn('user_id', $userIds)
            ->get();
    }
}
