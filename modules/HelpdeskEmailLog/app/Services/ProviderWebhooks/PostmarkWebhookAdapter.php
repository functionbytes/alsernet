<?php

namespace Modules\HelpdeskEmailLog\Services\ProviderWebhooks;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\HelpdeskEmailLog\Contracts\EmailProviderWebhookAdapter;
use Modules\HelpdeskEmailLog\Support\ParsedEmailEvent;

/**
 * Postmark tampoco firma sus webhooks nativamente (a diferencia de Mailgun) —
 * su propia documentación recomienda Basic Auth en la URL o un token propio;
 * aquí se exige el mismo token compartido en cabecera que Mailrelay, por
 * consistencia con el resto del conector. Payload real de Postmark: un
 * único evento JSON por request, con 'RecordType'
 * ('Bounce'|'SpamComplaint'|'Delivery'|'Open'), 'MessageID' (el ID que
 * Postmark asignó al enviar — solo correlaciona si EmailLog::message_id se
 * pobló con ESE id, es decir, si el envío real saliera algún día por
 * Postmark). El campo con el destinatario cambia de nombre según el tipo:
 * 'Email' en Bounce/SpamComplaint, 'Recipient' en Delivery/Open — se intentan
 * ambos. En 'Open', Postmark adjunta 'Geo.IP' y 'UserAgent' en el propio
 * evento, se toman de ahí para EmailLogOpen.
 */
class PostmarkWebhookAdapter implements EmailProviderWebhookAdapter
{
    public function key(): string
    {
        return 'postmark';
    }

    public function verify(Request $request, string $secret): bool
    {
        // $secret === '': proveedor seleccionado en Settings sin token
        // configurado todavía — sin este corte, un atacante que mande la
        // cabecera vacía pasaría hash_equals('', '').
        if ($secret === '') {
            return false;
        }

        $token = $request->header('X-Postmark-Webhook-Token');

        return is_string($token) && hash_equals($secret, $token);
    }

    public function handleControlMessage(Request $request): ?Response
    {
        return null;
    }

    public function parse(Request $request): array
    {
        $payload = $request->json()->all();
        $recordType = (string) ($payload['RecordType'] ?? '');

        $type = match ($recordType) {
            'Bounce' => 'bounce',
            'SpamComplaint' => 'complaint',
            'Delivery' => 'delivered',
            'Open' => 'open',
            default => null,
        };

        if ($type === null) {
            return [];
        }

        $bounceType = (string) ($payload['Type'] ?? '');

        return [new ParsedEmailEvent(
            type: $type,
            messageId: $payload['MessageID'] ?? null,
            recipient: $payload['Email'] ?? $payload['Recipient'] ?? null,
            isHard: $type === 'bounce' && in_array($bounceType, ['HardBounce', 'SMTPApiError'], true),
            reason: (string) ($payload['Description'] ?? $bounceType ?: $recordType),
            providerEventId: isset($payload['ID']) ? (string) $payload['ID'] : null,
            ip: $type === 'open' ? ($payload['Geo']['IP'] ?? null) : null,
            userAgent: $type === 'open' ? ($payload['UserAgent'] ?? null) : null,
            rawPayload: $payload,
        )];
    }
}
