<?php

namespace Modules\CampaignSendingServers\Console\Commands;

use Illuminate\Console\Command;
use Modules\CampaignSendingServers\Models\Sender;

class VerifySendersCommand extends Command
{
    protected $signature = 'campaign-sending-servers:verify-senders
                            {--uid= : Verificar sólo la identidad con este uid}';

    protected $description = 'Verifica las identidades de remitente pendientes (Amazon SES, etc.). Stub Fase 1 — implementación real en Fase 2.';

    public function handle(): int
    {
        $senders = $this->option('uid')
            ? Sender::where('uid', $this->option('uid'))->get()
            : Sender::where('status', Sender::STATUS_PENDING)->get();

        $this->info("Pendientes: {$senders->count()}");

        // TODO Fase 2: integrar AWS SDK SES (GetIdentityVerificationAttributes) o API equivalente.

        return self::SUCCESS;
    }
}
