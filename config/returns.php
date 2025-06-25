<?php

return [
    'return_reference' => env('RETURN_REFERENCE', 'DEV'),
    'return_days_limit' => env('RETURN_DAYS_LIMIT', 30),
    'default_status_id' => env('RETURN_DEFAULT_STATUS_ID', 1),
    'approved_status_id' => env('RETURN_APPROVED_STATUS_ID', 2),
    'send_confirmation_email' => env('RETURN_SEND_CONFIRMATION_EMAIL', true),
    'send_status_update_email' => env('RETURN_SEND_STATUS_UPDATE_EMAIL', true),
    'pdf_content' => env('RETURN_PDF_CUSTOM_CONTENT', ''),

    'company_info' => [
        'name' => env('COMPANY_NAME', 'Tu Empresa'),
        'address' => env('COMPANY_ADDRESS', 'Dirección de la empresa'),
        'phone' => env('COMPANY_PHONE', 'Teléfono'),
        'email' => env('COMPANY_EMAIL', 'info@empresa.com'),
        'website' => env('COMPANY_WEBSITE', 'www.empresa.com')
    ],
    'notifications' => [
        'enabled' => true,
        'queue' => 'emails',
        'reminder_cooldown_hours' => 24,
        'support_email' => 'soporte@ejemplo.com',
        'support_phone' => '900 123 456',
        'from' => [
            'address' => env('RETURNS_FROM_ADDRESS', 'noreply@example.com'),
            'name' => env('RETURNS_FROM_NAME', 'Devoluciones')
        ],
        'expiration_days' => 30,
        'admin_email' => env('RETURNS_ADMIN_EMAIL', 'admin@example.com'),
        'reminder_days' => 7, // Días para enviar recordatorio
        'tracking_enabled' => true,
    ],

    'logistics_modes' => [
        'customer_transport' => 'Agencia de transporte (cuenta del cliente)',
        'home_pickup' => 'Recogida a domicilio',
        'store_delivery' => 'Entrega en tienda',
        'inpost' => 'InPost'
    ],

    'return_order_statuses' => env('RETURN_ORDER_STATUS', '5,4'), // Estados de pedido que permiten devolución
    'allow_virtual_products' => env('RETURN_ALLOW_VIRTUAL', false),
    'customer_wallet_enabled' => env('RETURN_CUSTOMER_WALLET', false),
    'terms_and_conditions_required' => env('RETURN_TERMS_AND_CONDITIONS', true),
    'terms_and_conditions_cms_page' => env('RETURN_TERMS_AND_CONDITIONS_CMS_PAGE', 1),

    'allowed_erp_statuses' => env('RETURN_ALLOWED_ERP_STATUSES', '4,5,6'),
    'min_return_amount' => env('RETURN_MIN_AMOUNT', 0),
    'high_value_product_threshold' => env('RETURN_HIGH_VALUE_THRESHOLD', 500),
    'restricted_catalogs' => explode(',', env('RETURN_RESTRICTED_CATALOGS', '')),

    // Configuraciones de validación
    'validation' => [
        'min_description_length' => 10,
        'max_description_length' => 1000,
        'allowed_file_types' => ['jpg', 'jpeg', 'png', 'pdf'],
        'max_file_size' => 5120, // KB
    ],

    // Configuraciones de notificaciones
    'notifications' => [
        'admin_email' => env('RETURN_ADMIN_EMAIL'),
        'notify_admin_on_new_return' => env('RETURN_NOTIFY_ADMIN', true),
        'notify_customer_on_status_change' => env('RETURN_NOTIFY_CUSTOMER', true),
    ]
];
