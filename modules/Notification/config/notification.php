<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Notification Configuration
    |--------------------------------------------------------------------------
    */

    'cleanup' => [
        'enabled' => env('NOTIFICATION_CLEANUP_ENABLED', true),
        'days' => env('NOTIFICATION_CLEANUP_DAYS', 30),
    ],

    'push_notifications' => [
        'enabled' => env('PUSH_NOTIFICATIONS_ENABLED', true),
        'max_retries' => env('PUSH_NOTIFICATION_MAX_RETRIES', 3),
    ],

    'channels' => [
        'database' => env('NOTIFICATION_CHANNEL_DATABASE', true),
        'mail' => env('NOTIFICATION_CHANNEL_MAIL', true),
        'push' => env('NOTIFICATION_CHANNEL_PUSH', false),
    ],

    'retention_days' => env('NOTIFICATION_RETENTION_DAYS', 30),
];
