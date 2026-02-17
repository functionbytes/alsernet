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
    'api_url' => env('MAILRELAY_URL', 'https://inoqualab.mailrelay.com/api/v1'),

    /*
    |--------------------------------------------------------------------------
    | API Key
    |--------------------------------------------------------------------------
    |
    | Your Mailrelay API key for authentication.
    | This key is required for all API requests.
    |
    */
    'api_key' => env('MAILRELAY_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum time (in seconds) to wait for an API response.
    | Default: 30 seconds
    |
    */
    'timeout' => env('MAILRELAY_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Connection Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum time (in seconds) to wait for connection establishment.
    | Default: 10 seconds
    |
    */
    'connect_timeout' => env('MAILRELAY_CONNECT_TIMEOUT', 10),

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for automatic retry on failed requests.
    |
    */
    'retry' => [
        'max_attempts' => env('MAILRELAY_RETRY_MAX_ATTEMPTS', 3),
        'delay' => env('MAILRELAY_RETRY_DELAY', 100), // milliseconds
        'multiplier' => env('MAILRELAY_RETRY_MULTIPLIER', 2),
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
        'auto_sync' => env('MAILRELAY_AUTO_SYNC', true),

        // Sync interval for batch operations (in minutes)
        'batch_interval' => env('MAILRELAY_SYNC_BATCH_INTERVAL', 60),

        // Maximum subscribers to sync in one batch
        'batch_size' => env('MAILRELAY_SYNC_BATCH_SIZE', 100),

        // Enable sync queue for background processing
        'use_queue' => env('MAILRELAY_SYNC_USE_QUEUE', true),

        // Queue name for sync jobs
        'queue_name' => env('MAILRELAY_SYNC_QUEUE_NAME', 'mailrelay'),

        // Sync retry configuration
        'retry_failed' => env('MAILRELAY_SYNC_RETRY_FAILED', true),
        'max_retries' => env('MAILRELAY_SYNC_MAX_RETRIES', 3),

        // Track sync history
        'track_history' => env('MAILRELAY_SYNC_TRACK_HISTORY', true),

        // Sync subscriber metadata
        'sync_metadata' => env('MAILRELAY_SYNC_METADATA', true),

        // Sync subscriber groups
        'sync_groups' => env('MAILRELAY_SYNC_GROUPS', true),
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
        'enabled' => env('MAILRELAY_WEBHOOK_ENABLED', true),

        // Webhook URL path (relative to APP_URL)
        'path' => env('MAILRELAY_WEBHOOK_PATH', '/api/webhooks/mailrelay'),

        // Webhook secret for signature verification
        'secret' => env('MAILRELAY_WEBHOOK_SECRET'),

        // Verify webhook signatures
        'verify_signature' => env('MAILRELAY_WEBHOOK_VERIFY_SIGNATURE', true),

        // Events to process (empty array means all events)
        'events' => [
            'email.opened',
            'email.clicked',
            'subscriber.unsubscribed',
            'email.bounced',
            'email.complained',
        ],

        // Store webhook logs
        'log_events' => env('MAILRELAY_WEBHOOK_LOG_EVENTS', true),

        // Queue webhook processing
        'use_queue' => env('MAILRELAY_WEBHOOK_USE_QUEUE', true),
        'queue_name' => env('MAILRELAY_WEBHOOK_QUEUE_NAME', 'webhooks'),

        // Webhook timeout for processing (in seconds)
        'timeout' => env('MAILRELAY_WEBHOOK_TIMEOUT', 30),

        // Max payload size (in bytes)
        'max_payload_size' => env('MAILRELAY_WEBHOOK_MAX_PAYLOAD_SIZE', 1048576), // 1MB

        // IP whitelist for webhook requests (optional)
        'ip_whitelist' => env('MAILRELAY_WEBHOOK_IP_WHITELIST')
            ? explode(',', env('MAILRELAY_WEBHOOK_IP_WHITELIST'))
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
        'enabled' => env('MAILRELAY_CACHE_ENABLED', true),

        // Cache driver (uses Laravel's default cache driver if null)
        'driver' => env('MAILRELAY_CACHE_DRIVER', null),

        // Cache TTL (time to live) in seconds
        'ttl' => [
            'subscribers' => env('MAILRELAY_CACHE_TTL_SUBSCRIBERS', 3600), // 1 hour
            'groups' => env('MAILRELAY_CACHE_TTL_GROUPS', 3600), // 1 hour
            'campaigns' => env('MAILRELAY_CACHE_TTL_CAMPAIGNS', 1800), // 30 minutes
            'analytics' => env('MAILRELAY_CACHE_TTL_ANALYTICS', 300), // 5 minutes
        ],

        // Cache key prefix
        'prefix' => env('MAILRELAY_CACHE_PREFIX', 'mailrelay'),

        // Auto-invalidate cache on updates
        'auto_invalidate' => env('MAILRELAY_CACHE_AUTO_INVALIDATE', true),
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
        'id' => env('MAILRELAY_DEFAULT_GROUP_ID', null),

        // Auto-assign subscribers to default group
        'auto_assign' => env('MAILRELAY_DEFAULT_GROUP_AUTO_ASSIGN', true),
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
        'default_sender_id' => env('MAILRELAY_CAMPAIGN_DEFAULT_SENDER_ID', null),

        // Track campaign analytics
        'track_analytics' => env('MAILRELAY_CAMPAIGN_TRACK_ANALYTICS', true),

        // Sync analytics interval (in minutes)
        'analytics_sync_interval' => env('MAILRELAY_CAMPAIGN_ANALYTICS_SYNC_INTERVAL', 30),

        // Enable test mode (prevents actual sending)
        'test_mode' => env('MAILRELAY_CAMPAIGN_TEST_MODE', false),

        // Test email addresses for test mode
        'test_emails' => env('MAILRELAY_CAMPAIGN_TEST_EMAILS')
            ? explode(',', env('MAILRELAY_CAMPAIGN_TEST_EMAILS'))
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
        'enabled' => env('MAILRELAY_LOGGING_ENABLED', false),

        // Log channel (uses Laravel's default log channel if null)
        'channel' => env('MAILRELAY_LOGGING_CHANNEL', 'stack'),

        // Log level (debug, info, warning, error)
        'level' => env('MAILRELAY_LOGGING_LEVEL', 'info'),

        // Log requests
        'log_requests' => env('MAILRELAY_LOG_REQUESTS', true),

        // Log responses
        'log_responses' => env('MAILRELAY_LOG_RESPONSES', true),

        // Log request bodies
        'log_request_body' => env('MAILRELAY_LOG_REQUEST_BODY', false),

        // Log response bodies
        'log_response_body' => env('MAILRELAY_LOG_RESPONSE_BODY', false),
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
        'enabled' => env('MAILRELAY_RATE_LIMIT_ENABLED', true),

        // Maximum requests per minute
        'max_requests' => env('MAILRELAY_RATE_LIMIT_MAX_REQUESTS', 60),

        // Decay time in minutes
        'decay_minutes' => env('MAILRELAY_RATE_LIMIT_DECAY_MINUTES', 1),

        // Delay between requests (in milliseconds)
        'delay_between_requests' => env('MAILRELAY_RATE_LIMIT_DELAY', 50),
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
        'enabled' => env('MAILRELAY_VALIDATION_ENABLED', true),

        // Minimum validation score required (0-100)
        'min_score' => env('MAILRELAY_VALIDATION_MIN_SCORE', 70),

        // Skip validation for specific domains
        'skip_domains' => env('MAILRELAY_VALIDATION_SKIP_DOMAINS')
            ? explode(',', env('MAILRELAY_VALIDATION_SKIP_DOMAINS'))
            : [],

        // Block disposable emails
        'block_disposable' => env('MAILRELAY_VALIDATION_BLOCK_DISPOSABLE', true),

        // Block role-based emails (info@, settings@, etc.)
        'block_role_based' => env('MAILRELAY_VALIDATION_BLOCK_ROLE_BASED', false),
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
        'throw_exceptions' => env('MAILRELAY_THROW_EXCEPTIONS', true),

        // Notify on critical errors
        'notify_on_error' => env('MAILRELAY_NOTIFY_ON_ERROR', false),

        // Notification channels
        'notification_channels' => ['mail', 'slack'],

        // Error notification recipients
        'notification_recipients' => env('MAILRELAY_ERROR_NOTIFICATION_RECIPIENTS')
            ? explode(',', env('MAILRELAY_ERROR_NOTIFICATION_RECIPIENTS'))
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
        'sandbox_mode' => env('MAILRELAY_SANDBOX_MODE', false),

        // Mock API responses
        'mock_api' => env('MAILRELAY_MOCK_API', false),

        // Debug mode (verbose output)
        'debug' => env('MAILRELAY_DEBUG', false),
    ],

];
