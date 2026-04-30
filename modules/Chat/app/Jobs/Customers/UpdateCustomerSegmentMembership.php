<?php

namespace Modules\Chat\Jobs\Customers;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Chat\Models\Customers\CustomerSegment;
use Modules\Chat\Services\Customers\CustomerSegmentService;

class UpdateCustomerSegmentMembership implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ?int $segmentId = null,
        public ?int $accountId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(CustomerSegmentService $segmentService): void
    {
        // Update specific segment
        if ($this->segmentId) {
            $segment = CustomerSegment::find($this->segmentId);

            if (! $segment) {
                Log::warning("Segment not found: {$this->segmentId}");

                return;
            }

            $segmentService->updateSegmentMembership($segment);

            return;
        }

        // Update all dynamic segments for an account
        if ($this->accountId) {
            $segmentService->updateAllDynamicSegments($this->accountId);

            return;
        }

        Log::error('UpdateCustomerSegmentMembership job called without segment_id or account_id');
    }

    public function failed(\Throwable $exception): void
    {
        Log::error(static::class.' failed', [
            'segment_id' => $this->segmentId,
            'account_id' => $this->accountId,
            'error' => $exception->getMessage(),
        ]);
    }
}
