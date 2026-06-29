<?php

namespace Modules\Campaign\Console\Commands;

use Illuminate\Console\Command;
use Modules\Campaign\Models\Campaign;

class ExecuteScheduledCampaignsCommand extends Command
{
    protected $signature = 'campaign:execute-scheduled';

    protected $description = 'Lanza las campañas con status "scheduled" cuyo run_at ya ha pasado.';

    public function handle(): int
    {
        $this->info('Verificando campañas programadas…');

        // Reusa el lock interno de BaseCampaign::checkAndExecuteScheduledCampaigns
        Campaign::checkAndExecuteScheduledCampaigns();

        return self::SUCCESS;
    }
}
