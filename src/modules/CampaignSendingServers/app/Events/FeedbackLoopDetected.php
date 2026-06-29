<?php

namespace Modules\CampaignSendingServers\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento disparado cuando se detecta un feedback loop (spam complaint) en un buzón IMAP/POP3.
 * El módulo Campaign puede escuchar este evento para actualizar sus tracking logs.
 */
class FeedbackLoopDetected
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly ?string $email,
        public readonly ?string $messageId,
        public readonly string $description,
    ) {}
}
