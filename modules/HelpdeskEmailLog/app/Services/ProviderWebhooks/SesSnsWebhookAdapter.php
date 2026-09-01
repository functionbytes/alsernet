<?php

namespace Modules\HelpdeskEmailLog\Services\ProviderWebhooks;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Support\OutboundUrlGuard;
use Modules\HelpdeskEmailLog\Contracts\EmailProviderWebhookAdapter;
use Modules\HelpdeskEmailLog\Support\ParsedEmailEvent;

/**
 * SES no entrega webhooks directamente — entrega notificaciones SES vía SNS
 * (Simple Notification Service), que envuelve el evento real en un sobre
 * propio ('Type', 'Message' como JSON-string anidado, 'Signature'). A
 * diferencia de Mailrelay/Postmark, SNS SÍ firma criptográficamente cada
 * mensaje (RSA sobre una cadena canónica) — se verifica de verdad, sin SDK de
 * AWS instalado (ninguno lo está, ver plan): se construye la cadena canónica
 * a mano y se valida con openssl_verify() contra el certificado que el
 * propio mensaje referencia (SigningCertURL), igual que documenta AWS.
 *
 * El campo 'secret' de este proveedor en Settings no se usa para firmar (SNS
 * no tiene "secreto compartido" configurable) — se ignora aquí a propósito;
 * la autenticidad viene solo de la firma RSA + el guard de host amazonaws.com.
 */
class SesSnsWebhookAdapter implements EmailProviderWebhookAdapter
{
    public function key(): string
    {
        return 'ses';
    }

    public function verify(Request $request, string $secret): bool
    {
        $payload = $request->json()->all();

        $signingCertUrl = (string) ($payload['SigningCertURL'] ?? '');

        if (! $this->isAmazonHost($signingCertUrl) || ! OutboundUrlGuard::isSafe($signingCertUrl)) {
            return false;
        }

        $canonical = $this->canonicalString($payload);

        if ($canonical === null) {
            return false;
        }

        $signature = base64_decode((string) ($payload['Signature'] ?? ''), true);

        if ($signature === false || $signature === '') {
            return false;
        }

        $cert = $this->fetchCert($signingCertUrl);

        if ($cert === null) {
            return false;
        }

        $publicKey = openssl_pkey_get_public($cert);

        if ($publicKey === false) {
            return false;
        }

        $algo = ((string) ($payload['SignatureVersion'] ?? '1')) === '2' ? OPENSSL_ALGO_SHA256 : OPENSSL_ALGO_SHA1;

        return openssl_verify($canonical, $signature, $publicKey, $algo) === 1;
    }

    public function handleControlMessage(Request $request): ?Response
    {
        $payload = $request->json()->all();
        $type = (string) ($payload['Type'] ?? '');

        if ($type === 'UnsubscribeConfirmation') {
            return response('unsubscribe acknowledged', 200);
        }

        if ($type !== 'SubscriptionConfirmation') {
            return null;
        }

        $subscribeUrl = (string) ($payload['SubscribeURL'] ?? '');

        if (! $this->isAmazonHost($subscribeUrl) || ! OutboundUrlGuard::isSafe($subscribeUrl)) {
            Log::warning('helpdeskemaillog: SubscribeURL de SNS con host no confiable, ignorado', ['url' => $subscribeUrl]);

            return response('rejected', 400);
        }

        try {
            Http::timeout(10)->get($subscribeUrl);
        } catch (\Throwable $e) {
            Log::warning('helpdeskemaillog: fallo al confirmar suscripción SNS', ['error' => $e->getMessage()]);

            return response('confirmation request failed', 502);
        }

        return response('subscribed', 200);
    }

    public function parse(Request $request): array
    {
        $payload = $request->json()->all();

        if (($payload['Type'] ?? null) !== 'Notification') {
            return [];
        }

        $message = json_decode((string) ($payload['Message'] ?? ''), true);

        if (! is_array($message)) {
            return [];
        }

        $eventType = (string) ($message['eventType'] ?? $message['notificationType'] ?? '');
        $messageId = $message['mail']['commonHeaders']['messageId'] ?? null;

        // Payload crudo guardado para depuración: el mensaje SES decodificado
        // tal cual, más el sobre SNS mínimo necesario para contexto —
        // deliberadamente SIN 'Signature'/'SigningCertURL' (material
        // criptográfico ya verificado en verify(), sin valor de depuración
        // aquí; ver WebhookPayloadRedactor, que igualmente los quitaría si
        // hubieran quedado).
        $rawPayload = [
            'sns' => [
                'Type' => $payload['Type'] ?? null,
                'MessageId' => $payload['MessageId'] ?? null,
                'TopicArn' => $payload['TopicArn'] ?? null,
                'Timestamp' => $payload['Timestamp'] ?? null,
            ],
            'message' => $message,
        ];

        if ($eventType === 'Bounce') {
            $bounce = $message['bounce'] ?? [];
            $recipient = $bounce['bouncedRecipients'][0]['emailAddress'] ?? null;

            return [new ParsedEmailEvent(
                type: 'bounce',
                messageId: is_string($messageId) ? $messageId : null,
                recipient: $recipient,
                isHard: ($bounce['bounceType'] ?? null) === 'Permanent',
                reason: (string) ($bounce['bouncedRecipients'][0]['diagnosticCode'] ?? $bounce['bounceSubType'] ?? 'SES bounce'),
                providerEventId: $message['mail']['messageId'] ?? null,
                rawPayload: $rawPayload,
            )];
        }

        if ($eventType === 'Complaint') {
            $complaint = $message['complaint'] ?? [];
            $recipient = $complaint['complainedRecipients'][0]['emailAddress'] ?? null;

            return [new ParsedEmailEvent(
                type: 'complaint',
                messageId: is_string($messageId) ? $messageId : null,
                recipient: $recipient,
                isHard: false,
                reason: (string) ($complaint['complaintFeedbackType'] ?? 'SES complaint'),
                providerEventId: $message['mail']['messageId'] ?? null,
                rawPayload: $rawPayload,
            )];
        }

        if ($eventType === 'Delivery') {
            $delivery = $message['delivery'] ?? [];
            // 'delivery.recipients' es una lista de strings (no de objetos,
            // a diferencia de bounce/complaint) — así lo documenta AWS.
            $recipient = $delivery['recipients'][0] ?? null;

            return [new ParsedEmailEvent(
                type: 'delivered',
                messageId: is_string($messageId) ? $messageId : null,
                recipient: is_string($recipient) ? $recipient : null,
                isHard: false,
                reason: (string) ($delivery['smtpResponse'] ?? 'SES delivery'),
                providerEventId: $message['mail']['messageId'] ?? null,
                rawPayload: $rawPayload,
            )];
        }

        // SES no notifica 'Open' de forma nativa vía Event Publishing salvo
        // que se habilite "engagement tracking" (dominio de tracking propio,
        // reescritura de contenido) en el configuration set — un opt-in
        // adicional que este conector no asume configurado. No se añade
        // ningún caso aquí a propósito: el pixel propio del módulo sigue
        // siendo el único origen de aperturas para SES.
        return [];
    }

    private function isAmazonHost(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        // Restringido al host exacto que SNS usa para firmar mensajes
        // (sns.<region>.amazonaws.com) — un simple sufijo ".amazonaws.com"
        // lo satisface cualquier bucket S3 público (p.ej.
        // "evil-bucket.s3.amazonaws.com"), permitiendo a un tercero alojar
        // su propio certificado y pasar openssl_verify().
        return is_string($host) && (bool) preg_match('/^sns\.[a-z0-9-]+\.amazonaws\.com$/i', $host);
    }

    /**
     * @return string|null PEM del certificado, o null si la descarga falla.
     *                     Cacheado 1h: es el mismo certificado de firma para
     *                     todos los mensajes de una misma cuenta/región
     *                     durante mucho tiempo.
     */
    private function fetchCert(string $url): ?string
    {
        return Cache::remember(
            'helpdeskemaillog:sns_cert:'.md5($url),
            now()->addHour(),
            function () use ($url) {
                try {
                    $response = Http::timeout(10)->get($url);
                } catch (\Throwable) {
                    return null;
                }

                return $response->successful() ? $response->body() : null;
            }
        );
    }

    /**
     * Cadena canónica AWS SNS (SignatureVersion 1/2): pares clave-valor
     * ordenados alfabéticamente entre los campos presentes en el mensaje,
     * cada uno como "Clave\nValor\n" — formato distinto según el mensaje sea
     * una Notification o una (Un)SubscriptionConfirmation.
     */
    private function canonicalString(array $payload): ?string
    {
        $type = (string) ($payload['Type'] ?? '');

        $fields = match ($type) {
            'Notification' => ['Message', 'MessageId', 'Subject', 'Timestamp', 'TopicArn', 'Type'],
            'SubscriptionConfirmation', 'UnsubscribeConfirmation' => ['Message', 'MessageId', 'SubscribeURL', 'Timestamp', 'Token', 'TopicArn', 'Type'],
            default => null,
        };

        if ($fields === null) {
            return null;
        }

        $canonical = '';

        foreach ($fields as $field) {
            // 'Subject' es opcional en Notification — se omite del todo si no
            // vino, no se incluye como línea vacía (así lo exige AWS).
            if (! array_key_exists($field, $payload)) {
                continue;
            }

            $canonical .= $field."\n".$payload[$field]."\n";
        }

        return $canonical;
    }
}
