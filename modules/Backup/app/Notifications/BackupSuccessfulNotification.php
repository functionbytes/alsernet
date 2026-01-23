<?php

namespace Modules\Backup\Notifications;

use Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification as BaseNotification;

/**
 * Silent backup successful notification.
 *
 * Handles successful backup events without sending actual notifications.
 */
class BackupSuccessfulNotification extends BaseNotification
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
