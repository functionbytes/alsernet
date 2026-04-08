<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Telescope
    |--------------------------------------------------------------------------
    | Laravel Telescope - Debug assistant for local development.
    | Access: /telescope (admin only)
    */
    'telescope' => [
        'enabled' => env('TELESCOPE_ENABLED', false),
        'path' => env('TELESCOPE_PATH', 'telescope'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pulse
    |--------------------------------------------------------------------------
    | Laravel Pulse - Real-time application performance monitoring.
    | Access: /pulse (admin only)
    */
    'pulse' => [
        'enabled' => env('PULSE_ENABLED', true),
        'path' => env('PULSE_PATH', 'pulse'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Horizon
    |--------------------------------------------------------------------------
    | Laravel Horizon - Queue monitoring dashboard.
    | Access: /horizon (admin only)
    */
    'horizon' => [
        'path' => env('HORIZON_PATH', 'horizon'),
        'notification_email' => env('HORIZON_NOTIFICATION_EMAIL', env('ADMIN_EMAIL')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Checks
    |--------------------------------------------------------------------------
    */
    'health' => [
        'cache_ttl' => env('HEALTH_CACHE_TTL', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Alerts
    |--------------------------------------------------------------------------
    */
    'alerts' => [
        'admin_email' => env('ADMIN_EMAIL'),
        'error_throttle' => env('ERROR_ALERT_THROTTLE', 300),    // seconds (5 minutes)
        'failed_job_limit' => env('FAILED_JOB_ALERT_THRESHOLD', 10),
    ],

];
