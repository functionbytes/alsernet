<?php

namespace Modules\Helpdesk\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\WorkflowRun;
use Modules\Helpdesk\Services\Workflow\WorkflowEngine;

class RunWorkflowJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public int $backoff = 10;

    public function __construct(
        private readonly WorkflowRun $run,
    ) {
        $this->onQueue('helpdesk');
    }

    public function handle(WorkflowEngine $engine): void
    {
        $engine->runWorkflow($this->run->fresh() ?? $this->run);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('RunWorkflowJob: job failed', [
            'run_id' => $this->run->id,
            'workflow_id' => $this->run->workflow_id,
            'error' => $exception->getMessage(),
        ]);
    }
}
