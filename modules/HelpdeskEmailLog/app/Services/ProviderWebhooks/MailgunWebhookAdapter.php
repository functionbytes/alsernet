<?php

namespace Modules\HelpdeskEmailLog\Services\ProviderWebhooks;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\HelpdeskEmailLog\Contracts\EmailProviderWebhookAdapter;
use Modules\HelpdeskEmailLog\Support\ParsedEmailEvent;

/**
 * Mailgun sí firma de verdad (a diferencia de Mailrelay/Postmark): HMAC-SHA256
 * sobre timestamp+token con la "webhook signing key" de la cuenta — mecanismo
 * documentado oficialmente, se implementa tal cual (no un token plano
 * inventado como los otros dos adapters).
 *
 * event-data.event nativo de Mailgun cubre, entre otros, 'delivered' y
 * 'opened' además de 'failed'/'complained' — los cuatro se procesan aquí. En
 * 'opened', Mailgun adjunta 'ip' y 'client-info.user-agent' en el propio
 * evento (mismo payload que usa para 'clicked'), se toman de ahí para
 * EmailLogOpen.
 */
class MailgunWebhookAdapter implements EmailProviderWebhookAdapter
{
    public function key(): string
    {
        return 'mailgun';
    }

    public function verify(Request $request, string $secret): bool
    {
        $signature = $request->input('signature') ?? [];
        $timestamp = (string) ($signature['timestamp'] ?? '');
        $token = (string) ($signature['token'] ?? '');
        $digest = (string) ($signature['signature'] ?? '');

        if ($timestamp === '' || $token === '' || $digest === '' || $secret === '') {
            // $secret === '': proveedor seleccionado en Settings sin secreto
            // configurado todavía — sin este corte, hash_hmac(..., '') es
            // trivialmente reproducible por cualquiera (la clave HMAC no
            // sería un secreto real), permitiendo falsificar bounces/quejas.
            return false;
        }

        // Ventana de 5 minutos: mismo margen que usan otros webhooks HMAC del
        // proyecto (Meta/WhatsApp) contra replay de una firma capturada.
        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.$token, $secret);

        return hash_equals($expected, $digest);
    }

    public function handleControlMessage(Request $request): ?Response
    {
        return null;
    }

    public function parse(Request $request): array
    {
        $eventData = $request->input('event-data') ?? [];
        $event = (string) ($eventData['event'] ?? '');

        $type = match ($event) {
            'failed' => 'bounce',
            'complained' => 'complaint',
            'delivered' => 'delivered',
            'opened' => 'open',
            default => null,
        };

        if ($type === null) {
            return [];
        }

        $severity = (string) ($eventData['severity'] ?? '');
        $messageId = $eventData['message']['headers']['message-id'] ?? null;

        return [new ParsedEmailEvent(
            type: $type,
            messageId: is_string($messageId) ? trim($messageId, '<>') : null,
            recipient: $eventData['recipient'] ?? null,
            isHard: $type === 'bounce' && $severity === 'permanent',
            reason: (string) ($eventData['delivery-status']['message'] ?? $eventData['reason'] ?? $event),
            providerEventId: $eventData['id'] ?? null,
            ip: $type === 'open' ? ($eventData['ip'] ?? null) : null,
            userAgent: $type === 'open' ? ($eventData['client-info']['user-agent'] ?? null) : null,
        )];
    }
}
