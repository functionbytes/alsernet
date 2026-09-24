<?php

/*
 | Extensión "refunds": reembolso parcial (pieza 08), resolver devolución
 | (pieza 35) e instrucciones de retorno (pieza 36) del documento "Alvarez
 | PrestaShop en el Chat". Accesible en config('helpdeskprestashop.ext.refunds.*').
 */

return [
    // Acciones del puente (helpers/ext/refunds.php) que escriben en la tienda:
    // viajan con Idempotency-Key y nunca se cachean.
    'write_actions' => [
        'refunds.issue_partial',
        'refunds.rma_set_state',
    ],

    'permissions' => [
        'helpdeskprestashop.refunds.issue' => 'Emitir reembolsos parciales en PrestaShop (hasta el límite de agente)',
        'helpdeskprestashop.refunds.approve' => 'Emitir reembolsos parciales por encima del límite de agente',
        'helpdeskprestashop.returns.resolve' => 'Cambiar el estado de las devoluciones (RMA) de PrestaShop',
    ],

    // Mismo reparto que los vales: el agente reembolsa hasta su límite y
    // supervisores/responsables hasta el suyo. helpdesk-agent-restricted no
    // escribe en la tienda, así que no aparece.
    'role_permissions' => [
        'helpdesk-agent' => ['helpdeskprestashop.refunds.issue', 'helpdeskprestashop.returns.resolve'],
        'helpdesk-supervisor' => ['helpdeskprestashop.refunds.issue', 'helpdeskprestashop.refunds.approve', 'helpdeskprestashop.returns.resolve'],
        'helpdesk-manager' => ['helpdeskprestashop.refunds.issue', 'helpdeskprestashop.refunds.approve', 'helpdeskprestashop.returns.resolve'],
        'helpdesk-admin' => ['helpdeskprestashop.refunds.issue', 'helpdeskprestashop.refunds.approve', 'helpdeskprestashop.returns.resolve'],
    ],

    'listeners' => [],

    /*
     | Límite por reembolso (con IVA, lo que sale de caja) según permiso:
     | refunds.issue usa agent_limit; refunds.approve usa approver_limit. El
     | puente vuelve a comprobar el tope con el importe que calcula PrestaShop.
     */
    'agent_limit' => (float) env('HELPDESK_PS_REFUND_AGENT_LIMIT', 50),
    'approver_limit' => (float) env('HELPDESK_PS_REFUND_APPROVER_LIMIT', 500),

    /*
     | Estados de devolución de PrestaShop con significado propio en el modal
     | "Resolver". Son los ids del core, comprobados en aalv_order_return_state:
     | 1 A la espera de confirmación · 2 A la espera del paquete · 3 Paquete
     | recibido · 4 Devolución denegada · 5 Devolución completada.
     */
    'rma_states' => [
        'pending' => 1,
        'approved' => 2,
        'received' => 3,
        'denied' => 4,
        'completed' => 5,
    ],

    /*
     | Pieza 36 · Etiqueta de retorno. Ningún módulo de transportista de la
     | tienda (seur, correosexpress) genera etiquetas de DEVOLUCIÓN por API,
     | así que el bloque muestra instrucciones de retorno configurables y las
     | escribe en el chat. Si un día hay integración, este bloque se sustituye.
     | La dirección no tiene valor por defecto a propósito (la tienda tampoco
     | tiene PS_SHOP_ADDR1): sin ella el bloque pide configurarla en vez de
     | mandar al cliente una dirección inventada.
     */
    'return_instructions' => [
        'carrier' => env('HELPDESK_PS_RETURN_CARRIER', 'Sin etiqueta automática · lo envía el cliente'),
        // Varias líneas separadas por "|" (el .env no admite saltos de línea).
        'address' => env('HELPDESK_PS_RETURN_ADDRESS', ''),
        'validity_days' => (int) env('HELPDESK_PS_RETURN_VALIDITY_DAYS', 14),
        'steps' => [
            'Empaqueta los artículos en su caja original con todos sus accesorios.',
            'Incluye dentro un papel con el número de devolución :rma y el del pedido :order.',
            'Envíalo a la dirección de devoluciones antes de :days días.',
            'Cuando lo recibamos lo revisaremos y te avisaremos del reembolso.',
        ],
    ],
];
