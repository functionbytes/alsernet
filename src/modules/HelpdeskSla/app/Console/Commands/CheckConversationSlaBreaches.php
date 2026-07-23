<?php

namespace Modules\HelpdeskSla\Console\Commands;

use Illuminate\Console\Command;
use Modules\HelpdeskSla\Services\ConversationSlaService;

class CheckConversationSlaBreaches extends Command
{
    protected $signature = 'helpdesksla:check-breaches';

    protected $description = 'Detect conversations breaching their SLA policy (first response / resolution) and broadcast alerts';

    public function handle(ConversationSlaService $slaService): int
    {
        if (! helpdesk_sla_enabled()) {
            $this->info('HelpdeskSla integration is disabled; skipping breach check.');

            return self::SUCCESS;
        }

        $count = $slaService->checkBreaches();

        $this->info("SLA check complete. {$count} breach(es) detected.");

        return self::SUCCESS;
    }
}
