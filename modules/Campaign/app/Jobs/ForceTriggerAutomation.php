<?php

namespace Modules\Campaign\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ForceTriggerAutomation implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public int $backoff = 10;

    protected $automation;

    public function __construct($automation)
    {
        $this->automation = $automation;
    }

    public function handle()
    {
        $this->automation->forceTrigger();
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ForceTriggerAutomation failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}
