<?php

return [
    'name' => 'Attention',

    /*
    |--------------------------------------------------------------------------
    | peticiones Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for peticiones system (Peticiones, Quejas, Reclamos,
    | Sugerencias, Felicitaciones)
    |
    */

    'radicado_prefix' => env('peticiones_RADICADO_PREFIX', 'peticiones'),

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
        'radicado_prefix' => env('peticiones_RADICADO_PREFIX', 'peticiones'),
        'auto_assign' => env('peticiones_AUTO_ASSIGN', false),
        'require_attachments' => env('peticiones_REQUIRE_ATTACHMENTS', false),
        'enable_anonymous' => env('peticiones_ENABLE_ANONYMOUS', true),
        'max_attachments' => env('peticiones_MAX_ATTACHMENTS', 5),
        'attachment_max_size' => env('peticiones_ATTACHMENT_MAX_SIZE', 10240), // KB

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
            'enabled' => env('peticiones_EMAIL_NOTIFICATIONS', true),
            'notify_on_received' => true,
            'notify_on_in_process' => true,
            'notify_on_resolved' => true,
            'notify_on_closed' => true,
            'notify_assigned_user' => true,
        ],

        // SLA Defaults
        'sla_defaults' => [
            'enabled' => env('peticiones_SLA_ENABLED', true),
            'response_hours' => env('peticiones_SLA_RESPONSE_HOURS', 24),
            'resolution_hours' => env('peticiones_SLA_RESOLUTION_HOURS', 72),
            'business_hours_only' => env('peticiones_SLA_BUSINESS_HOURS_ONLY', true),
            'business_hours_start' => env('peticiones_SLA_BUSINESS_START', '09:00'),
            'business_hours_end' => env('peticiones_SLA_BUSINESS_END', '17:00'),
            'exclude_weekends' => env('peticiones_SLA_EXCLUDE_WEEKENDS', true),
            'auto_escalate' => env('peticiones_SLA_AUTO_ESCALATE', true),
            'escalation_email' => env('peticiones_SLA_ESCALATION_EMAIL', ''),
        ],
    ],
];
