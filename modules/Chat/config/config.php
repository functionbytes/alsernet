<?php

return [
    'name' => 'Chat',

    /*
    |--------------------------------------------------------------------------
    | Email Channel Configuration
    |--------------------------------------------------------------------------
    */
    'email' => [
        'fetch_limit' => env('CHAT_EMAIL_FETCH_LIMIT', 20),
        'threading_lookback_days' => env('CHAT_EMAIL_THREADING_DAYS', 7),
    ],

    /*
    |--------------------------------------------------------------------------
    | CSAT Survey Configuration
    |--------------------------------------------------------------------------
    */
    'csat' => [
        'token_length' => 32,
        'auto_send_on_resolve' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Names
    |--------------------------------------------------------------------------
    */
    'queues' => [
        'default' => env('CHAT_QUEUE_DEFAULT', 'default'),
        'messages' => env('CHAT_QUEUE_MESSAGES', 'messages'),
        'notifications' => env('CHAT_QUEUE_NOTIFICATIONS', 'notifications'),
    ],
];
