<?php

namespace Modules\HelpdeskEmailLog\Support;

/**
 * Salida común de CUALQUIER adapter de proveedor (Mailrelay/SES-SNS/Postmark/
 * Mailgun) — cada uno traduce su payload propio a esta forma; el controlador
 * genérico (EmailProviderWebhookController) no conoce ningún formato de
 * proveedor, solo sabe iterar ParsedEmailEvent y delegar en
 * EmailBounceCorrelatorService::correlateByMessageId()/correlateByRecipient().
 *
 * $recipient es el fallback usado únicamente cuando $messageId viene vacío
 * (el proveedor no lo reenvió, o el envío original no lo llevaba) — mismo
 * criterio de "un único candidato o no se correlaciona" que ya aplica
 * BounceProcessorService para el canal IMAP.
 */
final class ParsedEmailEvent
{
    public function __construct(
        public readonly string $type, // 'bounce' | 'complaint'
        public readonly ?string $messageId,
        public readonly ?string $recipient,
        public readonly bool $isHard,
        public readonly string $reason,
        public readonly ?string $providerEventId = null,
    ) {}

    public function isBounce(): bool
    {
        return $this->type === 'bounce';
    }

    public function isComplaint(): bool
    {
        return $this->type === 'complaint';
    }
}
