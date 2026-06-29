<?php

namespace Modules\Template\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class ClearTemplateCachesJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public int $backoff = 10;

    public function __construct()
    {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        Artisan::call('optimize:clear');
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Template cache clear job failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}
