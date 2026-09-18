<?php

return [
    'name' => 'HelpdeskAnalytics',

    /*
    | TTL (segundos) del cache de las agregaciones del dashboard.
    */
    'cache_ttl' => 300,

    /*
    | Maximo de clientes a puntuar (health score) por rango para los segmentos.
    */
    'customer_segment_limit' => 500,
];
