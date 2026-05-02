<?php

namespace Modules\Remarketing\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Remarketing\Models\Segment;
use Modules\Remarketing\Services\SegmentService;

class RecalculateSegmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public int $backoff = 60;

    public function __construct(
        public readonly ?Segment $segment = null,
    ) {
        $this->onQueue('remarketing');
    }

    public function handle(SegmentService $service): void
    {
        if ($this->segment) {
            $service->recalculate($this->segment);

            return;
        }

        Segment::query()
            ->whereNull('deleted_at')
            ->cursor()
            ->each(fn (Segment $segment) => $service->recalculate($segment));
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('RecalculateSegmentJob failed', [
            'segment_id' => $this->segment?->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
