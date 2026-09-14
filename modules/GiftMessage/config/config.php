<?php

return [
    'name' => 'GiftMessage',
    'generation_retention_days' => env('GIFTMESSAGE_GENERATION_RETENTION_DAYS', 90),

    /*
     | URL y secreto del bridge alsernetbridge (mismo bridge/credenciales que
     | usa HelpdeskPrestashop — no dupliques estas variables de entorno).
     */
    'bridge_url' => env('ALSERNETBRIDGE_API_URL', ''),
    'bridge_secret' => env('ALSERNETBRIDGE_WEBHOOK_SECRET', ''),

    /*
     | Timeouts (segundos) para las llamadas HTTP al bridge.
     */
    'bridge_http_timeout' => env('GIFTMESSAGE_BRIDGE_TIMEOUT', 10),
    'bridge_http_connect_timeout' => env('GIFTMESSAGE_BRIDGE_CONNECT_TIMEOUT', 2),
];
