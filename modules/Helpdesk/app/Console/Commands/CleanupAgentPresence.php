<?php

namespace Modules\Helpdesk\Console\Commands;

use Illuminate\Console\Command;
use Modules\Helpdesk\Services\AgentPresenceService;

class CleanupAgentPresence extends Command
{
    protected $signature = 'helpdesk:agents:cleanup-presence';

    protected $description = 'Mark agents without recent heartbeat as offline';

    public function handle(AgentPresenceService $presenceService): int
    {
        $count = $presenceService->cleanup();

        if ($count > 0) {
            $this->info("Marked {$count} agent(s) as offline due to missing heartbeat.");
        }

        return self::SUCCESS;
    }
}
