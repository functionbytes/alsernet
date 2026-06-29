<?php

namespace Modules\Notification\Observers;

use Illuminate\Notifications\DatabaseNotification;
use Modules\Notification\Events\NewNotificationEvent;

class DatabaseNotificationObserver
{
    /**
     * Dispatch a real-time broadcast event whenever a database notification is created,
     * so the header badge and dropdown update without polling.
     */
    public function created(DatabaseNotification $notification): void
    {
        $userId = $notification->notifiable_id;
        $data = $notification->data;

        broadcast(new NewNotificationEvent(
            userId: (int) $userId,
            title: $data['title'] ?? 'Nueva notificación',
            message: $data['message'] ?? '',
            type: $data['color'] ?? 'info',
            actionUrl: $data['action_url'] ?? null,
            icon: $data['icon'] ?? null,
        ))->toOthers();
    }
}
