<?php

return [
    /*
    |--------------------------------------------------------------------------
    | PrestaShop Database Connection
    |--------------------------------------------------------------------------
    |
    | Configuration for connecting to PrestaShop database.
    |
    */
    'connections' => [
        'primary' => env('PRESTASHOP_DB_CONNECTION', 'prestashop'),
        'secondary' => env('PRESTASHOP_DB_CONNECTION_12', 'prestashop12'),
        'supplier' => env('PRESTASHOP_DB_CONNECTION_S', 'prestashops'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Configuration
    |--------------------------------------------------------------------------
    */
    'sync' => [
        'enabled' => env('PRESTASHOP_SYNC_ENABLED', false),
        'queue' => env('PRESTASHOP_SYNC_QUEUE', 'default'),
        'retry_times' => 3,
        'retry_delay' => 60, // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Product Configuration
    |--------------------------------------------------------------------------
    */
    'products' => [
        'default_shop_id' => env('PRESTASHOP_DEFAULT_SHOP_ID', 1),
        'default_lang_id' => env('PRESTASHOP_DEFAULT_LANG_ID', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Order States
    |--------------------------------------------------------------------------
    |
    | PrestaShop paid order states configuration
    |
    */
    'paid_order_states' => [2, 3, 4, 10, 13],
];
