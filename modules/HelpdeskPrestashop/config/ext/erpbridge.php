<?php

/**
 * Extensión "erpbridge" — tarjeta "En Gestión (ERP)" del workspace de pedido.
 *
 * Para un pedido de PrestaShop muestra el pedido correspondiente en Gestión
 * (Oracle) y, si Gestión la expone, su factura fiscal. SOLO lecturas:
 *
 *   - Bridge PS (order.detail con lookup de propiedad) → el pedido es del
 *     cliente y da su id_customer.
 *   - API REST de Gestión (InterGes, `erp_api_url`):
 *       pedido-cliente/?identificadororigen={id_order}      cabecera + líneas
 *       pedido-cliente-hist/?identificadororigen={id_order} historial de estados
 *       pedido-cliente-tracking/?identificadororigen={id}   seguimiento
 *     `identificadororigen` es el id_order de PrestaShop: lo manda
 *     AlvarezERP::construirdatospedido() al pasar el pedido a Gestión.
 *   - Manager (proxy Oracle de HelpdeskErp, `helpdeskErp.manager_url`):
 *       customer/search/web/{id_customer}   cliente ERP con CODIGO_INTERNET = id_customer PS
 *       customer/{id}/orders/{central}      fechas, origen, facturado
 *       customer/{id}/delivery-notes        albaranes (idfacturacli)
 *       customer/{id}/invoices[/{id}]       FACTURACLI_CENTRAL
 *
 * Nada escribe ni en PrestaShop ni en Gestión.
 */
return [
    'write_actions' => [],

    'permissions' => [
        // Aparte de orders.view: enseña datos de Gestión (estado real de
        // almacén, albarán, factura fiscal) que no están en la tienda.
        'helpdeskprestashop.orders.erp' => 'Ver el pedido y la factura de Gestión (ERP) de un pedido PrestaShop en el workspace de pedido',
    ],

    // Solo lectura: mismos roles que ya ven el contexto ERP en el inbox.
    'role_permissions' => [
        'helpdesk-agent' => ['helpdeskprestashop.orders.erp'],
        'helpdesk-supervisor' => ['helpdeskprestashop.orders.erp'],
        'helpdesk-manager' => ['helpdeskprestashop.orders.erp'],
        'helpdesk-admin' => ['helpdeskprestashop.orders.erp'],
    ],

    'listeners' => [],

    // URL de la API REST de Gestión. Vacío = ajuste `erp_api_url` (módulo
    // Core, p. ej. http://192.168.253.8:8080/api-gestion). Solo se usan
    // esquema, host y puerto; la ruta /api-gestion/ la pone el cliente.
    'gestion_url' => env('HELPDESK_PS_ERPBRIDGE_GESTION_URL', ''),

    // Segundos de espera por llamada. La API de Gestión responde en <1 s y
    // el manager en 0,5-3 s; con más, el workspace se quedaría colgado.
    'gestion_timeout' => 8,
    'manager_timeout' => 12,

    // La tarjeta se reabre con cada pedido: se cachea la respuesta completa
    // un rato corto para no repetir 6-7 lecturas contra Oracle.
    'cache_ttl' => 120,

    // Margen (segundos) entre la hora a la que Gestión marcó el pedido como
    // servido (pedido-cliente-hist, estado 7/10) y la creación del albarán
    // para darlos por el mismo movimiento. En datos reales coinciden al
    // segundo (pedido 816880: 2026-02-26 08:15:58 en ambos).
    'delivery_note_match_seconds' => 180,

    // Estados de PEDIDOCLI en Gestión. Fuente: el script real de sincronía
    // de estados de la tienda (alvarez scripts/coding/seguimiento_pedido.php),
    // que es quien traduce estos códigos a estados de PrestaShop.
    'states' => [
        0 => 'Anulado',
        1 => 'En creación',
        2 => 'Revisión transportista',
        3 => 'Aceptación financiera',
        4 => 'Pendiente de mercancía',
        5 => 'Listo para servir',
        6 => 'Sirviéndose',
        7 => 'Servido',
        8 => 'Incidencia',
        9 => 'Aceptación financiera (reservando)',
        10 => 'Servido parcialmente',
        11 => 'Pendiente de transferencia bancaria',
    ],

    // Estados que implican que el almacén ya ha sacado mercancía.
    'served_states' => [7, 10],
];
