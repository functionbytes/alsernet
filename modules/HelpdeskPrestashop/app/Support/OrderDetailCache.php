<?php

namespace Modules\HelpdeskPrestashop\Support;

use Illuminate\Support\Facades\Cache;

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

    /**
     * Variante para llamadores (Contacts 360) que resuelven la propiedad del
     * pedido por external_id en vez de por email — clave independiente, no
     * pisa ni comparte invalidación con key().
     */
    public static function keyForExternalId(int $orderId, int $externalId): string
    {
        return 'ps_order_detail:'.$orderId.':ext:'.$externalId;
    }

    /**
     * Olvida ambas variantes de caché para un pedido — un mismo cliente puede
     * verse desde el inbox (clave por email) y desde Contacts 360 (clave por
     * external_id); tras una mutación ninguna de las dos debe quedar obsoleta.
     */
    public static function forget(int $orderId, ?string $email, ?int $externalId = null): void
    {
        if ($email !== null && trim($email) !== '') {
            Cache::forget(self::key($orderId, $email));
        }
        if ($externalId !== null) {
            Cache::forget(self::keyForExternalId($orderId, $externalId));
        }
    }
}
