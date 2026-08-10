<?php

return [
    'name' => 'Forms',

    /*
     | Secreto HMAC compartido con el módulo alsernetforms de PrestaShop
     | (Configuration ALSERNETFORMS_WEBHOOK_SECRET, generada en su
     | install()/upgrade). Debe copiarse el mismo valor a ambos lados.
     */
    'webhook_secret' => env('FORMS_WEBHOOK_SECRET', ''),
];
