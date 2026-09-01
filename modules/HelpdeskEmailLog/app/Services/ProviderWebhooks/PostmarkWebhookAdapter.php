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
 * único evento JSON por request, con 'RecordType' ('Bounce'|'SpamComplaint'),
 * 'MessageID' (el ID que Postmark asignó al enviar — solo correlaciona si
 * EmailLog::message_id se pobló con ESE id, es decir, si el envío real
 * saliera algún día por Postmark) y 'Email'.
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

        if (! in_array($recordType, ['Bounce', 'SpamComplaint'], true)) {
            return [];
        }

        $isComplaint = $recordType === 'SpamComplaint';
        $type = (string) ($payload['Type'] ?? '');

        return [new ParsedEmailEvent(
            type: $isComplaint ? 'complaint' : 'bounce',
            messageId: $payload['MessageID'] ?? null,
            recipient: $payload['Email'] ?? null,
            isHard: ! $isComplaint && in_array($type, ['HardBounce', 'SMTPApiError'], true),
            reason: (string) ($payload['Description'] ?? $type ?: $recordType),
            providerEventId: isset($payload['ID']) ? (string) $payload['ID'] : null,
        )];
    }
}
