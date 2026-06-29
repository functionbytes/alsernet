<?php

return [
    'name' => 'HelpdeskSocial',

    'integrations' => [
        'meta' => [
            'enabled' => env('HELPDESK_SOCIAL_META_ENABLED', true),
            'app_id' => env('HELPDESK_SOCIAL_META_APP_ID'),
            'app_secret' => env('HELPDESK_SOCIAL_META_APP_SECRET'),
            'api_version' => env('HELPDESK_SOCIAL_META_API_VERSION', 'v25.0'),
            'verify_token' => env('HELPDESK_SOCIAL_META_VERIFY_TOKEN'),
            'webhook_path' => env('HELPDESK_SOCIAL_META_WEBHOOK_PATH', '/webhooks/meta'),
        ],
        'whatsapp' => [
            'enabled' => env('HELPDESK_SOCIAL_WHATSAPP_ENABLED', true),
            'api_version' => env('HELPDESK_SOCIAL_WHATSAPP_API_VERSION', 'v25.0'),
            'verify_token' => env('HELPDESK_SOCIAL_WHATSAPP_VERIFY_TOKEN'),
        ],
    ],

    'auto_reply' => [
        'enabled' => env('HELPDESK_SOCIAL_AUTO_REPLY_ENABLED', true),
        'max_rules_per_account' => 50,
        'default_reply_delay_seconds' => 5,
        'human_override_keyword' => 'humano',
    ],

    'intent_classification' => [
        'enabled' => env('HELPDESK_SOCIAL_INTENT_ENABLED', true),
        'provider' => env('HELPDESK_SOCIAL_INTENT_PROVIDER', 'rules'), // rules, openai, hybrid
        'openai_model' => env('HELPDESK_SOCIAL_OPENAI_MODEL', 'gpt-4o-mini'),
        'confidence_threshold' => 0.75,
        'cache_ttl_minutes' => 60,
    ],

    'comments' => [
        'enabled' => env('HELPDESK_SOCIAL_COMMENTS_ENABLED', true),
        'sync_interval_minutes' => 15,
        'max_comments_per_sync' => 100,
        'reply_to_comments_enabled' => true,
        'hide_spam_comments' => true,
    ],

    'queues' => [
        'webhooks' => 'helpdesk-social-webhooks',
        'processing' => 'helpdesk-social-processing',
        'analytics' => 'helpdesk-social-analytics',
    ],

    'analytics' => [
        'retention_days' => 365,
        'aggregation_interval' => 'daily',
    ],
];
