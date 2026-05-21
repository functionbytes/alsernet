<?php

return [
    'name' => 'HelpdeskErp',

    /*
     | URL base del proyecto manager (sin trailing slash).
     | Ejemplo: https://manager.test  o  http://192.168.1.3
     | La API ERP está en {manager_url}/api/erp/...
     */
    'manager_url' => env('ERP_MANAGER_URL', ''),

    /*
     | Token Sanctum emitido por el proyecto manager para el bridge Alsernet.
     | Generarlo en manager con:  php artisan erp:issue-bridge-token
     */
    'bridge_token' => env('ERP_BRIDGE_TOKEN', ''),

    /*
     | TTL en segundos para el contexto del cliente cuando se encuentra (default: 10 min).
     */
    'cache_ttl' => env('HELPDESK_ERP_CACHE_TTL', 600),

    /*
     | TTL en segundos para negative cache cuando el cliente no se encuentra (default: 1 min).
     | Evita golpear el manager repetidamente para emails inexistentes.
     */
    'miss_ttl' => env('HELPDESK_ERP_MISS_TTL', 60),

    /*
     | Segundos antes de la expiración real en los que se considera el caché stale
     | y se lanza un RefreshErpContextJob en background (stale-while-revalidate).
     */
    'stale_grace' => env('HELPDESK_ERP_STALE_GRACE', 60),

    /*
     | Timeout HTTP para llamadas a la API del manager (segundos).
     */
    'http_timeout' => env('HELPDESK_ERP_HTTP_TIMEOUT', 15),

    /*
     | TTL en segundos para la línea temporal agregada del cliente (ERP + PrestaShop + Helpdesk).
     | TTL corto: solo evita re-agregar las tres fuentes en visitas repetidas (default: 2 min).
     | El webhook orders-ready invalida esta entrada al terminar el escaneo Oracle.
     */
    'timeline_cache_ttl' => env('HELPDESK_ERP_TIMELINE_CACHE_TTL', 120),

    /*
     | Máximo de pedidos ERP a recuperar.
     */
    'orders_limit' => 10,

    /*
     | Máximo de facturas ERP a recuperar.
     */
    'invoices_limit' => 5,

    /*
     | Secret HMAC compartido con el proyecto manager para validar webhooks
     | que notifican que el escaneo de pedidos Oracle ha terminado.
     | Generar con: openssl rand -hex 32
     */
    'webhook_secret' => env('ERP_WEBHOOK_SECRET', ''),
];
