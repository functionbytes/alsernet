<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mailrelay API Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for Mailrelay API integration.
    | Based on FASE 2 section 2.3 - Groups/Lists Management
    |
    */

    /*
    |--------------------------------------------------------------------------
    | API URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the Mailrelay API endpoint.
    | Example: https://your-account.mailrelay.com/api/v1
    |
    */
    'api_url' => env('MAILING_URL', 'https://inoqualab.mailrelay.com/api/v1'),

    /*
    |--------------------------------------------------------------------------
    | API Key
    |--------------------------------------------------------------------------
    |
    | Your Mailrelay API key for authentication.
    | This key is required for all API requests.
    |
    */
    'api_key' => env('MAILING_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum time (in seconds) to wait for an API response.
    | Default: 30 seconds
    |
    */
    'timeout' => env('MAILING_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Connection Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum time (in seconds) to wait for connection establishment.
    | Default: 10 seconds
    |
    */
    'connect_timeout' => env('MAILING_CONNECT_TIMEOUT', 10),

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for automatic retry on failed requests.
    |
    */
    'retry' => [
        'max_attempts' => env('MAILING_RETRY_MAX_ATTEMPTS', 3),
        'delay' => env('MAILING_RETRY_DELAY', 100), // milliseconds
        'multiplier' => env('MAILING_RETRY_MULTIPLIER', 2),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for syncing subscribers with Mailrelay.
    |
    */
    'sync' => [
        // Enable automatic sync after subscriber creation/update
        'auto_sync' => env('MAILING_AUTO_SYNC', true),

        // Sync interval for batch operations (in minutes)
        'batch_interval' => env('MAILING_SYNC_BATCH_INTERVAL', 60),

        // Maximum subscribers to sync in one batch
        'batch_size' => env('MAILING_SYNC_BATCH_SIZE', 100),

        // Enable sync queue for background processing
        'use_queue' => env('MAILING_SYNC_USE_QUEUE', true),

        // Queue name for sync jobs
        'queue_name' => env('MAILING_SYNC_QUEUE_NAME', 'mailing'),

        // Sync retry configuration
        'retry_failed' => env('MAILING_SYNC_RETRY_FAILED', true),
        'max_retries' => env('MAILING_SYNC_MAX_RETRIES', 3),

        // Track sync history
        'track_history' => env('MAILING_SYNC_TRACK_HISTORY', true),

        // Sync subscriber metadata
        'sync_metadata' => env('MAILING_SYNC_METADATA', true),

        // Sync subscriber groups
        'sync_groups' => env('MAILING_SYNC_GROUPS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for receiving webhooks from Mailrelay.
    | Webhooks notify your application about events like:
    | - Email opens
    | - Link clicks
    | - Unsubscribes
    | - Bounces
    |
    */
    'webhook' => [
        // Enable webhook processing
        'enabled' => env('MAILING_WEBHOOK_ENABLED', true),

        // Webhook URL path (relative to APP_URL)
        'path' => env('MAILING_WEBHOOK_PATH', '/api/webhooks/mailrelay'),

        // Webhook secret for signature verification
        'secret' => env('MAILING_WEBHOOK_SECRET'),

        // Verify webhook signatures
        'verify_signature' => env('MAILING_WEBHOOK_VERIFY_SIGNATURE', true),

        // Events to process (empty array means all events)
        'events' => [
            'email.opened',
            'email.clicked',
            'subscriber.unsubscribed',
            'email.bounced',
            'email.complained',
        ],

        // Store webhook logs
        'log_events' => env('MAILING_WEBHOOK_LOG_EVENTS', true),

        // Queue webhook processing
        'use_queue' => env('MAILING_WEBHOOK_USE_QUEUE', true),
        'queue_name' => env('MAILING_WEBHOOK_QUEUE_NAME', 'webhooks'),

        // Webhook timeout for processing (in seconds)
        'timeout' => env('MAILING_WEBHOOK_TIMEOUT', 30),

        // Max payload size (in bytes)
        'max_payload_size' => env('MAILING_WEBHOOK_MAX_PAYLOAD_SIZE', 1048576), // 1MB

        // IP whitelist for webhook requests (optional)
        'ip_whitelist' => env('MAILING_WEBHOOK_IP_WHITELIST')
            ? explode(',', env('MAILING_WEBHOOK_IP_WHITELIST'))
            : [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for caching API responses.
    |
    */
    'cache' => [
        // Enable response caching
        'enabled' => env('MAILING_CACHE_ENABLED', true),

        // Cache driver (uses Laravel's default cache driver if null)
        'driver' => env('MAILING_CACHE_DRIVER', null),

        // Cache TTL (time to live) in seconds
        'ttl' => [
            'subscribers' => env('MAILING_CACHE_TTL_SUBSCRIBERS', 3600), // 1 hour
            'groups' => env('MAILING_CACHE_TTL_GROUPS', 3600), // 1 hour
            'campaigns' => env('MAILING_CACHE_TTL_CAMPAIGNS', 1800), // 30 minutes
            'analytics' => env('MAILING_CACHE_TTL_ANALYTICS', 300), // 5 minutes
        ],

        // Cache key prefix
        'prefix' => env('MAILING_CACHE_PREFIX', 'mailing'),

        // Auto-invalidate cache on updates
        'auto_invalidate' => env('MAILING_CACHE_AUTO_INVALIDATE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Group Settings
    |--------------------------------------------------------------------------
    |
    | Default group/list settings for new subscribers.
    |
    */
    'default_group' => [
        // Default group ID for new subscribers
        'id' => env('MAILING_DEFAULT_GROUP_ID', null),

        // Auto-assign subscribers to default group
        'auto_assign' => env('MAILING_DEFAULT_GROUP_AUTO_ASSIGN', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Campaign Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for campaign management.
    |
    */
    'campaign' => [
        // Default sender ID for campaigns
        'default_sender_id' => env('MAILING_CAMPAIGN_DEFAULT_SENDER_ID', null),

        // Track campaign analytics
        'track_analytics' => env('MAILING_CAMPAIGN_TRACK_ANALYTICS', true),

        // Sync analytics interval (in minutes)
        'analytics_sync_interval' => env('MAILING_CAMPAIGN_ANALYTICS_SYNC_INTERVAL', 30),

        // Enable test mode (prevents actual sending)
        'test_mode' => env('MAILING_CAMPAIGN_TEST_MODE', false),

        // Test email addresses for test mode
        'test_emails' => env('MAILING_CAMPAIGN_TEST_EMAILS')
            ? explode(',', env('MAILING_CAMPAIGN_TEST_EMAILS'))
            : [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for API request/response logging.
    |
    */
    'logging' => [
        // Enable API logging
        'enabled' => env('MAILING_LOGGING_ENABLED', false),

        // Log channel (uses Laravel's default log channel if null)
        'channel' => env('MAILING_LOGGING_CHANNEL', 'stack'),

        // Log level (debug, info, warning, error)
        'level' => env('MAILING_LOGGING_LEVEL', 'info'),

        // Log requests
        'log_requests' => env('MAILING_LOG_REQUESTS', true),

        // Log responses
        'log_responses' => env('MAILING_LOG_RESPONSES', true),

        // Log request bodies
        'log_request_body' => env('MAILING_LOG_REQUEST_BODY', false),

        // Log response bodies
        'log_response_body' => env('MAILING_LOG_RESPONSE_BODY', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configuration for API rate limiting.
    |
    */
    'rate_limit' => [
        // Enable rate limiting
        'enabled' => env('MAILING_RATE_LIMIT_ENABLED', true),

        // Maximum requests per minute
        'max_requests' => env('MAILING_RATE_LIMIT_MAX_REQUESTS', 60),

        // Decay time in minutes
        'decay_minutes' => env('MAILING_RATE_LIMIT_DECAY_MINUTES', 1),

        // Delay between requests (in milliseconds)
        'delay_between_requests' => env('MAILING_RATE_LIMIT_DELAY', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for email validation before syncing to Mailrelay.
    |
    */
    'validation' => [
        // Validate emails before syncing
        'enabled' => env('MAILING_VALIDATION_ENABLED', true),

        // Minimum validation score required (0-100)
        'min_score' => env('MAILING_VALIDATION_MIN_SCORE', 70),

        // Skip validation for specific domains
        'skip_domains' => env('MAILING_VALIDATION_SKIP_DOMAINS')
            ? explode(',', env('MAILING_VALIDATION_SKIP_DOMAINS'))
            : [],

        // Block disposable emails
        'block_disposable' => env('MAILING_VALIDATION_BLOCK_DISPOSABLE', true),

        // Block role-based emails (info@, admin@, etc.)
        'block_role_based' => env('MAILING_VALIDATION_BLOCK_ROLE_BASED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Handling
    |--------------------------------------------------------------------------
    |
    | Configuration for error handling and notifications.
    |
    */
    'error_handling' => [
        // Throw exceptions on API errors
        'throw_exceptions' => env('MAILING_THROW_EXCEPTIONS', true),

        // Notify on critical errors
        'notify_on_error' => env('MAILING_NOTIFY_ON_ERROR', false),

        // Notification channels
        'notification_channels' => ['mail', 'slack'],

        // Error notification recipients
        'notification_recipients' => env('MAILING_ERROR_NOTIFICATION_RECIPIENTS')
            ? explode(',', env('MAILING_ERROR_NOTIFICATION_RECIPIENTS'))
            : [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Testing & Development
    |--------------------------------------------------------------------------
    |
    | Configuration for testing and development environments.
    |
    */
    'testing' => [
        // Enable sandbox mode
        'sandbox_mode' => env('MAILING_SANDBOX_MODE', false),

        // Mock API responses
        'mock_api' => env('MAILING_MOCK_API', false),

        // Debug mode (verbose output)
        'debug' => env('MAILING_DEBUG', false),
    ],

];
