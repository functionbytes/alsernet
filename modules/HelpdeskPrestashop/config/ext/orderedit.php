<?php

/**
 * Extensión "orderedit": repetir pedido (pieza 11 del documento "Alvarez
 * PrestaShop en el Chat"). Crea un carrito NUEVO del cliente en PrestaShop
 * con las líneas de un pedido anterior, a precio actual.
 *
 * Editar líneas / añadir producto a un pedido ya hecho (piezas 09 y 21) y el
 * cambio de producto (06) no se implementan: en esta tienda el pedido pasa a
 * Gestión (ERP) a los pocos minutos y el cambio de producto lo gestiona Rever.
 */
return [
    'write_actions' => [
        'orderedit.reorder_create',
    ],

    'permissions' => [
        'helpdeskprestashop.orders.reorder' => 'Repetir pedidos de PrestaShop (crear un carrito nuevo del cliente con las líneas de un pedido anterior)',
        // Aparte del anterior: manda un correo real al cliente (plantilla
        // backoffice_order del core) con un enlace que inicia sesión en la
        // tienda como él (FrontController::recoverCart), así que un
        // administrador puede querer retirarlo sin quitar la creación del
        // carrito.
        'helpdeskprestashop.orders.reorder_link' => 'Enviar por correo al cliente el enlace para abrir y pagar el carrito repetido',
    ],

    // helpdesk-agent-restricted no escribe en la tienda.
    'role_permissions' => [
        'helpdesk-agent' => ['helpdeskprestashop.orders.reorder', 'helpdeskprestashop.orders.reorder_link'],
        'helpdesk-supervisor' => ['helpdeskprestashop.orders.reorder', 'helpdeskprestashop.orders.reorder_link'],
        'helpdesk-manager' => ['helpdeskprestashop.orders.reorder', 'helpdeskprestashop.orders.reorder_link'],
        'helpdesk-admin' => ['helpdeskprestashop.orders.reorder', 'helpdeskprestashop.orders.reorder_link'],
    ],

    'listeners' => [],

    // Tope de líneas que se mandan al bridge (el bridge tiene el suyo: 60).
    'max_lines' => 60,

    // La vista previa se cachea poco: precio y stock cambian, pero abrir y
    // cerrar la hoja varias veces no debe repetir el cálculo en la tienda.
    'preview_ttl' => 60,
];
