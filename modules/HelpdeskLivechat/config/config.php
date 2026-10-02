<?php

return [
    'name' => 'HelpdeskLivechat',

    /*
    |--------------------------------------------------------------------------
    | Per-website_token rate limits (public widget API)
    |--------------------------------------------------------------------------
    | Applied by ThrottleByWebsiteToken on top of the per-IP `throttle:`
    | middleware. Buckets are keyed by the middleware parameter; a bucket
    | falls back to `default` when not defined. `max_attempts` of 0 disables
    | the bucket. Limits are intentionally generous: they exist to cap
    | distributed abuse against a single store token, not normal traffic.
    */
    'token_rate_limits' => [
        'default' => [
            'max_attempts' => (int) env('HD_LIVECHAT_TOKEN_LIMIT_DEFAULT', 3600),
            'decay_seconds' => 3600,
        ],
        // Conversation creation: ~5/min sustained per store.
        'conversations' => [
            'max_attempts' => (int) env('HD_LIVECHAT_TOKEN_LIMIT_CONVERSATIONS', 300),
            'decay_seconds' => 3600,
        ],
        // Widget messages: allows ~100 concurrent active chats at human pace.
        'messages' => [
            'max_attempts' => (int) env('HD_LIVECHAT_TOKEN_LIMIT_MESSAGES', 3000),
            'decay_seconds' => 3600,
        ],
        // Public ticket creation from the widget.
        'tickets' => [
            'max_attempts' => (int) env('HD_LIVECHAT_TOKEN_LIMIT_TICKETS', 120),
            'decay_seconds' => 3600,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Retención de datos de tracking del widget
    |--------------------------------------------------------------------------
    | PruneWidgetTrackingJob borra sesiones del widget (y sus vistas de página
    | en cascada) sin actividad desde hace más de N días.
    */
    'retention' => [
        'widget_sessions_days' => (int) env('HD_LIVECHAT_WIDGET_RETENTION_DAYS', 90),
    ],
];
