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

    /*
    |--------------------------------------------------------------------------
    | Acelle Mail Integration Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for Acelle Mail system features
    | These settings are migrated from Acelle's original configuration
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Feature Toggles
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific features globally
    | Used by policies to control feature access
    |
    */
    'features' => [
        'automations' => [
            'enabled' => env('MAILING_AUTOMATIONS_ENABLED', true),
        ],
        'segments' => [
            'enabled' => env('MAILING_SEGMENTS_ENABLED', true),
        ],
        'forms' => [
            'enabled' => env('MAILING_FORMS_ENABLED', true),
        ],
        'bounce_handler' => [
            'enabled' => env('MAILING_BOUNCE_HANDLER_ENABLED', false),
        ],
        'feedback_loop' => [
            'enabled' => env('MAILING_FEEDBACK_LOOP_ENABLED', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resource Limits
    |--------------------------------------------------------------------------
    |
    | Global limits for resource creation (null = no limit)
    | These limits apply on top of quota checks
    |
    */
    'limits' => [
        'campaigns' => env('MAILING_CAMPAIGN_LIMIT', null),
        'lists' => env('MAILING_LIST_LIMIT', null),
        'automations' => env('MAILING_AUTOMATION_LIMIT', null),
        'sending_servers' => env('MAILING_SENDING_SERVER_LIMIT', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Quota Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for resource quotas
    |
    */
    'quotas' => [
        // Value representing unlimited quota
        'unlimited_value' => -1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Tracking Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for tracking opens, clicks, and other email interactions
    |
    */
    'tracking' => [
        // Enable/disable open tracking
        'track_opens' => env('MAILING_TRACK_OPENS', true),

        // Enable/disable click tracking
        'track_clicks' => env('MAILING_TRACK_CLICKS', true),

        // Enable/disable unsubscribe tracking
        'track_unsubscribes' => env('MAILING_TRACK_UNSUBSCRIBES', true),

        // Tracking domain (for links and images)
        'tracking_domain' => env('MAILING_TRACKING_DOMAIN', null),

        // Use HTTPS for tracking URLs
        'tracking_https' => env('MAILING_TRACKING_HTTPS', true),

        // Click tracking queue
        'click_tracking_queue' => env('MAILING_CLICK_TRACKING_QUEUE', 'tracking'),

        // Open tracking queue
        'open_tracking_queue' => env('MAILING_OPEN_TRACKING_QUEUE', 'tracking'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Subscriber Import Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for importing subscribers from CSV/Excel files
    |
    */
    'import' => [
        // Maximum file size for imports (in MB)
        'max_file_size' => env('MAILING_IMPORT_MAX_FILE_SIZE', 50),

        // Batch size for processing imports
        'batch_size' => env('MAILING_IMPORT_BATCH_SIZE', 1000),

        // Queue for import jobs
        'queue' => env('MAILING_IMPORT_QUEUE', 'imports'),

        // Allowed file types
        'allowed_types' => ['csv', 'txt', 'xlsx', 'xls'],

        // CSV delimiter
        'csv_delimiter' => env('MAILING_IMPORT_CSV_DELIMITER', ','),

        // CSV enclosure
        'csv_enclosure' => env('MAILING_IMPORT_CSV_ENCLOSURE', '"'),

        // Skip duplicate emails
        'skip_duplicates' => env('MAILING_IMPORT_SKIP_DUPLICATES', true),

        // Validate email format
        'validate_emails' => env('MAILING_IMPORT_VALIDATE_EMAILS', true),

        // Maximum import execution time (seconds)
        'max_execution_time' => env('MAILING_IMPORT_MAX_EXECUTION_TIME', 7200),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sending Server Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for email sending servers (SMTP, SendGrid, AWS SES, etc.)
    |
    */
    'sending_servers' => [
        // Default sending server type
        'default_type' => env('MAILING_SENDING_SERVER_DEFAULT_TYPE', 'smtp'),

        // Rotation method: 'random', 'round-robin', 'percentage'
        'rotation_method' => env('MAILING_SENDING_SERVER_ROTATION', 'round-robin'),

        // Enable server health monitoring
        'health_check_enabled' => env('MAILING_SENDING_SERVER_HEALTH_CHECK', true),

        // Health check interval (minutes)
        'health_check_interval' => env('MAILING_SENDING_SERVER_HEALTH_CHECK_INTERVAL', 15),

        // Auto-disable server on consecutive failures
        'auto_disable_threshold' => env('MAILING_SENDING_SERVER_AUTO_DISABLE_THRESHOLD', 5),

        // Sending quota per server (hourly)
        'quota_per_hour' => env('MAILING_SENDING_SERVER_QUOTA_HOUR', 10000),

        // Sending quota per server (daily)
        'quota_per_day' => env('MAILING_SENDING_SERVER_QUOTA_DAY', 100000),

        // Connection timeout (seconds)
        'connection_timeout' => env('MAILING_SENDING_SERVER_CONNECTION_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Bounce Handler Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for handling bounced emails
    |
    */
    'bounce_handler' => [
        // Enable bounce handler
        'enabled' => env('MAILING_BOUNCE_HANDLER_ENABLED', false),

        // Bounce handler type: 'imap', 'pop3', 'webhook'
        'type' => env('MAILING_BOUNCE_HANDLER_TYPE', 'imap'),

        // IMAP/POP3 server settings
        'host' => env('MAILING_BOUNCE_HANDLER_HOST'),
        'port' => env('MAILING_BOUNCE_HANDLER_PORT', 993),
        'username' => env('MAILING_BOUNCE_HANDLER_USERNAME'),
        'password' => env('MAILING_BOUNCE_HANDLER_PASSWORD'),
        'encryption' => env('MAILING_BOUNCE_HANDLER_ENCRYPTION', 'ssl'),

        // Bounce processing queue
        'queue' => env('MAILING_BOUNCE_HANDLER_QUEUE', 'bounces'),

        // Bounce processing interval (minutes)
        'processing_interval' => env('MAILING_BOUNCE_HANDLER_INTERVAL', 5),

        // Maximum bounces to process per cycle
        'max_process_per_cycle' => env('MAILING_BOUNCE_HANDLER_MAX_PROCESS', 500),

        // Hard bounce action: 'unsubscribe', 'mark_inactive', 'delete'
        'hard_bounce_action' => env('MAILING_BOUNCE_HARD_ACTION', 'unsubscribe'),

        // Soft bounce threshold before converting to hard bounce
        'soft_bounce_threshold' => env('MAILING_BOUNCE_SOFT_THRESHOLD', 5),

        // Delete processed bounce emails from server
        'delete_after_processing' => env('MAILING_BOUNCE_DELETE_AFTER_PROCESSING', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Feedback Loop Handler Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for handling spam complaints (FBL)
    |
    */
    'feedback_loop' => [
        // Enable feedback loop handler
        'enabled' => env('MAILING_FEEDBACK_LOOP_ENABLED', false),

        // FBL handler type: 'imap', 'pop3', 'webhook'
        'type' => env('MAILING_FEEDBACK_LOOP_TYPE', 'imap'),

        // IMAP/POP3 server settings
        'host' => env('MAILING_FEEDBACK_LOOP_HOST'),
        'port' => env('MAILING_FEEDBACK_LOOP_PORT', 993),
        'username' => env('MAILING_FEEDBACK_LOOP_USERNAME'),
        'password' => env('MAILING_FEEDBACK_LOOP_PASSWORD'),
        'encryption' => env('MAILING_FEEDBACK_LOOP_ENCRYPTION', 'ssl'),

        // FBL processing queue
        'queue' => env('MAILING_FEEDBACK_LOOP_QUEUE', 'feedback'),

        // FBL processing interval (minutes)
        'processing_interval' => env('MAILING_FEEDBACK_LOOP_INTERVAL', 10),

        // Action on complaint: 'unsubscribe', 'blacklist'
        'complaint_action' => env('MAILING_FEEDBACK_LOOP_ACTION', 'unsubscribe'),

        // Delete processed FBL emails from server
        'delete_after_processing' => env('MAILING_FEEDBACK_LOOP_DELETE_AFTER_PROCESSING', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Template Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for email templates
    |
    */
    'templates' => [
        // Enable template caching
        'cache_enabled' => env('MAILING_TEMPLATE_CACHE_ENABLED', true),

        // Template cache TTL (minutes)
        'cache_ttl' => env('MAILING_TEMPLATE_CACHE_TTL', 60),

        // Default template language
        'default_language' => env('MAILING_TEMPLATE_DEFAULT_LANGUAGE', 'en'),

        // Allow custom CSS in templates
        'allow_custom_css' => env('MAILING_TEMPLATE_ALLOW_CUSTOM_CSS', true),

        // Allow custom JavaScript in templates
        'allow_custom_js' => env('MAILING_TEMPLATE_ALLOW_CUSTOM_JS', false),

        // Maximum template size (KB)
        'max_size' => env('MAILING_TEMPLATE_MAX_SIZE', 500),

        // Template storage path
        'storage_path' => env('MAILING_TEMPLATE_STORAGE_PATH', 'mailing/templates'),

        // Enable template versioning
        'versioning_enabled' => env('MAILING_TEMPLATE_VERSIONING', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Automation Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for automated email workflows
    |
    */
    'automation' => [
        // Enable automation
        'enabled' => env('MAILING_AUTOMATION_ENABLED', true),

        // Automation processing queue
        'queue' => env('MAILING_AUTOMATION_QUEUE', 'automation'),

        // Automation processing interval (minutes)
        'processing_interval' => env('MAILING_AUTOMATION_INTERVAL', 1),

        // Maximum automations to process per cycle
        'max_process_per_cycle' => env('MAILING_AUTOMATION_MAX_PROCESS', 100),

        // Enable automation triggers
        'triggers_enabled' => env('MAILING_AUTOMATION_TRIGGERS_ENABLED', true),

        // Maximum automation depth (nested automations)
        'max_depth' => env('MAILING_AUTOMATION_MAX_DEPTH', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Segmentation Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for subscriber segmentation
    |
    */
    'segmentation' => [
        // Enable segmentation
        'enabled' => env('MAILING_SEGMENTATION_ENABLED', true),

        // Segment cache TTL (minutes)
        'cache_ttl' => env('MAILING_SEGMENTATION_CACHE_TTL', 30),

        // Maximum segment size for real-time calculation
        'realtime_threshold' => env('MAILING_SEGMENTATION_REALTIME_THRESHOLD', 10000),

        // Segment recalculation queue
        'queue' => env('MAILING_SEGMENTATION_QUEUE', 'segments'),

        // Maximum conditions per segment
        'max_conditions' => env('MAILING_SEGMENTATION_MAX_CONDITIONS', 20),
    ],

    /*
    |--------------------------------------------------------------------------
    | List Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for mailing lists
    |
    */
    'lists' => [
        // Enable double opt-in by default
        'double_optin_default' => env('MAILING_LIST_DOUBLE_OPTIN_DEFAULT', true),

        // Send welcome email by default
        'send_welcome_email_default' => env('MAILING_LIST_SEND_WELCOME_EMAIL_DEFAULT', false),

        // Send unsubscribe notification
        'send_unsubscribe_notification' => env('MAILING_LIST_SEND_UNSUBSCRIBE_NOTIFICATION', true),

        // Maximum lists per user
        'max_lists_per_user' => env('MAILING_LIST_MAX_PER_USER', 100),

        // Enable list verification
        'verification_enabled' => env('MAILING_LIST_VERIFICATION_ENABLED', true),

        // List cleanup enabled (remove inactive subscribers)
        'cleanup_enabled' => env('MAILING_LIST_CLEANUP_ENABLED', false),

        // Cleanup threshold (days of inactivity)
        'cleanup_threshold_days' => env('MAILING_LIST_CLEANUP_THRESHOLD_DAYS', 365),
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Verification Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for email verification services
    |
    */
    'verification' => [
        // Enable email verification (beyond basic validation)
        'advanced_enabled' => env('MAILING_VERIFICATION_ADVANCED_ENABLED', false),

        // Verification service: 'internal', 'zerobounce', 'neverbounce'
        'service' => env('MAILING_VERIFICATION_SERVICE', 'internal'),

        // API credentials for external verification services
        'api_key' => env('MAILING_VERIFICATION_API_KEY'),
        'api_secret' => env('MAILING_VERIFICATION_API_SECRET'),

        // Verification queue
        'queue' => env('MAILING_VERIFICATION_QUEUE', 'verification'),

        // Auto-verify on import
        'auto_verify_on_import' => env('MAILING_VERIFICATION_AUTO_IMPORT', false),

        // Remove invalid emails
        'remove_invalid' => env('MAILING_VERIFICATION_REMOVE_INVALID', true),

        // Verification cache TTL (days)
        'cache_ttl_days' => env('MAILING_VERIFICATION_CACHE_TTL_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Deliverability Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for improving email deliverability
    |
    */
    'deliverability' => [
        // Enable DKIM signing
        'dkim_enabled' => env('MAILING_DKIM_ENABLED', false),

        // DKIM selector
        'dkim_selector' => env('MAILING_DKIM_SELECTOR', 'default'),

        // DKIM private key path
        'dkim_private_key' => env('MAILING_DKIM_PRIVATE_KEY_PATH'),

        // DKIM domain
        'dkim_domain' => env('MAILING_DKIM_DOMAIN'),

        // Enable SPF checking
        'spf_enabled' => env('MAILING_SPF_ENABLED', true),

        // Enable DMARC
        'dmarc_enabled' => env('MAILING_DMARC_ENABLED', false),

        // Custom headers
        'custom_headers' => [
            'List-Unsubscribe' => env('MAILING_HEADER_LIST_UNSUBSCRIBE', true),
            'Precedence' => env('MAILING_HEADER_PRECEDENCE', 'bulk'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Reporting Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for reports and analytics
    |
    */
    'reporting' => [
        // Enable reporting
        'enabled' => env('MAILING_REPORTING_ENABLED', true),

        // Report generation queue
        'queue' => env('MAILING_REPORTING_QUEUE', 'reports'),

        // Report cache TTL (minutes)
        'cache_ttl' => env('MAILING_REPORTING_CACHE_TTL', 60),

        // Enable real-time statistics
        'realtime_stats' => env('MAILING_REPORTING_REALTIME_STATS', true),

        // Statistics aggregation interval (minutes)
        'aggregation_interval' => env('MAILING_REPORTING_AGGREGATION_INTERVAL', 5),

        // Export formats: 'pdf', 'csv', 'xlsx'
        'export_formats' => ['pdf', 'csv', 'xlsx'],

        // Maximum report history (days)
        'max_history_days' => env('MAILING_REPORTING_MAX_HISTORY_DAYS', 365),
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for file storage (attachments, exports, etc.)
    |
    */
    'storage' => [
        // Default storage disk for mailing files
        'disk' => env('MAILING_STORAGE_DISK', 'local'),

        // Attachments storage path
        'attachments_path' => env('MAILING_STORAGE_ATTACHMENTS_PATH', 'mailing/attachments'),

        // Maximum attachment size (MB)
        'max_attachment_size' => env('MAILING_STORAGE_MAX_ATTACHMENT_SIZE', 10),

        // Allowed attachment types
        'allowed_attachment_types' => [
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
            'jpg', 'jpeg', 'png', 'gif', 'zip', 'txt', 'csv',
        ],

        // Export files retention (days)
        'export_retention_days' => env('MAILING_STORAGE_EXPORT_RETENTION_DAYS', 7),

        // Temporary files cleanup interval (hours)
        'temp_cleanup_interval' => env('MAILING_STORAGE_TEMP_CLEANUP_INTERVAL', 24),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for security features
    |
    */
    'security' => [
        // Enable CAPTCHA for subscription forms
        'captcha_enabled' => env('MAILING_SECURITY_CAPTCHA_ENABLED', false),

        // CAPTCHA type: 'recaptcha', 'hcaptcha'
        'captcha_type' => env('MAILING_SECURITY_CAPTCHA_TYPE', 'recaptcha'),

        // reCAPTCHA site key
        'recaptcha_site_key' => env('MAILING_SECURITY_RECAPTCHA_SITE_KEY'),

        // reCAPTCHA secret key
        'recaptcha_secret_key' => env('MAILING_SECURITY_RECAPTCHA_SECRET_KEY'),

        // Enable honeypot for forms
        'honeypot_enabled' => env('MAILING_SECURITY_HONEYPOT_ENABLED', true),

        // IP-based rate limiting for subscriptions
        'ip_rate_limiting_enabled' => env('MAILING_SECURITY_IP_RATE_LIMITING', true),

        // Maximum subscriptions per IP per hour
        'max_subscriptions_per_ip' => env('MAILING_SECURITY_MAX_SUBSCRIPTIONS_PER_IP', 10),

        // Blacklist checking enabled
        'blacklist_check_enabled' => env('MAILING_SECURITY_BLACKLIST_CHECK', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Localization Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for multi-language support
    |
    */
    'localization' => [
        // Available languages
        'available_languages' => ['en', 'es', 'fr', 'de', 'it', 'pt', 'ru'],

        // Default language
        'default_language' => env('MAILING_LOCALIZATION_DEFAULT_LANGUAGE', 'es'),

        // Enable timezone support
        'timezone_enabled' => env('MAILING_LOCALIZATION_TIMEZONE_ENABLED', true),

        // Default timezone
        'default_timezone' => env('MAILING_LOCALIZATION_DEFAULT_TIMEZONE', 'Europe/Madrid'),

        // Enable RTL languages
        'rtl_enabled' => env('MAILING_LOCALIZATION_RTL_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for performance optimization
    |
    */
    'performance' => [
        // Enable query caching
        'query_cache_enabled' => env('MAILING_PERFORMANCE_QUERY_CACHE', true),

        // Query cache TTL (minutes)
        'query_cache_ttl' => env('MAILING_PERFORMANCE_QUERY_CACHE_TTL', 10),

        // Enable lazy loading
        'lazy_loading_enabled' => env('MAILING_PERFORMANCE_LAZY_LOADING', true),

        // Chunk size for large queries
        'chunk_size' => env('MAILING_PERFORMANCE_CHUNK_SIZE', 1000),

        // Enable database connection pooling
        'connection_pooling' => env('MAILING_PERFORMANCE_CONNECTION_POOLING', true),

        // Maximum database connections
        'max_connections' => env('MAILING_PERFORMANCE_MAX_CONNECTIONS', 100),
    ],

];
