<?php

namespace Modules\Backup\Notifications;

use Spatie\Backup\Notifications\Notifiable as BaseNotifiable;

/**
 * Silent backup notification class.
 *
 * This notification handles backup events but doesn't send actual notifications.
 * It prevents "There is no notification class that can handle event" errors
 * from the Spatie Laravel Backup package.
 */
class BackupNotification extends BaseNotifiable
{
    /**
     * Get the receivers of the notification.
     * Return an empty array so no notifications are sent.
     *
     * @return array
     */
    public function getNotifiables()
    {
        return [];
    }
}
