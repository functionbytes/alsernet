<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => env('PAGE_CACHE_ENABLED', true),
        'ttl_minutes' => env('PAGE_CACHE_TTL', 1440),
        'warm_on_publish' => true,
        'tags' => ['pages'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported Locales
    |--------------------------------------------------------------------------
    | List of locales for which pages should be cached separately
    */
    'supported_locales' => explode(',', env('PAGE_SUPPORTED_LOCALES', 'es,en')),

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */
    'per_page' => env('PAGE_PER_PAGE', 20),

    /*
    |--------------------------------------------------------------------------
    | Default Template
    |--------------------------------------------------------------------------
    */
    'default_template' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Available Page Templates
    |--------------------------------------------------------------------------
    | These templates appear in the page editor's template selector.
    | The key maps to page->template and to the theme view file.
    */
    'templates' => [
        'default' => 'Por defecto',
        'homepage' => 'Página de inicio',
        'services' => 'Servicios',
        'gallery' => 'Galería',
        'contact' => 'Contacto',
    ],

    /*
    |--------------------------------------------------------------------------
    | Blacklisted Pages
    |--------------------------------------------------------------------------
    | Pages that should not be cached
    */
    'blacklist' => env('PAGE_CACHE_BLACKLIST', ''),

    /*
    |--------------------------------------------------------------------------
    | Analytics Configuration
    |--------------------------------------------------------------------------
    */
    'analytics' => [
        'retention_days' => env('PAGE_VIEWS_RETENTION_DAYS', 90),
        'track_bots' => false,
        'track_preview' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications Configuration
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'on_publish' => env('PAGE_NOTIFY_ON_PUBLISH', false),
        'publish_notify_emails' => array_filter(explode(',', env('PAGE_NOTIFY_EMAILS', ''))),
        'notify_subscribers' => env('PAGE_NOTIFY_SUBSCRIBERS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Social Share Configuration
    |--------------------------------------------------------------------------
    */
    'social_share' => [
        'enabled' => env('PAGE_SOCIAL_SHARE', true),
        'networks' => ['facebook', 'twitter', 'linkedin', 'whatsapp'],
    ],
];
