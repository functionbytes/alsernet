<?php

return [
    'name' => 'Database',

    /*
    |--------------------------------------------------------------------------
    | Database Cleanup Configuration
    |--------------------------------------------------------------------------
    |
    | Enable or disable the database cleanup feature. When enabled, users
    | with appropriate permissions can truncate tables from the admin panel.
    |
    */
    'cleanup' => [
        'enabled' => env('DATABASE_CLEANUP_ENABLED', false),
    ],
];
