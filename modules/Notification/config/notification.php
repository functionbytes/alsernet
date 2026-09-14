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

    'circuit_breaker' => [
        'fcm' => [
            'failure_threshold' => env('FCM_CIRCUIT_BREAKER_THRESHOLD', 5),
            'recovery_seconds' => env('FCM_CIRCUIT_BREAKER_RECOVERY', 60),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Types
    |--------------------------------------------------------------------------
    | System-wide notification type identifiers stored in the `type` column
    | of the `notifications` table. Each type maps to a human-readable label
    | and a default icon/color for display purposes.
    */
    'types' => [
        'system' => [
            'label' => 'Sistema',
            'icon' => 'fas fa-cog',
            'color' => 'secondary',
        ],
        'review' => [
            'label' => 'Reseña',
            'icon' => 'fas fa-star',
            'color' => 'warning',
        ],
        'attention' => [
            'label' => 'Atención / PQRSF',
            'icon' => 'fas fa-headset',
            'color' => 'info',
        ],
        'blog' => [
            'label' => 'Blog',
            'icon' => 'fas fa-newspaper',
            'color' => 'primary',
        ],
        'security' => [
            'label' => 'Seguridad',
            'icon' => 'fas fa-shield-alt',
            'color' => 'danger',
        ],
    ],
];
