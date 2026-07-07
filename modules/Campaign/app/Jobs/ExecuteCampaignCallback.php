<?php

namespace Modules\Campaign\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExecuteCampaignCallback implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public int $backoff = 10;

    protected $webhook;

    protected $log;

    public function __construct($webhook, $log)
    {
        $this->webhook = $webhook;
        $this->log = $log;
    }

    public function handle()
    {
        $this->webhook->execute($this->log);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ExecuteCampaignCallback failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}
