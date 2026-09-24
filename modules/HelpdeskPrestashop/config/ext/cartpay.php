<?php

/*
|--------------------------------------------------------------------------
| Extensión "cartpay" — piezas 02 (Cobro del pedido pendiente) y 32
| (Convertir o vaciar carrito)
|--------------------------------------------------------------------------
| Acciones del puente en alsernetbridge/helpers/ext/cartpay.php. Accesible en
| config('helpdeskprestashop.ext.cartpay.*').
*/

return [
    'write_actions' => ['cartpay.convert', 'cartpay.empty'],

    'permissions' => [
        // Crear un pedido real a partir del carrito en vivo, pendiente de
        // pago por transferencia: dispara ERP, stock y el correo al cliente.
        'helpdeskprestashop.cartpay.convert' => 'Convertir el carrito en vivo del cliente en pedido pendiente de pago',
        // Crear el pedido YA como pagado (Pago aceptado / Pedido confirmado):
        // es dar por cobrado un dinero que PrestaShop no ha visto, así que va
        // aparte y solo para responsables.
        'helpdeskprestashop.cartpay.convert_paid' => 'Convertir el carrito en pedido marcado como pagado',
        // Vaciar es irreversible y el cliente lo ve en su sesión abierta.
        'helpdeskprestashop.cartpay.empty' => 'Vaciar el carrito en vivo del cliente en PrestaShop',
    ],

    // El agente de base y el restringido no crean pedidos ni vacían carritos.
    'role_permissions' => [
        'helpdesk-supervisor' => ['helpdeskprestashop.cartpay.convert', 'helpdeskprestashop.cartpay.empty'],
        'helpdesk-manager' => ['helpdeskprestashop.cartpay.convert', 'helpdeskprestashop.cartpay.convert_paid', 'helpdeskprestashop.cartpay.empty'],
        'helpdesk-admin' => ['helpdeskprestashop.cartpay.convert', 'helpdeskprestashop.cartpay.convert_paid', 'helpdeskprestashop.cartpay.empty'],
    ],

    'listeners' => [],

    'convert' => [
        // Claves de estado que entiende el puente (PS_OS_BANKWIRE,
        // PS_OS_PAYMENT, PS_OS_PREPARATION) y si cuentan como pagado.
        'states' => [
            'bankwire' => ['paid' => false],
            'payment' => ['paid' => true],
            'preparation' => ['paid' => true],
        ],
    ],
];
