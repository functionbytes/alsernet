<?php

namespace Modules\Backup\Notifications;

use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification as BaseNotification;

/**
 * Silent backup failed notification.
 *
 * Handles failed backup events without sending actual notifications.
 */
class BackupFailedNotification extends BaseNotification
{
    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        // Return empty array to prevent any notification from being sent
        return [];
    }
}
