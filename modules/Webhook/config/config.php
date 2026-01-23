<?php

return [
    'name' => 'Webhook',
    'description' => 'Webhook module for managing webhook integrations and event-driven subscriptions',

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configure default webhook backups and behavior
    |
    */

    'max_retries' => env('WEBHOOK_MAX_RETRIES', 3),

    'retry_delay' => env('WEBHOOK_RETRY_DELAY', 300), // seconds

    'timeout' => env('WEBHOOK_TIMEOUT', 30), // seconds

    'events' => [
        // Define available webhook events here
        // Example: 'order.created', 'order.updated', 'order.deleted'
    ],
];
