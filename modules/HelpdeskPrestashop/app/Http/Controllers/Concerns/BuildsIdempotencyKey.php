<?php

namespace Modules\HelpdeskPrestashop\Http\Controllers\Concerns;

use Illuminate\Http\Request;

/**
 * Clave de idempotencia determinista para acciones mutadoras de pedido: si el
 * cliente HTTP no manda la cabecera Idempotency-Key, se deriva de
 * usuario+pedido+acción+payload, así un doble envío accidental (doble clic,
 * reintento de red) no duplica la mutación en PrestaShop.
 */
trait BuildsIdempotencyKey
{
    private function idempotencyKey(Request $request, int $order, string $action, array $payload = []): string
    {
        $header = trim((string) $request->header('Idempotency-Key', ''));

        if ($header !== '') {
            return $header;
        }

        return sha1(sprintf(
            '%s:%d:%s:%s',
            (string) ($request->user()?->getAuthIdentifier() ?? 'anon'),
            $order,
            $action,
            json_encode($payload),
        ));
    }
}
