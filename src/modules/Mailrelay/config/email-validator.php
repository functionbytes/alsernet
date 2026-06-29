<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Primary Email Validation Provider
    |--------------------------------------------------------------------------
    |
    | This is the primary external API provider that will be used first
    | for email validation. Available options: zerobounce, neverbounce,
    | abstract, hunter
    |
    */

    'primary_provider' => env('EMAIL_VALIDATOR_PRIMARY', 'zerobounce'),

    /*
    |--------------------------------------------------------------------------
    | Fallback Email Validation Providers
    |--------------------------------------------------------------------------
    |
    | If the primary provider fails or is unavailable, these providers
    | will be tried in order. This ensures high availability for the
    | validation service.
    |
    */

    'fallback_providers' => [
        'neverbounce',
        'abstract',
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Validation Providers Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for each external email validation provider.
    | Each provider can be enabled/disabled independently and requires
    | its own API key.
    |
    */

    'providers' => [

        'zerobounce' => [
            'enabled' => env('ZEROBOUNCE_ENABLED', true),
            'api_key' => env('ZEROBOUNCE_API_KEY'),
            'api_url' => env('ZEROBOUNCE_API_URL', 'https://api.zerobounce.net/v2'),
            'timeout' => env('ZEROBOUNCE_TIMEOUT', 10),
            'rate_limit' => env('ZEROBOUNCE_RATE_LIMIT', 100), // requests per minute
        ],

        'neverbounce' => [
            'enabled' => env('NEVERBOUNCE_ENABLED', false),
            'api_key' => env('NEVERBOUNCE_API_KEY'),
            'api_url' => env('NEVERBOUNCE_API_URL', 'https://api.neverbounce.com/v4'),
            'timeout' => env('NEVERBOUNCE_TIMEOUT', 10),
            'rate_limit' => env('NEVERBOUNCE_RATE_LIMIT', 100),
        ],

        'abstract' => [
            'enabled' => env('ABSTRACT_ENABLED', false),
            'api_key' => env('ABSTRACT_API_KEY'),
            'api_url' => env('ABSTRACT_API_URL', 'https://emailvalidation.abstractapi.com/v1'),
            'timeout' => env('ABSTRACT_TIMEOUT', 5),
            'rate_limit' => env('ABSTRACT_RATE_LIMIT', 100),
        ],

        'hunter' => [
            'enabled' => env('HUNTER_ENABLED', false),
            'api_key' => env('HUNTER_API_KEY'),
            'api_url' => env('HUNTER_API_URL', 'https://api.hunter.io/v2'),
            'timeout' => env('HUNTER_TIMEOUT', 10),
            'rate_limit' => env('HUNTER_RATE_LIMIT', 100),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Email validation results will be cached to reduce API calls and
    | improve performance. The TTL is specified in seconds.
    |
    */

    'cache_ttl' => env('EMAIL_VALIDATION_CACHE_TTL', 86400), // 24 hours

    'cache_prefix' => 'email_validation:',

    /*
    |--------------------------------------------------------------------------
    | SMTP Validation Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for SMTP-level email validation. This performs a
    | connection test to the mail server to verify the mailbox exists.
    |
    */

    'smtp' => [
        'enabled' => env('SMTP_VALIDATION_ENABLED', true),
        'timeout' => env('SMTP_VALIDATION_TIMEOUT', 10),
        'max_retries' => env('SMTP_MAX_RETRIES', 3),
        'retry_delay' => env('SMTP_RETRY_DELAY', 1), // seconds
        'from_email' => env('SMTP_VALIDATION_FROM', 'verify@example.com'),
        'from_domain' => env('SMTP_VALIDATION_DOMAIN', 'example.com'),
    ],

    /*
    |--------------------------------------------------------------------------
    | DNS Validation Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for DNS-level validation. Checks for valid MX and
    | A records for the email domain.
    |
    */

    'dns' => [
        'enabled' => env('DNS_VALIDATION_ENABLED', true),
        'check_mx' => true,
        'check_a' => true,
        'cache_ttl' => env('DNS_CACHE_TTL', 86400), // 24 hours
    ],

    /*
    |--------------------------------------------------------------------------
    | Syntax Validation Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for syntax validation using RFC 5322 standards.
    |
    */

    'syntax' => [
        'enabled' => env('SYNTAX_VALIDATION_ENABLED', true),
        'strict_mode' => env('SYNTAX_VALIDATION_STRICT', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Python ML Validator Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for the Python-based machine learning validator
    | that detects suspicious patterns and disposable email addresses.
    |
    */

    'python_ml' => [
        'enabled' => env('PYTHON_ML_ENABLED', false),
        'python_executable' => env('PYTHON_EXECUTABLE', '/usr/bin/python3'),
        'script_path' => env('PYTHON_ML_SCRIPT', base_path('python/ml_validator/validator.py')),
        'timeout' => env('PYTHON_ML_TIMEOUT', 30),
        'cache_ttl' => env('PYTHON_ML_CACHE_TTL', 86400),
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Thresholds
    |--------------------------------------------------------------------------
    |
    | Score thresholds for determining email validity. Scores range from
    | 0-100, with higher scores indicating higher confidence.
    |
    */

    'thresholds' => [
        'valid' => env('VALIDATION_THRESHOLD_VALID', 80),
        'risky' => env('VALIDATION_THRESHOLD_RISKY', 50),
        'invalid' => env('VALIDATION_THRESHOLD_INVALID', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Disposable Email Domains
    |--------------------------------------------------------------------------
    |
    | Enable checking against a list of known disposable email providers.
    | The list is updated periodically from external sources.
    |
    */

    'disposable_domains' => [
        'enabled' => env('CHECK_DISPOSABLE_DOMAINS', true),
        'cache_ttl' => env('DISPOSABLE_DOMAINS_CACHE_TTL', 604800), // 7 days
        'update_url' => env('DISPOSABLE_DOMAINS_URL', 'https://raw.githubusercontent.com/disposable-email-domains/disposable-email-domains/master/disposable_email_blocklist.conf'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Free Email Providers
    |--------------------------------------------------------------------------
    |
    | List of known free email providers. Useful for filtering or
    | flagging free email addresses if needed.
    |
    */

    'free_email_providers' => [
        'gmail.com',
        'yahoo.com',
        'hotmail.com',
        'outlook.com',
        'aol.com',
        'icloud.com',
        'mail.com',
        'protonmail.com',
        'zoho.com',
        'gmx.com',
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    |
    | Default validation rules that will be applied to all email
    | validation requests unless overridden.
    |
    */

    'default_validators' => [
        'syntax',
        'dns',
        'smtp',
        'external',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limiting configuration to prevent abuse and stay within
    | API provider limits.
    |
    */

    'rate_limiting' => [
        'enabled' => env('VALIDATION_RATE_LIMITING_ENABLED', true),
        'max_attempts' => env('VALIDATION_MAX_ATTEMPTS', 60),
        'decay_minutes' => env('VALIDATION_DECAY_MINUTES', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Bulk Validation Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for bulk email validation operations.
    |
    */

    'bulk' => [
        'chunk_size' => env('BULK_VALIDATION_CHUNK_SIZE', 100),
        'max_emails_per_request' => env('BULK_MAX_EMAILS', 1000),
        'queue' => env('BULK_VALIDATION_QUEUE', 'high'),
        'timeout' => env('BULK_VALIDATION_TIMEOUT', 3600), // 1 hour
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure what validation events should be logged.
    |
    */

    'logging' => [
        'enabled' => env('VALIDATION_LOGGING_ENABLED', true),
        'log_channel' => env('VALIDATION_LOG_CHANNEL', 'stack'),
        'log_failed_only' => env('VALIDATION_LOG_FAILED_ONLY', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for retry logic when validation fails.
    |
    */

    'retry' => [
        'enabled' => env('VALIDATION_RETRY_ENABLED', true),
        'max_attempts' => env('VALIDATION_RETRY_MAX_ATTEMPTS', 3),
        'delay' => env('VALIDATION_RETRY_DELAY', 5), // seconds
        'multiplier' => env('VALIDATION_RETRY_MULTIPLIER', 2), // exponential backoff
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for sending validation results via webhooks.
    |
    */

    'webhooks' => [
        'enabled' => env('VALIDATION_WEBHOOKS_ENABLED', false),
        'url' => env('VALIDATION_WEBHOOK_URL'),
        'secret' => env('VALIDATION_WEBHOOK_SECRET'),
        'events' => [
            'validation.completed',
            'validation.failed',
            'bulk.completed',
        ],
    ],

];
