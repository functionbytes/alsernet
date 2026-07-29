<?php

return [
    'erp_internal_url' => env('ERP_INTERNAL_URL', 'http://nginx'),

    'erp_sync' => [
        // Lower bound for the ERP "products/filter" sync (idmodelo import).
        'filter_date_from' => env('SUPPLIER_ERP_FILTER_DATE_FROM', '2026-01-01'),
    ],

    'automation' => [
        'enabled' => env('SUPPLIER_AUTOMATION_ENABLED', true),
        'max_retries' => env('SUPPLIER_AUTOMATION_MAX_RETRIES', 3),
        'timeout_seconds' => env('SUPPLIER_AUTOMATION_TIMEOUT', 300),
    ],
    'content_generation' => [
        'enabled' => env('SUPPLIER_CONTENT_GENERATION_ENABLED', true),
        'model' => env('SUPPLIER_AI_MODEL', 'gpt-4'),
        'temperature' => env('SUPPLIER_AI_TEMPERATURE', 0.7),
    ],
    'extraction' => [
        'enabled' => env('SUPPLIER_EXTRACTION_ENABLED', true),
        'batch_size' => env('SUPPLIER_EXTRACTION_BATCH_SIZE', 100),
    ],
    'sources' => [
        'default_timeout' => env('SUPPLIER_SOURCE_TIMEOUT', 30),
        'verify_ssl' => env('SUPPLIER_SOURCE_VERIFY_SSL', true),
    ],
];
