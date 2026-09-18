<?php

namespace Modules\HelpdeskEmailActivity\Support;

/**
 * Salida común de CUALQUIER adapter de proveedor (Mailrelay/SES-SNS/Postmark/
 * Mailgun) — cada uno traduce su payload propio a esta forma; el controlador
 * genérico (EmailProviderWebhookController) no conoce ningún formato de
 * proveedor, solo sabe iterar ParsedEmailEvent y delegar en
 * EmailBounceCorrelatorService (bounce/complaint) o
 * EmailDeliveryEventCorrelatorService (delivered/open).
 *
 * $recipient es el fallback usado únicamente cuando $messageId viene vacío
 * (el proveedor no lo reenvió, o el envío original no lo llevaba) — mismo
 * criterio de "un único candidato o no se correlaciona" que ya aplica
 * BounceProcessorService para el canal IMAP.
 *
 * $ip/$userAgent solo se rellenan para $type === 'open' (dato que el
 * proveedor adjunta al evento de apertura; ver cada adapter) — siempre null
 * para el resto de tipos.
 *
 * $rawPayload es la porción del payload original que corresponde
 * ÚNICAMENTE a este evento (nunca el request completo) — para Mailgun/
 * Postmark/SES-SNS coincide con el propio request porque ya traen un solo
 * evento por petición; para Mailrelay, que sí puede traer varios eventos en
 * un mismo array, es el objeto individual dentro del batch. Ese matiz
 * importa: EmailProviderWebhookController lo guarda en
 * email_provider_events.payload para depuración, y
 * WebhookEventsController::reprocess() reconstruye un ParsedEmailEvent
 * desde ahí — si aquí hubiera quedado el batch entero, reprocesar una fila
 * de un batch de Mailrelay habría sido ambiguo (¿cuál de los varios eventos
 * del array corresponde a esta fila?).
 */
final class ParsedEmailEvent
{
    /**
     * @param  array<string, mixed>  $rawPayload
     */
    public function __construct(
        public readonly string $type, // 'bounce' | 'complaint' | 'delivered' | 'open'
        public readonly ?string $messageId,
        public readonly ?string $recipient,
        public readonly bool $isHard,
        public readonly string $reason,
        public readonly ?string $providerEventId = null,
        public readonly ?string $ip = null,
        public readonly ?string $userAgent = null,
        public readonly array $rawPayload = [],
    ) {}

    public function isBounce(): bool
    {
        return $this->type === 'bounce';
    }

    public function isComplaint(): bool
    {
        return $this->type === 'complaint';
    }

    public function isDelivered(): bool
    {
        return $this->type === 'delivered';
    }

    public function isOpen(): bool
    {
        return $this->type === 'open';
    }
}
