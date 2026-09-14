<?php

return [
    'name' => 'Questions',

    /*
     | Endpoint api.php del módulo alsernetquestions instalado en PrestaShop.
     */
    'api_url' => env('ALSERNETQUESTIONS_API_URL', ''),

    /*
     | Secreto HMAC compartido con ese módulo. Propio, no el de opiniones:
     | revocar uno no debe dejar el otro sin sincronizar.
     */
    'secret' => env('ALSERNETQUESTIONS_SECRET', ''),

    /*
     | Buzón interno al que avisar de cada consulta nueva. Vacío = no se avisa.
     */
    'notify_email' => env('QUESTIONS_NOTIFY_EMAIL', ''),

    /*
     | Raíz de la tienda, para enlazar la ficha del producto desde el correo de
     | respuesta. Se deduce de api_url si no se indica.
     */
    'shop_url' => env('QUESTIONS_SHOP_URL', ''),

    'http_timeout' => env('QUESTIONS_HTTP_TIMEOUT', 10),
    'http_connect_timeout' => env('QUESTIONS_CONNECT_TIMEOUT', 3),

    /*
     | Idiomas de la tienda con su id_lang en PrestaShop.
     */
    'languages' => ['es' => 1, 'en' => 2, 'fr' => 3, 'pt' => 4, 'de' => 5, 'it' => 6],
];
