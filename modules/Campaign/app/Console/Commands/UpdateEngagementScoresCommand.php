<?php

namespace Modules\Campaign\Console\Commands;

use Illuminate\Console\Command;
use Modules\Campaign\Services\EngagementScorer;

class UpdateEngagementScoresCommand extends Command
{
    protected $signature = 'campaign:update-engagement-scores {--limit=1000 : Suscriptores a procesar por ejecución}';

    protected $description = 'Recalcula engagement scores de suscriptores';

    public function handle(): int
    {
        $scorer = new EngagementScorer;
        $processed = $scorer->scoreBatch((int) $this->option('limit'));
        $this->info("{$processed} suscriptores actualizados.");

        return self::SUCCESS;
    }
}
