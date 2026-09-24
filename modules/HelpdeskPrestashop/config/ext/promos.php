<?php

/*
|--------------------------------------------------------------------------
| Extensión "promos" — piezas 33 (Editar cupón) y 34 (Promociones de la tienda)
|--------------------------------------------------------------------------
| Acciones del puente en alsernetbridge/helpers/ext/promos.php. Accesible en
| config('helpdeskprestashop.ext.promos.*').
*/

return [
    'write_actions' => ['promos.voucher_edit'],

    'permissions' => [
        // Editar un cupón propio del cliente sin usar, o duplicarlo si ya se
        // usó. Las subidas de importe siguen limitadas por el límite de vale
        // del agente (helpdeskprestashop.vouchers.*), igual que crear uno.
        'helpdeskprestashop.vouchers.edit' => 'Editar o duplicar cupones propios del cliente en PrestaShop',
    ],

    // El agente restringido no escribe en la tienda: no aparece aquí.
    'role_permissions' => [
        'helpdesk-agent' => ['helpdeskprestashop.vouchers.edit'],
        'helpdesk-supervisor' => ['helpdeskprestashop.vouchers.edit'],
        'helpdesk-manager' => ['helpdeskprestashop.vouchers.edit'],
        'helpdesk-admin' => ['helpdeskprestashop.vouchers.edit'],
    ],

    'listeners' => [],

    'edit' => [
        // Tope de porcentaje para SUBIR un cupón de porcentaje (bajarlo o
        // dejarlo igual siempre se puede). El puente limita a 100.
        'max_percent' => (float) env('HELPDESK_PS_PROMOS_MAX_PERCENT', 30),
        // Usos máximos al subir la cantidad (el puente limita a 20).
        'max_quantity' => (int) env('HELPDESK_PS_PROMOS_MAX_QUANTITY', 5),
        // Caducidad máxima desde hoy (el puente limita a 730 días).
        'max_validity_days' => (int) env('HELPDESK_PS_PROMOS_MAX_VALIDITY_DAYS', 365),
        // Pedido mínimo máximo que se puede fijar (el puente limita a 10.000 €).
        'max_minimum' => 10000,
    ],

    'shop' => [
        // Segundos que se guarda en caché la lista de promociones: es la
        // misma para todos los clientes y cambia poco.
        'cache_ttl' => (int) env('HELPDESK_PS_PROMOS_SHOP_TTL', 300),
        // Un código sin cliente con un solo uso es un saldo personal (abono de
        // REVER, bono de Gestión), no una promoción: se exige multiuso.
        'min_quantity' => 2,
        // Códigos multiuso que NO son promociones para clientes.
        'exclude_codes' => ['ALV-PEDIDOPRUEBA'],
    ],
];
