<?php

namespace Modules\HelpdeskLivechat\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskLivechat\Models\LivestreamEvent;

class PruneLivestreamEventsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 120;

    public int $backoff = 30;

    public function __construct()
    {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $cutoff = now()->subDays(30);
        $total = 0;

        do {
            $deleted = LivestreamEvent::query()
                ->where('created_at', '<', $cutoff)
                ->limit(1000)
                ->delete();
            $total += $deleted;
        } while ($deleted > 0);

        Log::info('PruneLivestreamEventsJob completed', [
            'cutoff' => $cutoff->toIso8601String(),
            'deleted' => $total,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('PruneLivestreamEventsJob failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}
