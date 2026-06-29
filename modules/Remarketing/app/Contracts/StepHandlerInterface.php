<?php

namespace Modules\Remarketing\Contracts;

use Modules\Remarketing\Models\AutomationRun;

interface StepHandlerInterface
{
    /**
     * Execute this step for the given run using the step's config.
     */
    public function execute(AutomationRun $run, array $config): void;
}
