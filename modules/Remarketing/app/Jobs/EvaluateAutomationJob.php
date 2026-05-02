<?php

namespace Modules\Remarketing\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Remarketing\Models\AutomationRun;
use Modules\Remarketing\Services\AutomationService;

class EvaluateAutomationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public int $backoff = 10;

    public function __construct(
        public readonly AutomationRun $run,
    ) {
        $this->onQueue('remarketing');
    }

    public function handle(AutomationService $service): void
    {
        $service->advance($this->run);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('EvaluateAutomationJob failed', [
            'run_id' => $this->run->id,
            'automation_id' => $this->run->automation_id,
            'customer_id' => $this->run->customer_id,
            'error' => $exception->getMessage(),
        ]);
    }
}
