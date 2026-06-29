<?php

namespace Modules\Seo\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Seo\Services\IndexNowService;

class SubmitUrlsToIndexNowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    /** @var array<int> */
    public array $backoff = [60, 300, 900];

    /**
     * @param  array<int, string>  $urls
     */
    public function __construct(public array $urls)
    {
        $this->onQueue('seo');
    }

    public function handle(IndexNowService $service): void
    {
        if (! $service->enabled()) {
            return;
        }

        $result = $service->submit($this->urls);

        if (! $result['ok']) {
            Log::info('IndexNow partial submit', $result + ['urls' => $this->urls]);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('IndexNow job failed after retries', [
            'error' => $e->getMessage(),
            'urls' => $this->urls,
        ]);
    }
}
