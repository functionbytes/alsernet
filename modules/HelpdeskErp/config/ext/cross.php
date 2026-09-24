<?php

/*
 | Extensión "cross": cruce Gestión ↔ tienda (PrestaShop) y línea de tiempo
 | del cliente en la ficha de Gestión del chat. Solo lectura.
 |
 | No crea permisos nuevos: usa helpdeskerp.view / helpdeskerp.orders.view
 | (y los de cada sección para la línea de tiempo) y, para enlazar con la
 | tienda, helpdeskprestashop.orders.view.
 |
 | Rutas (routes/managers.d/cross.php):
 |   GET panel/helpdesk/customers/{customer}/erp/orders/{orderId}/shop  manager.helpdesk.erp.cross.shop-order
 |   GET panel/helpdesk/customers/{customer}/erp/timeline              manager.helpdesk.erp.cross.timeline
 */
return [
    'permissions' => [],

    'role_permissions' => [],

    'listeners' => [],

    // API REST de Gestión (InterGes). Vacío = la de la tarjeta erpbridge de
    // HelpdeskPrestashop o el ajuste `erp_api_url` del módulo Core.
    'gestion_url' => env('HELPDESK_ERP_CROSS_GESTION_URL', ''),
    'gestion_timeout' => 8,

    // Caché de pedido-cliente/?idcliente= (mapa idpedidocli → id_order PS).
    'gestion_cache_ttl' => 600,

    // Caché del cruce pedido Gestión → pedido tienda: con coincidencia y sin ella.
    'match_cache_ttl' => 1800,
    'miss_cache_ttl' => 600,

    // Último recurso (mismo día + mismo importe): solo para pedidos de estos
    // orígenes de Gestión (4 = INTERNET), con esta tolerancia en euros, entre
    // los últimos N pedidos del cliente en la tienda.
    'internet_origin_ids' => ['4'],
    'amount_tolerance' => 0.01,
    'fallback_orders' => 50,

    // Línea de tiempo: caché de la parte de la tienda (segundos), pedidos de
    // la tienda y conversaciones que se leen.
    'timeline_ps_ttl' => 120,
    'timeline_ps_orders' => 50,
    'timeline_conversations' => 30,
];
