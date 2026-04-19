<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Category labels for variable grouping
    |--------------------------------------------------------------------------
    */
    'category_labels' => [
        'system' => 'Sistema',
        'site' => 'Sitio',
        'customer' => 'Cliente',
        'order' => 'Pedido',
        'document' => 'Documento',
        'general' => 'General',
    ],

    /*
    |--------------------------------------------------------------------------
    | Available modules for variables
    |--------------------------------------------------------------------------
    */
    'modules' => [
        'core' => 'Core',
        'documents' => 'Documentos',
        'orders' => 'Pedidos',
    ],

    /*
    |--------------------------------------------------------------------------
    | Critical component aliases that cannot be deleted
    |--------------------------------------------------------------------------
    */
    'critical_components' => [
        'mail_template_header',
        'mail_template_footer',
        'mail_template_wrapper',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache layout aliases for targeted cache clearing
    |--------------------------------------------------------------------------
    */
    'cache_layout_aliases' => [
        'email_template_header',
        'email_template_footer',
        'email_template_wrapper',
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue settings for background jobs
    |--------------------------------------------------------------------------
    */
    'queue' => env('MAILER_QUEUE', 'emails'),

    /*
    |--------------------------------------------------------------------------
    | Send limits applied by SendEndpointEmailJob
    |--------------------------------------------------------------------------
    */
    'limits' => [
        'payload_max_bytes' => (int) env('MAILER_PAYLOAD_MAX_BYTES', 262144),   // 256 KB
        'subject_max_length' => (int) env('MAILER_SUBJECT_MAX_LENGTH', 255),
        'body_max_bytes' => (int) env('MAILER_BODY_MAX_BYTES', 5242880),        // 5 MB
    ],

    /*
    |--------------------------------------------------------------------------
    | Retention policies (purge commands)
    |--------------------------------------------------------------------------
    */
    'retention' => [
        'logs_days' => (int) env('MAILER_LOG_RETENTION_DAYS', 90),
        'versions_per_template' => (int) env('MAILER_VERSIONS_PER_TEMPLATE', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | Reserved slugs that cannot be used on endpoints (collision with API routes)
    |--------------------------------------------------------------------------
    */
    'reserved_slugs' => ['send', 'info', 'status', 'logs'],
];
