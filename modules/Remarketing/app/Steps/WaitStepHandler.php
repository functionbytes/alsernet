<?php

namespace Modules\Remarketing\Steps;

use Modules\Remarketing\Contracts\StepHandlerInterface;
use Modules\Remarketing\Models\AutomationRun;

class WaitStepHandler implements StepHandlerInterface
{
    public function execute(AutomationRun $run, array $config): void
    {
        $hours = (int) ($config['hours'] ?? 0);
        $run->update(['next_step_at' => now()->addHours($hours)]);
    }
}
