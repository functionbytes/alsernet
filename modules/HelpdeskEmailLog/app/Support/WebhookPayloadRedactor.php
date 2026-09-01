<?php

namespace Modules\HelpdeskEmailLog\Support;

use Modules\Core\Models\Setting;

/**
 * Prepara el payload crudo de un webhook de proveedor (Mailrelay/SES-SNS/
 * Postmark/Mailgun) para guardarlo en email_provider_events.payload — ver
 * config('helpdeskemaillog.webhook_payload_max_bytes'/'webhook_payload_redact_keys')
 * para el criterio completo de qué se quita y por qué.
 *
 * A propósito NO toca 'recipient'/'email'/'Email'/'Recipient'/'message_id'/
 * 'MessageID'/'ip'/'IP'/'UserAgent': son exactamente los campos que hay que
 * poder comparar cuando un evento no correlacionó, la razón de ser de esta
 * tabla.
 */
class WebhookPayloadRedactor
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function redact(array $payload): array
    {
        $redacted = self::stripKeys($payload, self::redactedKeys());

        $maxBytes = (int) Setting::get('helpdeskemaillog.webhook_payload_max_bytes', config('helpdeskemaillog.webhook_payload_max_bytes'));
        $encoded = json_encode($redacted);

        if ($maxBytes <= 0 || $encoded === false || strlen($encoded) <= $maxBytes) {
            return $redacted;
        }

        // Un JSON no se puede cortar por bytes sin arriesgar dejarlo
        // inválido (a diferencia de bodyOf()/truncate() con texto plano) —
        // se sustituye entero por un marcador en vez de intentarlo.
        return ['_truncated' => true, 'original_size' => strlen($encoded)];
    }

    /**
     * @return list<string>
     */
    private static function redactedKeys(): array
    {
        return (array) Setting::get('helpdeskemaillog.webhook_payload_redact_keys', config('helpdeskemaillog.webhook_payload_redact_keys', []));
    }

    /**
     * Recorre el array recursivamente y elimina cualquier clave presente en
     * $keys, a cualquier nivel de anidamiento (los proveedores anidan el
     * evento real bajo 'event-data'/'Message'/etc., y la clave a redactar
     * puede vivir en cualquiera de esos niveles).
     *
     * @param  array<string, mixed>  $data
     * @param  list<string>  $keys
     * @return array<string, mixed>
     */
    private static function stripKeys(array $data, array $keys): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            if (is_string($key) && in_array($key, $keys, true)) {
                continue;
            }

            $result[$key] = is_array($value) ? self::stripKeys($value, $keys) : $value;
        }

        return $result;
    }
}
