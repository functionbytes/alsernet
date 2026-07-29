<?php

return [
    // =========================================
    // API AUTH
    // =========================================
    // Protección de /api/erp/* (customer, products, suppliers, families, ...).
    // Cuando 'enabled' = false (default), las rutas están abiertas y se asume que
    // el acceso lo restringe un firewall / reverse-proxy externo.
    //
    // Cuando 'enabled' = true se aplica el guard seleccionado:
    //   - 'sanctum'   → requiere Bearer Sanctum (usuarios autenticados / IssueBridgeToken)
    //   - 'erp_token' → requiere header `X-Erp-Token` con un ErpEndpointToken válido
    //   - 'both'      → acepta cualquiera de los dos en cascada
    'api' => [
        'enabled' => env('ERP_API_AUTH_ENABLED', false),
        'guard' => env('ERP_API_AUTH_GUARD', 'sanctum'),
        'throttle' => env('ERP_API_THROTTLE', '60,1'),
        'public_token_throttle' => env('ERP_PUBLIC_TOKEN_THROTTLE', '60,1'),
    ],

    'url_erp' => env('ERP_URL'),
    // Constantes para bonos
    'bono_origen_web' => 'web',
    'bono_origen_gestion' => 'gestion',
    'marcar_bono_anular' => 0,
    'marcar_bono_recargar' => 1,
    'marcar_bono_consumir' => 2,

    // Métodos de pago
    'payment_cashondelivery' => 1,
    'payment_wire' => 3,
    'payment_creditcard' => 7,
    'payment_redsys' => 22,
    'payment_bizum' => 8,
    'payment_google' => 26,
    'payment_apple' => 27,
    'payment_paypal' => 10,
    'payment_finance' => 11,
    'payment_sequra' => 100000101,
    'payment_Alsernetfinance' => 5,
    'payment_transferencia_online' => '25',
    'payment_ban_lendismart' => 28,

    'payment_bizum_tpv' => 2,
    'payment_google_tpv' => 3,
    'payment_apple_tpv' => 2,

    // =========================================
    // INTEGRACIÓN CONFIG
    // =========================================
    'oracle' => [
        'enabled' => env('ORACLE_ENABLED', false),
        'connection' => 'oracle',
    ],

    'prestashop' => [
        'enabled' => env('PRESTASHOP_ENABLED', false),
        'connection' => 'prestashop',
    ],

    'price_validation' => [
        'queue' => 'default',
        'timeout' => 300,
        'retries' => 3,
    ],

    'country_mapping' => [
        6 => 1,   // España
        15 => 2,  // Portugal
        8 => 3,   // Francia
        1 => 4,   // Alemania
        10 => 5,  // Italia
        2 => 6,   // Austria
    ],

    // =========================================
    // SUPPLIER SYNC CONFIG (ERP ↔ Supplier)
    // =========================================
    'supplier_sync' => [
        // Real-time monitoring of Oracle changes
        'oracle_monitor_interval' => env('ORACLE_MONITOR_INTERVAL', 30), // seconds between monitor cycles
        'oracle_monitor_chunk_size' => env('ORACLE_MONITOR_CHUNK_SIZE', 100), // records per transaction

        // Bidirectional sync queue
        'sync_queue' => env('ERP_SYNC_QUEUE', 'erp-sync'),
        'sync_timeout' => 30, // seconds
        'sync_retries' => 3,
        'sync_backoff' => [5, 15, 30], // exponential backoff in seconds

        // Dead Letter Queue (failed syncs)
        'dlq_max_retries' => 5,
        'dlq_retry_delay' => 30, // minutes before auto-retry

        // =====================================================
        // MEJORA #6: Selective Entity Monitoring
        // =====================================================
        // Enable/disable monitoring per entity type
        // Set to false to skip syncing specific entities
        'monitored_entities' => [
            'sports' => env('SYNC_MONITOR_SPORTS', true),
            'categories' => env('SYNC_MONITOR_CATEGORIES', true),
            'families' => env('SYNC_MONITOR_FAMILIES', true),
            'subfamilies' => env('SYNC_MONITOR_SUBFAMILIES', true),
            'groups' => env('SYNC_MONITOR_GROUPS', true),
            'providers' => env('SYNC_MONITOR_PROVIDERS', true),
            'products' => env('SYNC_MONITOR_PRODUCTS', true),
            'provider_products' => env('SYNC_MONITOR_PROVIDER_PRODUCTS', true),
            'prices' => env('SYNC_MONITOR_PRICES', true),
        ],
    ],
];
