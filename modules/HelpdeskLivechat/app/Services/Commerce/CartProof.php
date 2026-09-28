<?php

namespace Modules\HelpdeskLivechat\Services\Commerce;

/**
 * Prueba firmada por la tienda (alsernetbridge, widgetcontext) de que una
 * sesión del navegador posee una cesta: base64url({c, u, g, d, e}).hmac, con
 * la clave derivada del secreto compartido del bridge. Sin ella, el cart_id
 * del latido lo inventa el navegador (los ids son consecutivos) y se podría
 * reclamar la atribución de ventas ajenas.
 */
final class CartProof
{
    /**
     * Id de cesta que prueba el token, o null si no es válido o caducó.
     */
    public static function cartId(?string $token): ?int
    {
        $secret = (string) config('helpdeskprestashop.webhook_secret', '');
        if ($token === null || $token === '' || $secret === '' || strlen($token) > 512) {
            return null;
        }

        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return null;
        }
        [$body, $signature] = $parts;

        $key = hash_hmac('sha256', 'alsernet-guest-cart-token:v1', $secret);
        if (! hash_equals(hash_hmac('sha256', $body, $key), $signature)) {
            return null;
        }

        $data = json_decode((string) base64_decode(strtr($body, '-_', '+/')), true);
        if (! is_array($data) || (int) ($data['e'] ?? 0) < time() || (int) ($data['c'] ?? 0) <= 0) {
            return null;
        }

        return (int) $data['c'];
    }
}
