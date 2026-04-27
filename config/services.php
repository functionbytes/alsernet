<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY', ''),
    ],

    'google' => [
        'pagespeed_key' => env('GOOGLE_PAGESPEED_KEY', ''),
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URL', '/tienda/auth/google/callback'),
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URL', '/tienda/auth/facebook/callback'),
    ],

    'fcm' => [
        'project_id' => env('FCM_PROJECT_ID', ''),
        'credentials_path' => env('FCM_CREDENTIALS_PATH', storage_path('app/firebase-credentials.json')),
    ],

    'deepl' => [
        'key' => env('DEEPL_API_KEY', ''),
        'url' => env('DEEPL_API_URL', 'https://api-free.deepl.com'),
    ],

    'mailrelay' => [
        'api_key' => env('MAILRELAY_API_KEY'),
        'host' => env('MAILRELAY_HOST'),
        'newsletter_group_id' => env('MAILRELAY_NEWSLETTER_GROUP_ID'),
        'webhook_token' => env('MAILRELAY_WEBHOOK_TOKEN', ''),
    ],

    'sentry' => [
        'dsn' => env('SENTRY_DSN'),
        'environment' => env('SENTRY_ENVIRONMENT'),
        'traces_sample_rate' => env('SENTRY_TRACES_SAMPLE_RATE', 0.1),
    ],

];
