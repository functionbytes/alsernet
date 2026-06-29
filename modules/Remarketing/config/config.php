<?php

return [
    'name' => 'Remarketing',

    'webhooks' => [
        'email_events_secrets' => [
            'mailrelay' => env('REMARKETING_WEBHOOK_SECRET_MAILRELAY', ''),
            'mailgun' => env('REMARKETING_WEBHOOK_SECRET_MAILGUN', ''),
            'sendgrid' => env('REMARKETING_WEBHOOK_SECRET_SENDGRID', ''),
            'postmark' => env('REMARKETING_WEBHOOK_SECRET_POSTMARK', ''),
        ],
    ],

    'cart_abandonment_minutes' => env('REMARKETING_CART_ABANDONMENT_MINUTES', 60),
    'rfm_winback_days' => env('REMARKETING_RFM_WINBACK_DAYS', 90),
    'segment_cache_ttl' => env('REMARKETING_SEGMENT_CACHE_TTL', 300),
];
