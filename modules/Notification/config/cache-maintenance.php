<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cache Maintenance Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for system cache maintenance commands
    |
    */

    // Composer executable path - leave empty for auto-discovery
    'composer_path' => env('COMPOSER_PATH', ''),

    // PHP binary path - leave empty to use current PHP_BINARY
    'php_path' => env('PHP_PATH', ''),

    // Enable/disable each maintenance command
    'commands' => [
        'cache_clear' => true,
        'config_clear' => true,
        'config_cache' => true,
        'route_clear' => true,
        'view_clear' => true,
        'optimize_clear' => true,
        'composer_dump_autoload' => true,
    ],
];
