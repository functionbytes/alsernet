<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your backups for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these backups as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'broadcasting/auth'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    // Mobile apps (Flutter) don't send Origin headers, but WebViews and dev tools might.
    // Production: lock to specific domains via CORS_ALLOWED_ORIGINS env.
    'allowed_origins' => array_filter(explode(',', env('CORS_ALLOWED_ORIGINS', env('APP_URL', '*')))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'X-Requested-With', 'Authorization', 'X-CSRF-TOKEN', 'Accept', 'Accept-Language', 'Idempotency-Key'],

    'exposed_headers' => ['X-API-Version', 'Retry-After', 'X-RateLimit-Remaining', 'X-RateLimit-Limit'],

    'max_age' => 86400,

    'supports_credentials' => false,

];
