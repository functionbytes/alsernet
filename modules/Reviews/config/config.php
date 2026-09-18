<?php

return [
    'name' => 'Reviews',

    /*
     | Endpoint api.php del módulo alsernetreviews instalado en PrestaShop.
     | Ejemplo: https://tienda.ejemplo.com/modules/alsernetreviews/api.php
     */
    'api_url' => env('ALSERNETREVIEWS_API_URL', ''),

    /*
     | Secreto HMAC compartido con ese módulo (configuración ALSERNETREVIEWS_SECRET
     | de PrestaShop). Firma tanto lo que llega como lo que se devuelve.
     */
    'secret' => env('ALSERNETREVIEWS_SECRET', ''),

    /*
     | Segundos de espera para las llamadas al API de la tienda.
     */
    'http_timeout' => env('REVIEWS_HTTP_TIMEOUT', 10),
    'http_connect_timeout' => env('REVIEWS_CONNECT_TIMEOUT', 3),

    /*
     | Idiomas a los que se publica una opinión aprobada, con su id_lang en
     | PrestaShop. El origen se excluye solo: no se traduce a sí mismo.
     */
    'languages' => [
        'es' => 1,
        'en' => 2,
        'fr' => 3,
        'pt' => 4,
        'de' => 5,
        'it' => 6,
    ],

    /*
     | Traducción automática al aprobar. Desactivada: mientras esté en false, el
     | módulo registra, modera y publica, pero no traduce nada.
     */
    'auto_translate' => env('REVIEWS_AUTO_TRANSLATE', false),
];
