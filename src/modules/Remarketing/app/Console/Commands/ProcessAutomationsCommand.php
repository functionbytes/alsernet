<?php

namespace Modules\Remarketing\Console\Commands;

use Illuminate\Console\Command;
use Modules\Remarketing\Jobs\EvaluateAutomationJob;
use Modules\Remarketing\Models\AutomationRun;

class ProcessAutomationsCommand extends Command
{
    protected $signature = 'remarketing:process-automations';

    protected $description = 'Dispatch evaluation jobs for ready automation runs';

    public function handle(): int
    {
        $count = 0;

        AutomationRun::query()
            ->where('status', 'running')
            ->where(function ($q) {
                $q->whereNull('next_step_at')->orWhere('next_step_at', '<=', now());
            })
            ->cursor()
            ->each(function (AutomationRun $run) use (&$count) {
                EvaluateAutomationJob::dispatch($run);
                $count++;
            });

        $this->info("Dispatched {$count} automation runs");

        return self::SUCCESS;
    }
}
