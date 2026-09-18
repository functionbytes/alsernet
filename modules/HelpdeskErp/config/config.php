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

    // Búsqueda de clientes (buscador externo): las búsquedas por nombre/
    // apellidos recorren CLIENTE_CENT entera (~14 s sin índice), no caben en
    // http_timeout.
    'search_timeout' => env('HELPDESK_ERP_SEARCH_TIMEOUT', 40),

    /*
     | Circuit breaker: nº de fallos consecutivos del manager (conexión rechazada
     | o timeout) tras los que se corta el tráfico ERP. Con el manager caído cada
     | request cuelga hasta http_timeout; el breaker evita arrastrar el inbox.
     */
    'circuit_failure_threshold' => env('HELPDESK_ERP_CIRCUIT_FAILURE_THRESHOLD', 5),

    /*
     | Circuit breaker: segundos que permanece abierto (se saltan las llamadas y
     | se devuelve contexto vacío) antes de reintentar contra el manager.
     |
     | Debe cubrir con margen el peor caso para ACUMULAR el umbral de fallos:
     | Cache::add() fija el TTL de la ventana solo en el primer fallo, y los
     | incrementos posteriores NO lo renuevan (HasCircuitBreaker::recordFailure()).
     | Con http_timeout=15s y circuit_failure_threshold=5, el manager caído tarda
     | hasta 5×15=75s en generar los 5 fallos que abren el breaker — con una
     | ventana de 30s (el valor anterior) la clave de caché expiraba y el
     | contador volvía a cero antes de llegar al umbral, así que el breaker
     | JAMÁS llegaba a abrirse de verdad con una caída real (solo en los tests,
     | que fallan al instante con Http::fake() en vez de colgar 15s). Bug real
     | encontrado 4-sep-2026: WarmErpCacheJob (5 emails secuenciales, timeout
     | 60s) llevaba media hora reventando su propio timeout sin que el breaker
     | interviniera nunca, monopolizando el único worker que atiende
     | 'notifications' (ver reference_helpdesk_erp_warmcache_infinite_loop).
     */
    'circuit_open_seconds' => env('HELPDESK_ERP_CIRCUIT_OPEN_SECONDS', 120),

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

    /*
     | Segundos que espera la sonda de salud antes de dar el ERP por caído.
     |
     | Estaba fijada en 3 y una búsqueda real tarda entre 3,4 y 6,5 segundos
     | contra Oracle (medido el 7-sep-2026), así que la sonda expiraba siempre y
     | el panel daba el ERP por degradado de forma permanente mientras
     | funcionaba con normalidad.
     |
     | 10 segundos cubren ese rango con margen sin dejar la sonda colgada: por
     | encima de eso el ERP está de verdad para pocas cosas, y ahí "degradado"
     | es la respuesta correcta.
     */
    'health_timeout' => env('HELPDESK_ERP_HEALTH_TIMEOUT', 10),

    /*
     | Enfriamiento, en minutos, antes de volver a buscar en el ERP un cliente
     | que ya se buscó y no apareció. Sin esto, cada correo de un remitente que
     | no es cliente (proveedores, notificaciones, spam que pasa el filtro)
     | vuelve a consultar el manager. La cola helpdesk-erp comparte procesos de
     | supervisor con otras veinte colas, así que este freno importa.
     |
     | El estado vive en helpdesk_customers.erp_lookup_status/erp_lookup_at.
     | Un reintento pedido a mano por el agente lo ignora.
     */
    'lookup_cooldown_minutes' => env('HELPDESK_ERP_LOOKUP_COOLDOWN', 1440),

    /*
     | Enfriamiento más corto cuando el intento anterior falló por caída del
     | ERP en vez de por no existir el cliente: ahí el reintento sí tiene
     | sentido pronto.
     */
    'lookup_error_cooldown_minutes' => env('HELPDESK_ERP_LOOKUP_ERROR_COOLDOWN', 30),
];
