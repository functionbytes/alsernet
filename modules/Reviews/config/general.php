<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Review Sync Interval
    |--------------------------------------------------------------------------
    |
    | How often (in minutes) should reviews be synced from Google automatically
    | via the scheduler. Default: 15 minutes.
    |
    */
    'sync_interval_minutes' => env('REVIEWS_SYNC_INTERVAL', 15),

    /*
    |--------------------------------------------------------------------------
    | Auto-Publish Replies
    |--------------------------------------------------------------------------
    |
    | When enabled, approved replies will be automatically published to Google
    | without requiring manual confirmation. Default: false.
    |
    */
    'auto_publish_replies' => env('REVIEWS_AUTO_PUBLISH', false),

    /*
    |--------------------------------------------------------------------------
    | Default Moderation Visibility
    |--------------------------------------------------------------------------
    |
    | Default visibility status for newly synced reviews. When true, reviews
    | are visible on widgets by default. Default: true.
    |
    */
    'default_moderation_visible' => true,

    /*
    |--------------------------------------------------------------------------
    | Rate Limit Per Minute
    |--------------------------------------------------------------------------
    |
    | Maximum number of API requests to Google per minute to avoid hitting
    | rate limits. Default: 60.
    |
    */
    'rate_limit_per_minute' => 60,

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Cache TTL (time-to-live) settings for various cached data.
    |
    */
    'cache' => [
        'stats_ttl_minutes' => env('REVIEWS_STATS_CACHE_TTL', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Urgent Review Notifications
    |--------------------------------------------------------------------------
    |
    | Configure urgent notifications for low-star reviews without a reply.
    | Set slack_webhook to send Slack alerts via incoming webhook URL.
    | urgent_rating_threshold defines the maximum rating that triggers alerts.
    |
    */
    'notifications' => [
        'slack_webhook' => env('REVIEWS_SLACK_WEBHOOK', null),
        'urgent_rating_threshold' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for receiving push notifications from Google via Cloud Pub/Sub.
    | Set REVIEWS_GOOGLE_WEBHOOK_TOKEN to a secret shared with the Google
    | Pub/Sub subscription so incoming requests can be authenticated.
    |
    */
    'webhooks' => [
        'google_verification_token' => env('REVIEWS_GOOGLE_WEBHOOK_TOKEN', null),
        'enabled' => env('REVIEWS_WEBHOOKS_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | DeepL Translation
    |--------------------------------------------------------------------------
    |
    | Configure DeepL automatic translation for review comments.
    | Uses the DeepL free API (api-free.deepl.com) when an API key is provided.
    |
    */
    'deepl' => [
        'api_key' => env('DEEPL_API_KEY', null),
        'target_language' => env('DEEPL_TARGET_LANG', 'ES'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Check Thresholds
    |--------------------------------------------------------------------------
    |
    | Thresholds for the health check endpoint.
    |
    */
    'health_thresholds' => [
        'pending_syncs' => [
            'critical' => 50,
            'warning' => 20,
        ],
        'failed_replies' => [
            'critical' => 20,
            'warning' => 5,
        ],
        'last_sync_minutes' => [
            'critical' => 60,
            'warning' => 30,
        ],
    ],
];
