<?php

namespace Modules\CampaignSendingServers\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento disparado cuando se detecta un bounce (hard o soft) en un buzón IMAP/POP3.
 * El módulo Campaign puede escuchar este evento para actualizar sus tracking logs.
 */
class BounceDetected
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly ?string $email,
        public readonly ?string $messageId,
        public readonly bool $isHard,
        public readonly string $description,
    ) {}
}
