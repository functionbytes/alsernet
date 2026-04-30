<?php

/**
 * ============================================================
 * CHANNELS CONFIGURATION
 * ============================================================
 *
 * Centralized configuration for all messaging channels:
 * - Facebook Messenger
 * - Instagram Direct Messages
 * - WhatsApp Business
 *
 * All values are read from .env file for secure management.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Webhook Base URL
    |--------------------------------------------------------------------------
    |
    | Base URL for webhook endpoints (used by all channels)
    | Required for Facebook, Instagram, WhatsApp configurations
    |
    */
    'webhook_url' => env('WEBHOOK_URL', 'https://channels.functionbytes.com'),

    /*
    |--------------------------------------------------------------------------
    | Facebook Messenger Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Facebook Messenger channel integration
    | Requires: Facebook Business Account + App ID + App Secret
    |
    */
    'facebook' => [
        'app_id' => env('FACEBOOK_APP_ID'),
        'app_secret' => env('FACEBOOK_APP_SECRET'),
        'api_version' => env('FACEBOOK_API_VERSION', 'v24.0'),
        'api_url' => 'https://graph.instagram.com/',
        'verify_token' => env('FACEBOOK_VERIFY_TOKEN', 'channels_fb_webhook_verify_secret_2025'),
        'enable_human_agent' => env('FACEBOOK_ENABLE_HUMAN_AGENT', false),

    ],

    /*
    |--------------------------------------------------------------------------
    | Instagram Direct Messages Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Instagram DM channel integration
    | Requires: Instagram Business Account + App ID + App Secret
    |
    */
    'instagram' => [
        'app_id' => env('INSTAGRAM_APP_ID'),
        'app_secret' => env('INSTAGRAM_APP_SECRET'),
        'api_version' => env('INSTAGRAM_API_VERSION', 'v24.0'),
        'api_url' => 'https://graph.instagram.com/',
        'verify_token' => env('INSTAGRAM_VERIFY_TOKEN', 'channels_ig_webhook_verify_secret_2025'),
        'enable_human_agent' => env('INSTAGRAM_ENABLE_HUMAN_AGENT', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Business Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for WhatsApp Business API (Cloud API by Meta)
    | Requires: WhatsApp Business Account + Phone Number ID + Access Token
    |
    | Phone Number ID is the unique identifier for your WhatsApp phone number
    | Business Account ID is the Meta Business Account ID
    |
    */
    'whatsapp' => [
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),
        'business_phone_number' => env('WHATSAPP_BUSINESS_PHONE_NUMBER', '+34123456789'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'api_version' => env('WHATSAPP_API_VERSION', 'v22.0'),
        'api_url' => 'https://graph.facebook.com/',
        'verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN', 'channels_whatsapp_webhook_verify_secret_2025'),

        // Download media from WhatsApp API
        'media_download' => [
            'timeout' => 30,
            'max_size' => 100 * 1024 * 1024, // 100 MB
        ],

        // Rate limiting for message sending
        'rate_limiting' => [
            'enabled' => true,
            'requests_per_minute' => 80, // Conservative limit for tier 1
        ],

        // Retry configuration for failed sends
        'retry' => [
            'max_attempts' => 3,
            'backoff' => [10, 30, 60], // seconds
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Shared Configuration
    |--------------------------------------------------------------------------
    |
    | Settings applied to all channels
    |
    */
    'shared' => [
        // Enable/disable all webhooks globally
        'webhooks_enabled' => true,

        // Log all webhook events (for debugging)
        'log_webhooks' => env('APP_DEBUG', false),

        // Queue for processing webhook jobs
        'webhook_queue' => 'webhooks',

        // API request timeout (seconds)
        'api_timeout' => 30,

        // Default locale for messages
        'default_locale' => env('APP_LOCALE', 'es'),

        // Enable message encryption at rest
        'encrypt_messages' => true,
    ],
];
