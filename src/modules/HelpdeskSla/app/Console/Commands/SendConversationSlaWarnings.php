<?php

namespace Modules\HelpdeskSla\Console\Commands;

use Illuminate\Console\Command;
use Modules\HelpdeskSla\Services\ConversationSlaService;

class SendConversationSlaWarnings extends Command
{
    protected $signature = 'helpdesksla:send-warnings';

    protected $description = 'Warn about open conversations approaching their SLA deadline (before they breach)';

    public function handle(ConversationSlaService $slaService): int
    {
        $count = $slaService->sendWarnings();

        $this->info("SLA warnings sent: {$count}.");

        return self::SUCCESS;
    }
}
