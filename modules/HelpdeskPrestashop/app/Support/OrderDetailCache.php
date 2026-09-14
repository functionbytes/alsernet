<?php

namespace Modules\HelpdeskPrestashop\Support;

/**
 * Clave de caché del detalle de pedido PS, compartida entre
 * PsOrderDetailController (que la escribe) y PsOrderActionsController (que la
 * invalida tras cada mutación). El email se normaliza igual en ambos lados
 * para que la invalidación siempre acierte con la clave real.
 */
class OrderDetailCache
{
    public static function key(int $orderId, string $email): string
    {
        return 'ps_order_detail:'.$orderId.':'.md5(mb_strtolower(trim($email)));
    }
}
