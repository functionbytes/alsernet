<?php

return [
    'name' => 'Attention',

    /*
    |--------------------------------------------------------------------------
    | PQRSF Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for PQRSF system (Peticiones, Quejas, Reclamos,
    | Sugerencias, Felicitaciones)
    |
    */

    'radicado_prefix' => env('PQRSF_RADICADO_PREFIX', 'PQRSF'),

    'attachments' => [
        'max_size' => 10 * 1024 * 1024, // 10MB
        'allowed_mimes' => [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/jpg',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ],
    ],

    'emails' => [
        'from' => [
            'address' => env('MAIL_FROM_ADDRESS', 'noreply@inoqualab.com'),
            'name' => env('MAIL_FROM_NAME', 'INOQUALAB'),
        ],
    ],

    'statuses' => [
        'received' => 1,
        'in_process' => 2,
        'resolved' => 3,
        'closed' => 4,
    ],

    /*
    |--------------------------------------------------------------------------
    | Settings Configuration
    |--------------------------------------------------------------------------
    |
    | Default settings for the Attention module. These can be overridden
    | through the settings settings interface.
    |
    */

    'settings' => [
        // General Settings
        'radicado_prefix' => env('PQRSF_RADICADO_PREFIX', 'PQRSF'),
        'auto_assign' => env('PQRSF_AUTO_ASSIGN', false),
        'require_attachments' => env('PQRSF_REQUIRE_ATTACHMENTS', false),
        'enable_anonymous' => env('PQRSF_ENABLE_ANONYMOUS', true),
        'max_attachments' => env('PQRSF_MAX_ATTACHMENTS', 5),
        'attachment_max_size' => env('PQRSF_ATTACHMENT_MAX_SIZE', 10240), // KB

        // Email Templates
        'email_templates' => [
            'received' => null,
            'in_process' => null,
            'resolved' => null,
            'closed' => null,
            'assigned' => null,
        ],

        // Email Notifications
        'notifications' => [
            'enabled' => env('PQRSF_EMAIL_NOTIFICATIONS', true),
            'notify_on_received' => true,
            'notify_on_in_process' => true,
            'notify_on_resolved' => true,
            'notify_on_closed' => true,
            'notify_assigned_user' => true,
        ],

        // SLA Defaults
        'sla_defaults' => [
            'enabled' => env('PQRSF_SLA_ENABLED', true),
            'response_hours' => env('PQRSF_SLA_RESPONSE_HOURS', 24),
            'resolution_hours' => env('PQRSF_SLA_RESOLUTION_HOURS', 72),
            'business_hours_only' => env('PQRSF_SLA_BUSINESS_HOURS_ONLY', true),
            'business_hours_start' => env('PQRSF_SLA_BUSINESS_START', '09:00'),
            'business_hours_end' => env('PQRSF_SLA_BUSINESS_END', '17:00'),
            'exclude_weekends' => env('PQRSF_SLA_EXCLUDE_WEEKENDS', true),
            'auto_escalate' => env('PQRSF_SLA_AUTO_ESCALATE', true),
            'escalation_email' => env('PQRSF_SLA_ESCALATION_EMAIL', ''),
        ],
    ],
];
