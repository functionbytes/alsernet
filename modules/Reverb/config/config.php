<?php

/**
 * Reverb Module Configuration
 *
 * Configuration for Laravel Reverb WebSocket server and broadcasting
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Reverb Server Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the Reverb WebSocket server backups including host,
    | port, and TLS/SSL backups for secure connections.
    */

    'server' => [
        'host' => env('REVERB_SERVER_HOST', '0.0.0.0'),
        'port' => env('REVERB_SERVER_PORT', 8080),
        'debug' => env('REVERB_DEBUG', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Broadcasting Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how clients connect to the Reverb server.
    | These backups are used by the client-side Echo instance.
    */

    'broadcasting' => [
        'host' => env('REVERB_HOST', 'localhost'),
        'port' => env('REVERB_PORT', 8080),
        'scheme' => env('REVERB_SCHEME', 'http'),
        'app_id' => env('REVERB_APP_ID', 'your-app-id'),
        'app_key' => env('REVERB_APP_KEY', 'your-app-key'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Channel Configuration
    |--------------------------------------------------------------------------
    |
    | Define channel groups and their default backups.
    */

    'channels' => [
        'public' => [
            'description' => 'Public broadcast channels accessible to all users',
            'enabled' => true,
        ],
        'private' => [
            'description' => 'Private channels authenticated per user',
            'enabled' => true,
        ],
        'presence' => [
            'description' => 'Presence channels for real-time user presence tracking',
            'enabled' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Broadcasting Settings
    |--------------------------------------------------------------------------
    |
    | Configure which events are broadcast and their behavior.
    */

    'events' => [
        'broadcast_on_create' => env('REVERB_BROADCAST_ON_CREATE', true),
        'broadcast_on_update' => env('REVERB_BROADCAST_ON_UPDATE', true),
        'broadcast_on_delete' => env('REVERB_BROADCAST_ON_DELETE', true),
        'include_model_in_broadcast' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Features Toggle
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific Reverb features.
    */

    'features' => [
        'presence_tracking' => true,
        'event_broadcasting' => true,
        'client_to_client_messages' => false,
        'channel_authentication' => true,
    ],
];
