<?php

namespace Modules\HelpdeskEmailActivity\Services\ProviderWebhooks;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Modules\HelpdeskEmailActivity\Contracts\EmailProviderWebhookAdapter;
use Modules\HelpdeskEmailActivity\Support\ParsedEmailEvent;

/**
 * Mailrelay no firma sus webhooks (no hay HMAC/firma criptográfica en su
 * API) — la verificación real es un token compartido en cabecera o query,
 * exactamente como ya asumía (sin usarlo bien) el
 * MailrelayWebhookController existente, que queda intacto como código
 * muerto. Formato de payload documentado por Mailrelay: uno o varios
 * eventos por request, cada uno con 'type' ('hard_bounce'|'soft_bounce'|
 * 'complaint'|...), 'email' y opcionalmente 'message_id'.
 *
 * A propósito NO se añaden aquí 'delivered'/'open': a diferencia de Mailgun/
 * Postmark/SES-SNS, el código existente nunca asumió (ni documentó) qué
 * valor exacto toma 'type' para esos dos eventos en Mailrelay — inventar un
 * literal ('delivered'/'opened'/'open'/...) sin poder confirmarlo contra la
 * API real arriesgaría silenciosamente NO matchear nunca (peor que no
 * implementarlo: parecería soportado sin estarlo). Este adapter se queda
 * solo con bounce/complaint hasta confirmar el esquema real de Mailrelay.
 */
class MailrelayWebhookAdapter implements EmailProviderWebhookAdapter
{
    public function key(): string
    {
        return 'mailrelay';
    }

    public function verify(Request $request, string $secret): bool
    {
        // $secret === '': proveedor seleccionado en Settings sin token
        // configurado todavía — sin este corte, un atacante que mande el
        // header/query vacío pasaría hash_equals('', '').
        if ($secret === '') {
            return false;
        }

        $token = $request->header('X-Mailrelay-Token') ?? $request->query('token');

        return is_string($token) && hash_equals($secret, $token);
    }

    public function handleControlMessage(Request $request): ?Response
    {
        return null;
    }

    public function parse(Request $request): array
    {
        $payload = $request->json()->all();
        $events = array_is_list($payload) ? $payload : [$payload];

        $parsed = [];

        foreach ($events as $event) {
            $type = strtolower((string) ($event['type'] ?? ''));

            if (! str_contains($type, 'bounce') && $type !== 'complaint') {
                continue;
            }

            $parsed[] = new ParsedEmailEvent(
                type: $type === 'complaint' ? 'complaint' : 'bounce',
                messageId: $event['message_id'] ?? null,
                recipient: $event['email'] ?? null,
                isHard: str_contains($type, 'hard'),
                reason: (string) ($event['reason'] ?? Str::headline($type)),
                providerEventId: $event['id'] ?? null,
                rawPayload: $event,
            );
        }

        return $parsed;
    }
}
