<?php

namespace Modules\HelpdeskChat\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskChat\Models\Contacts\ContactSegment;
use Modules\HelpdeskChat\Services\Contacts\ContactSegmentService;

class UpdateContactSegmentMembership implements ShouldQueue
{
    use Queueable;

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
    public function handle(ContactSegmentService $segmentService): void
    {
        // Update specific segment
        if ($this->segmentId) {
            $segment = ContactSegment::find($this->segmentId);

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

        Log::error('UpdateContactSegmentMembership job called without segment_id or account_id');
    }
}
