<?php

return [
    'name' => 'HelpdeskIntegration',

    /*
    |--------------------------------------------------------------------------
    | Slugs reservados
    |--------------------------------------------------------------------------
    | Plataformas con driver nativo. No pueden crearse como proveedores custom
    | desde el catalogo (ver StoreIntegrationProviderRequest).
    */
    'reserved_platforms' => ['prestashop', 'erp'],

    /*
    |--------------------------------------------------------------------------
    | Retencion (purga programada)
    |--------------------------------------------------------------------------
    | Dias que se conservan antes de purgar via los comandos
    | helpdeskintegration:purge-identity-verifications / purge-audit-log.
    */
    'retention' => [
        'identity_verifications_days' => env('HELPDESKINTEGRATION_IDENTITY_RETENTION_DAYS', 30),
        'audit_log_days' => env('HELPDESKINTEGRATION_AUDIT_RETENTION_DAYS', 180),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sincronización (reverificación de plataformas conectadas)
    |--------------------------------------------------------------------------
    | Presupuesto total (segundos) para el sync síncrono del modal. Las
    | plataformas que no dan tiempo dentro del presupuesto quedan en estado
    | 'pending' y se reverifican en background (ResyncCustomerIntegrationsJob).
    */
    'sync' => [
        'budget_seconds' => env('HELPDESKINTEGRATION_SYNC_BUDGET_SECONDS', 8),
    ],
];
