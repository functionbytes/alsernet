<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Widget Permissions
    |--------------------------------------------------------------------------
    |
    | Define permissions for widget management
    |
    */

    'permissions' => [
        [
            'name' => 'widget.view',
            'display_name' => 'View Widgets',
            'description' => 'View widgets',
            'module' => 'Widget',
        ],
        [
            'name' => 'widget.create',
            'display_name' => 'Create Widgets',
            'description' => 'Create new widgets',
            'module' => 'Widget',
        ],
        [
            'name' => 'widget.edit',
            'display_name' => 'Edit Widgets',
            'description' => 'Edit existing widgets',
            'module' => 'Widget',
        ],
        [
            'name' => 'widget.delete',
            'display_name' => 'Delete Widgets',
            'description' => 'Delete widgets',
            'module' => 'Widget',
        ],
        [
            'name' => 'widget.manage',
            'display_name' => 'Manage Widgets',
            'description' => 'Full widget management access',
            'module' => 'Widget',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure widget caching behavior
    |
    */

    'cache' => [
        'enabled' => env('WIDGET_CACHE_ENABLED', true),
        'lifetime' => env('WIDGET_CACHE_LIFETIME', 3600),
    ],

    /*
    |--------------------------------------------------------------------------
    | Widget Groups
    |--------------------------------------------------------------------------
    |
    | Define default widget groups
    |
    */

    'groups' => [
        'sidebar' => [
            'title' => 'Sidebar Widgets',
            'description' => 'Widgets for sidebar areas',
        ],
        'header' => [
            'title' => 'Header Widgets',
            'description' => 'Widgets for header areas',
        ],
        'footer' => [
            'title' => 'Footer Widgets',
            'description' => 'Widgets for footer areas',
        ],
        'content' => [
            'title' => 'Content Widgets',
            'description' => 'Widgets for content areas',
        ],
    ],
];
