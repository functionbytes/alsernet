<?php

namespace Modules\HelpdeskSocial\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\HelpdeskSocial\Jobs\SendWebPushNotificationJob;

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
