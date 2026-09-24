<?php

use Illuminate\Foundation\Http\Events\RequestHandled;
use Modules\HelpdeskPrestashop\Listeners\Ext\OrderlinkRecordStoreAction;

/*
 | Extensión "orderlink": liga cada pedido de PrestaShop con la conversación
 | del helpdesk en la que se trató (tabla helpdesk_ps_order_links). Lo usa el
 | mapeo de estados (opsmap) para cambiar la conversación correcta.
 |
 | No escribe en la tienda: sin write_actions. Tampoco declara permisos
 | propios: ver/ligar exige helpdeskprestashop.orders.view + ver la
 | conversación; desligar, además, poder editarla (ConversationPolicy).
 */
return [
    'write_actions' => [],

    'permissions' => [],

    'role_permissions' => [],

    'listeners' => [
        RequestHandled::class => [OrderlinkRecordStoreAction::class],
    ],

    /*
     | Escrituras que ligan el pedido a la conversación (patrones de Str::is
     | sobre el nombre de ruta). Solo cuentan las rutas con parámetro
     | {order} y {customer}; el resto se ignora solo.
     */
    'action_routes' => [
        'manager.helpdesk.ps.*',
        'manager.helpdesk.customers.ps.*',
    ],
    'action_exclude_routes' => [
        'manager.helpdesk.ps.ext.orderlink.*',
        'manager.helpdesk.ps.ext.opsmap.*',
        'manager.helpdesk.ps.ext.opslog.*',
    ],
];
