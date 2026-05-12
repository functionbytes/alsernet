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
];
