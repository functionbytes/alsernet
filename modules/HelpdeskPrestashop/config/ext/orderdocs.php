<?php

/**
 * Extensión "orderdocs" — piezas 20 (notas del pedido) y 22 (documentos del
 * pedido) del documento "PrestaShop en el chat".
 *
 * Solo lecturas en el bridge (orderdocs.notes / orderdocs.list /
 * orderdocs.pdf): crear notas sigue siendo order.add_note, ya en la lista
 * nativa de escrituras y con su permiso helpdeskprestashop.orders.manage.
 */
return [
    'write_actions' => [],

    'permissions' => [
        // Aparte de orders.view: el PDF lleva DNI, direcciones e importes y se
        // puede sacar del panel (descarga) o mandar al cliente por el chat.
        'helpdeskprestashop.orders.documents' => 'Descargar y enviar por el chat los PDF de pedidos PrestaShop (albarán, nota de crédito, factura)',
    ],

    // helpdesk-agent-restricted solo consulta: no descarga ni envía documentos.
    'role_permissions' => [
        'helpdesk-agent' => ['helpdeskprestashop.orders.documents'],
        'helpdesk-supervisor' => ['helpdeskprestashop.orders.documents'],
        'helpdesk-manager' => ['helpdeskprestashop.orders.documents'],
        'helpdesk-admin' => ['helpdeskprestashop.orders.documents'],
    ],

    'listeners' => [],

    // Un albarán o una nota de crédito de PrestaShop rondan los 10 KB; el tope
    // solo corta respuestas anómalas antes de servirlas o adjuntarlas.
    'pdf_max_bytes' => 8 * 1024 * 1024,
];
