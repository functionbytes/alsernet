<?php

namespace Modules\Campaign\Support;

/**
 * Verificación de la firma del webhook de Mailgun.
 *
 * Mailgun firma cada webhook con HMAC-SHA256 sobre `timestamp + token` usando la
 * "HTTP webhook signing key" de la cuenta, y envía {timestamp, token, signature}
 * en el objeto `signature` del payload.
 *
 * Política fail-open: si el servidor no tiene signing secret configurado se
 * devuelve true y la protección se apoya solo en el gate del serverUid (UUID no
 * enumerable). Al configurar el secreto, la firma pasa a ser obligatoria. Así el
 * despliegue es opt-in por servidor y no rompe la ingesta de los ya existentes.
 *
 * @param  array{timestamp?: mixed, token?: mixed, signature?: mixed}  $signature
 */
final class MailgunWebhookSignature
{
    public static function valid(?string $secret, array $signature): bool
    {
        if (empty($secret)) {
            return true;
        }

        $provided = (string) ($signature['signature'] ?? '');

        if ($provided === '') {
            return false;
        }

        $expected = hash_hmac(
            'sha256',
            (string) ($signature['timestamp'] ?? '').(string) ($signature['token'] ?? ''),
            $secret,
        );

        return hash_equals($expected, $provided);
    }
}
