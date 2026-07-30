<?php

namespace Modules\HelpdeskAgents\Observers;

use Modules\HelpdeskAgents\Concerns\InteractsWithDefaultAiAgent;
use Modules\HelpdeskAgents\Models\AiAgent;

class AiAgentObserver
{
    use InteractsWithDefaultAiAgent;

    public function saved(AiAgent $agent): void
    {
        $this->forgetDefaultAgent();
    }

    public function deleted(AiAgent $agent): void
    {
        $this->forgetDefaultAgent();
    }

    public function restored(AiAgent $agent): void
    {
        $this->forgetDefaultAgent();
    }
}
