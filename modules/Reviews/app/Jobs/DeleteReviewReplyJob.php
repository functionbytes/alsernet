<?php

namespace Modules\Reviews\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Services\GoogleReviewService;

class DeleteReviewReplyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300; // 5 minutes

    public int $backoff = 60;

    public function __construct(
        public Review $review
    ) {
        $this->onQueue('google-sync');
    }

    public function handle(GoogleReviewService $service): void
    {
        try {
            $service->deleteReply($this->review);

            Log::info("Deleted reply for review {$this->review->id} from Google");
        } catch (\Exception $e) {
            Log::error('Failed to delete reply', [
                'review_id' => $this->review->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('DeleteReviewReplyJob failed permanently', [
            'review_id' => $this->review->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
