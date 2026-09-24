<?php

/**
 * Extensión "address" — piezas 27 (crear/editar dirección con país y
 * provincia) y 05 (incidencia de envío) del documento "PrestaShop en el chat".
 *
 * Ninguna acción nueva de escritura en el bridge: el alta/edición de
 * direcciones sigue siendo customer.address.create/update y la incidencia se
 * registra con order.add_note (ambas ya en la lista nativa de escrituras).
 */
return [
    'write_actions' => [],

    'permissions' => [
        // Antes el alta de direcciones colgaba de carts.manage; se separa
        // porque crear una dirección no toca el carrito en vivo (las Form
        // Requests aceptan ambos para no quitar acceso a quien ya lo tenía).
        'helpdeskprestashop.addresses.manage' => 'Crear y editar direcciones del cliente en PrestaShop',
        'helpdeskprestashop.orders.ship_claim' => 'Registrar incidencias de envío como nota interna del pedido en PrestaShop',
    ],

    // helpdesk-agent-restricted no escribe en la tienda: no aparece aquí.
    'role_permissions' => [
        'helpdesk-agent' => ['helpdeskprestashop.addresses.manage', 'helpdeskprestashop.orders.ship_claim'],
        'helpdesk-supervisor' => ['helpdeskprestashop.addresses.manage', 'helpdeskprestashop.orders.ship_claim'],
        'helpdesk-manager' => ['helpdeskprestashop.addresses.manage', 'helpdeskprestashop.orders.ship_claim'],
        'helpdesk-admin' => ['helpdeskprestashop.addresses.manage', 'helpdeskprestashop.orders.ship_claim'],
    ],

    'listeners' => [],

    // Países activos de la tienda (acción address.countries): cambian casi
    // nunca, así que se cachean en Laravel.
    'countries_cache_ttl' => 3600,

    'ship_claim' => [
        'types' => [
            'not_arrived' => 'No ha llegado',
            'damaged' => 'Dañado',
            'incomplete' => 'Incompleto',
        ],
        'max_attachments' => 10,
    ],
];
