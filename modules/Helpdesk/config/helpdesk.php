<?php

return [
    'navigation' => [
        'manager' => [
            ['label' => 'Dashboard', 'route' => 'manager.helpdesk', 'icon' => 'fas fa-tachometer-alt'],
            ['label' => 'Tickets', 'route' => 'manager.helpdesk.tickets.index', 'icon' => 'fas fa-ticket-alt'],
            ['label' => 'Conversations', 'route' => 'manager.helpdesk.conversations.index', 'icon' => 'fas fa-comments'],
            ['label' => 'Customers', 'route' => 'manager.helpdesk.customers.index', 'icon' => 'fas fa-users'],
            ['label' => 'Reports', 'route' => 'manager.helpdesk.reports.index', 'icon' => 'fas fa-chart-bar'],
            ['label' => 'Help Center', 'route' => 'manager.helpdesk.helpcenter.index', 'icon' => 'fas fa-book'],
            ['label' => 'AI Agents', 'route' => 'manager.helpdesk.ai.flows.index', 'icon' => 'fas fa-robot'],
            ['label' => 'Campaigns', 'route' => 'manager.helpdesk.campaigns.index', 'icon' => 'fas fa-bullhorn'],
            ['label' => 'Templates', 'route' => 'manager.helpdesk.ticket-templates.index', 'icon' => 'fas fa-file-alt'],
            ['label' => 'Recurring', 'route' => 'manager.helpdesk.recurring-tickets.index', 'icon' => 'fas fa-redo'],
            ['label' => 'Agents', 'route' => 'manager.helpdesk.agents.index', 'icon' => 'fas fa-headset'],
            ['label' => 'Settings', 'route' => 'manager.helpdesk.backups.tickets', 'icon' => 'fas fa-cog'],
        ],
        'agent' => [
            ['label' => 'Dashboard', 'route' => 'agent.helpdesk.dashboard', 'icon' => 'fas fa-tachometer-alt'],
            ['label' => 'My Tickets', 'route' => 'agent.helpdesk.tickets.index', 'icon' => 'fas fa-ticket-alt'],
            ['label' => 'Reports', 'route' => 'agent.helpdesk.reports.index', 'icon' => 'fas fa-chart-bar'],
        ],
        // Legacy keys kept for backwards compatibility
        'manager_settings' => [
            'icon' => 'fa-duotone fas fa-headset',
            'title' => 'Helpdesk',
            'route' => 'manager.helpdesk',
            'permission' => 'manage_helpdesk_settings',
        ],
        'social_integrations' => [
            'icon' => 'fas fa-share-alt',
            'title' => 'Integraciones sociales',
            'route' => 'manager.helpdesk.backups.social-integrations.index',
            'permission' => 'manage_helpdesk_settings',
        ],
        'agent_menu' => [
            'icon' => 'fa-duotone fas fa-ticket-alt',
            'title' => 'Helpdesk',
            'route' => 'helpdesk.dashboard',
            'permission' => 'access_helpdesk',
        ],
    ],

    'ticket_number_format' => 'TKT-{year}-{sequence}',
    'ticket_number_padding' => 5,

    'auto_assignment' => [
        'enabled' => false,
        'strategy' => 'round_robin',
    ],

    'sla' => [
        'business_hours' => [
            'enabled' => true,
            'timezone' => env('HELPDESK_SLA_TIMEZONE', config('app.timezone')),
            'days' => [1, 2, 3, 4, 5],
            'start' => '09:00',
            'end' => '17:00',
        ],
        'warning_threshold_percent' => 80,
    ],

    'attachments' => [
        'max_size' => 10240,
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'txt', 'zip'],
        'allowed_mime_types' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/zip',
            'application/x-rar-compressed',
            'application/x-7z-compressed',
            'text/plain',
            'text/csv',
        ],
        'disk' => 'local',
        'path' => 'helpdesk/attachments',
    ],

    'cleanup' => [
        'enabled' => true,
        'closed_tickets_after_days' => 90,
    ],

    'notifications' => [
        'customer_on_create' => true,
        'customer_on_close' => true,
        'agent_on_assign' => true,
        'agent_on_sla_warning' => true,
    ],

    'queue' => [
        'default' => env('HELPDESK_QUEUE', 'default'),
        'notifications' => env('HELPDESK_NOTIFICATIONS_QUEUE', 'notifications'),
        'scheduled' => env('HELPDESK_SCHEDULED_QUEUE', 'helpdesk-scheduled'),
        'heavy' => env('HELPDESK_HEAVY_QUEUE', 'helpdesk-heavy'),
        'webhooks' => env('HELPDESK_WEBHOOKS_QUEUE', 'helpdesk-webhooks'),
    ],

    'email' => [
        'from_address' => env('HELPDESK_FROM_EMAIL', env('MAIL_FROM_ADDRESS', 'support@example.com')),
        'from_name' => env('HELPDESK_FROM_NAME', env('APP_NAME', 'Helpdesk')),
    ],

    'auto_response_hours' => env('HELPDESK_AUTO_RESPONSE_HOURS', 8),

    'portal' => [
        'enabled' => env('HELPDESK_PORTAL_ENABLED', true),
        'token_expiry_hours' => env('HELPDESK_PORTAL_TOKEN_EXPIRY', 24),
        'max_tickets_per_page' => env('HELPDESK_PORTAL_PAGE_SIZE', 10),
    ],

    'imap' => [
        'enabled' => env('HELPDESK_IMAP_ENABLED', false),
        'server' => env('HELPDESK_IMAP_SERVER', ''),
        'port' => env('HELPDESK_IMAP_PORT', 993),
        'encryption' => env('HELPDESK_IMAP_ENCRYPTION', 'ssl'),
        'username' => env('HELPDESK_IMAP_USERNAME', ''),
        // Supports both plaintext and "encrypted:..." payloads (see Core SecretHelper).
        'password' => secret_env('HELPDESK_IMAP_PASSWORD', ''),
        'folder' => env('HELPDESK_IMAP_FOLDER', 'INBOX'),
        'batch_size' => env('HELPDESK_IMAP_BATCH_SIZE', 50),
    ],

    'escalation' => [
        'enabled' => env('HELPDESK_ESCALATION_ENABLED', true),
        'thresholds' => [
            'low' => env('HELPDESK_ESCALATION_LOW_HOURS', 48),
            'normal' => env('HELPDESK_ESCALATION_NORMAL_HOURS', 24),
            'high' => env('HELPDESK_ESCALATION_HIGH_HOURS', 12),
        ],
    ],

    'llm_rate_limits' => [
        'per_user_per_minute' => 10,
        'per_session_per_5min' => 30,
        'per_user_per_day' => 1000,
    ],

    'prompt_injection_patterns' => [
        '/ignore\s+(all\s+)?previous\s+instructions/i',
        '/system\s+prompt/i',
        '/reveal\s+your\s+(system\s+)?prompt/i',
        '/you\s+are\s+(now\s+)?a/i',
    ],

    'integrations' => [
        'whatsapp' => [
            'enabled' => env('WHATSAPP_ENABLED', false),
            'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),
            'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
            'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
            'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
            'app_secret' => env('WHATSAPP_APP_SECRET', env('FACEBOOK_APP_SECRET')),
            'api_url' => env('WHATSAPP_API_URL', 'https://graph.facebook.com/v19.0'),
        ],
        'facebook' => [
            'enabled' => env('FACEBOOK_ENABLED', false),
            'app_id' => env('FACEBOOK_APP_ID'),
            'app_secret' => env('FACEBOOK_APP_SECRET'),
            'page_access_token' => env('FACEBOOK_PAGE_ACCESS_TOKEN'),
            'verify_token' => env('FACEBOOK_VERIFY_TOKEN'),
        ],
        'instagram' => [
            'enabled' => env('INSTAGRAM_ENABLED', false),
            'business_account_id' => env('INSTAGRAM_BUSINESS_ACCOUNT_ID'),
            'access_token' => env('INSTAGRAM_ACCESS_TOKEN'),
        ],
    ],
];
