<?php

return [
    'name' => 'Database',

    /*
    |--------------------------------------------------------------------------
    | Database Cleanup Configuration
    |--------------------------------------------------------------------------
    |
    | Enable or disable the database cleanup feature. When enabled, users
    | with appropriate permissions can truncate tables from the settings panel.
    |
    */
    'cleanup' => [
        'enabled' => env('CMS_ENABLED_CLEANUP_DATABASE', false),
    ],
];
