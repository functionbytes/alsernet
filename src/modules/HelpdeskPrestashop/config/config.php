<?php

return [
    'name' => 'HelpdeskPrestashop',

    /*
     | URL del endpoint api.php del módulo alsernetbridge instalado en PrestaShop.
     | Ejemplo: https://tienda.ejemplo.com/modules/alsernetbridge/api.php
     */
    'api_url' => env('ALSERNETBRIDGE_API_URL', ''),

    /*
     | Secreto HMAC compartido con el módulo alsernetbridge (PrestaShop config
     | ALSERNETBRIDGE_WEBHOOK_SECRET). Se usa para firmar cada petición.
     */
    'webhook_secret' => env('ALSERNETBRIDGE_WEBHOOK_SECRET', ''),

    /*
     | Tiempo en segundos que se cachea el contexto del cliente cuando el cliente
     | existe en PrestaShop (customer.found === true). Por defecto 5 minutos.
     */
    'cache_ttl' => env('HELPDESK_PS_CACHE_TTL', 300),

    /*
     | TTL corto (segundos) para respuestas negativas (customer.found === false).
     | Evita cachear "no encontrado" indefinidamente mientras el cliente puede
     | registrarse. Por defecto 60 s.
     */
    'miss_ttl' => env('HELPDESK_PS_MISS_TTL', 60),

    /*
     | Ventana de gracia (segundos) antes de que expire el TTL en la que se
     | dispara una revalidación en background (stale-while-revalidate).
     | Por defecto 30 s.
     */
    'stale_grace' => env('HELPDESK_PS_STALE_GRACE', 30),

    /*
     | Segundos de timeout para llamadas HTTP al API de PrestaShop.
     */
    'http_timeout' => env('HELPDESK_PS_HTTP_TIMEOUT', 10),

    /*
     | Segundos máximos para establecer la conexión TCP con PrestaShop antes de
     | abortar. Independiente de http_timeout, evita request hangs en caídas de red.
     */
    'http_connect_timeout' => env('HELPDESK_PS_CONNECT_TIMEOUT', 2),

    /*
     | Circuit breaker: número de fallos consecutivos antes de abrir el circuito,
     | y segundos durante los que se rechazan llamadas tras abrirse.
     */
    'circuit_failure_threshold' => env('HELPDESK_PS_CIRCUIT_THRESHOLD', 5),
    'circuit_open_seconds' => env('HELPDESK_PS_CIRCUIT_OPEN_SECONDS', 30),

    /*
     | Nombre de la base de datos de PrestaShop (solo lectura) usada por el banco
     | de pruebas del helpdesk para buscar clientes. Se consulta vía conexión `mysql`.
     */
    'ps_db' => env('HELPDESK_PS_DB', 'alvarez_cristia'),
];
