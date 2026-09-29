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

    // hd/api/*: API pública del widget de chat, llamada desde la tienda (otro
    // origen). Sin credenciales; los dominios los filtra ValidateTrustedOrigin.
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'hd/api/*'],

    'allowed_methods' => ['*'],

    // Seguridad 29-sep-2026: antes ['*']. Las llamadas servidor a servidor (tienda,
    // ERP) no envían Origin y no les afecta; esto solo limita a los navegadores.
    'allowed_origins' => array_values(array_filter(array_map('trim', explode(',', (string) env(
        'CORS_ALLOWED_ORIGINS',
        'https://webadmin.a-alvarez.com,https://www.a-alvarez.com,https://a-alvarez.com'
    ))))),

    // Subdominios https de a-alvarez.com (tiendas por país, etc.).
    'allowed_origins_patterns' => array_values(array_filter([
        env('CORS_ALLOWED_ORIGINS_PATTERN', '#^https://([a-z0-9-]+\.)*a-alvarez\.com$#'),
    ])),

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
