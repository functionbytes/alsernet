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
        ],
        // El chat de WhatsApp (mensajes entrantes) lo gestiona el módulo Helpdesk
        // core vía `helpdesk.integrations.whatsapp`. Este módulo solo cubre
        // comentarios y menciones de Facebook/Instagram por la Graph API.
    ],

    'auto_reply' => [
        'enabled' => env('HELPDESK_SOCIAL_AUTO_REPLY_ENABLED', true),
    ],

    'intent_classification' => [
        'provider' => env('HELPDESK_SOCIAL_INTENT_PROVIDER', 'rules'), // rules, openai, hybrid
        'openai_api_key' => env('HELPDESK_SOCIAL_OPENAI_API_KEY'),
        'openai_model' => env('HELPDESK_SOCIAL_OPENAI_MODEL', 'gpt-4o-mini'),
        'confidence_threshold' => 0.75,
    ],

    'comments' => [
        'enabled' => env('HELPDESK_SOCIAL_COMMENTS_ENABLED', true),
        'sync_interval_minutes' => 15,
        'max_posts_per_sync' => 20,
    ],

    'queues' => [
        'processing' => 'helpdesk-social-processing',
        'analytics' => 'helpdesk-social-analytics',
        'ai' => 'helpdesk-social-ai',
    ],

    /*
    |--------------------------------------------------------------------------
    | Benchmarking de competidores
    |--------------------------------------------------------------------------
    | demo_mode: permite que SyncCompetitorMetricsJob genere métricas
    | simuladas (etiquetadas source=simulated) cuando no hay integración real
    | disponible. Forzado a false en producción sin importar el env.
    | metrics_retention_days: días que se conservan las métricas antes de
    | purgarlas (helpdesksocial:prune). 0 o menos desactiva la limpieza.
    */
    'competitors' => [
        'demo_mode' => env('HELPDESKSOCIAL_COMPETITORS_DEMO_MODE', true),
        'metrics_retention_days' => (int) env('HELPDESKSOCIAL_COMPETITORS_METRICS_RETENTION_DAYS', 180),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retención de comentarios y menciones
    |--------------------------------------------------------------------------
    | Días que se conservan los comentarios ya cerrados (replied/spam/escalated)
    | y las menciones ya procesadas (status != new) antes de purgarlos
    | (helpdesksocial:prune, diario). 0 o menos desactiva la limpieza.
    */
    'comment_retention_days' => (int) env('HELPDESKSOCIAL_RETENTION_DAYS', 180),
];
