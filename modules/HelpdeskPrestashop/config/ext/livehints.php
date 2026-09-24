<?php

use Modules\HelpdeskPrestashop\Events\PsBackInStock;
use Modules\HelpdeskPrestashop\Events\PsCartAbandoned;
use Modules\HelpdeskPrestashop\Events\PsOrderCreated;
use Modules\HelpdeskPrestashop\Events\PsOrderReturned;
use Modules\HelpdeskPrestashop\Events\PsOrderStatusChanged;
use Modules\HelpdeskPrestashop\Events\PsPriceDropped;
use Modules\HelpdeskPrestashop\Listeners\Ext\LivehintsBackInStock;
use Modules\HelpdeskPrestashop\Listeners\Ext\LivehintsCartAbandoned;
use Modules\HelpdeskPrestashop\Listeners\Ext\LivehintsOrderCreated;
use Modules\HelpdeskPrestashop\Listeners\Ext\LivehintsOrderReturned;
use Modules\HelpdeskPrestashop\Listeners\Ext\LivehintsOrderStatus;
use Modules\HelpdeskPrestashop\Listeners\Ext\LivehintsPriceDropped;

/*
 | Extensión "livehints": avisos en vivo sobre el composer (como el del
 | carrito, ps.cart.updated) cuando un webhook de PrestaShop afecta al
 | cliente de una conversación ABIERTA ahora mismo. Solo lee: no escribe en la
 | tienda, así que no hay write_actions ni permisos propios (el canal
 | helpdesk.conversation.{id} ya exige poder ver la conversación).
 */
return [
    'write_actions' => [],

    'listeners' => [
        PsBackInStock::class => [LivehintsBackInStock::class],
        PsPriceDropped::class => [LivehintsPriceDropped::class],
        PsCartAbandoned::class => [LivehintsCartAbandoned::class],
        PsOrderCreated::class => [LivehintsOrderCreated::class],
        PsOrderStatusChanged::class => [LivehintsOrderStatus::class],
        PsOrderReturned::class => [LivehintsOrderReturned::class],
    ],

    // Conmutador por tipo de aviso (todos activos por defecto).
    'hints' => [
        'back_in_stock' => (bool) env('HELPDESK_PS_LIVEHINTS_BACK_IN_STOCK', true),
        'price_dropped' => (bool) env('HELPDESK_PS_LIVEHINTS_PRICE_DROPPED', true),
        'cart_abandoned' => (bool) env('HELPDESK_PS_LIVEHINTS_CART_ABANDONED', true),
        'order_created' => (bool) env('HELPDESK_PS_LIVEHINTS_ORDER_CREATED', true),
        'order_status' => (bool) env('HELPDESK_PS_LIVEHINTS_ORDER_STATUS', true),
        'order_returned' => (bool) env('HELPDESK_PS_LIVEHINTS_ORDER_RETURNED', true),
    ],

    /*
     | Stock y bajada de precio no traen cliente: se cruzan con las
     | conversaciones abiertas (como mucho estas, las más recientes) y solo
     | avisan si el cliente tiene el producto en su lista de deseos o un aviso
     | de reposición apuntado desde el panel en los últimos stock_alert_days.
     | La lista de deseos se lee del contexto ya cacheado del panel; si no
     | está, se pregunta al puente como mucho a wishlist_bridge_lookups
     | clientes por evento y se guarda wishlist_cache_minutes.
     */
    'max_open_conversations' => (int) env('HELPDESK_PS_LIVEHINTS_MAX_OPEN', 300),
    'wishlist_bridge_lookups' => (int) env('HELPDESK_PS_LIVEHINTS_WISHLIST_LOOKUPS', 10),
    'wishlist_cache_minutes' => 10,
    'stock_alert_days' => 180,

    // El mismo aviso a la misma conversación no se repite en esta ventana
    // (reintentos del webhook, cron que vuelve a detectar el cambio).
    'dedupe_minutes' => 30,
];
