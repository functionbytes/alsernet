<?php

return [
    'name' => 'Menu',

    /*
    |--------------------------------------------------------------------------
    | Menu Locations
    |--------------------------------------------------------------------------
    |
    | Define the available menu locations for your application.
    |
    */
    'locations' => [
        'header' => 'Header Menu',
        'footer' => 'Footer Menu',
        'sidebar' => 'Sidebar Menu',
        'mobile' => 'Mobile Menu',
    ],

    /*
    |--------------------------------------------------------------------------
    | Maximum Depth
    |--------------------------------------------------------------------------
    |
    | Define the maximum depth for nested menu items.
    |
    */
    'max_depth' => 3,

    /*
    |--------------------------------------------------------------------------
    | Cache Duration
    |--------------------------------------------------------------------------
    |
    | Cache duration in seconds for menu rendering.
    |
    */
    'cache_duration' => 3600,
];
