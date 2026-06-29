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
];
