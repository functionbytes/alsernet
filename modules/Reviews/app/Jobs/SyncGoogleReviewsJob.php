<?php

namespace Modules\Reviews\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Reviews\Models\ReviewGoogleLocation;
use Modules\Reviews\Services\GoogleReviewService;

class SyncGoogleReviewsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300; // 5 minutes

    public int $backoff = 60;

    public function __construct(
        public ReviewGoogleLocation $location
    ) {
        $this->onQueue('google-sync');
    }

    public function handle(GoogleReviewService $service): void
    {
        try {
            $count = $service->syncReviews($this->location);

            Log::info("Synced {$count} reviews for location {$this->location->id}");
        } catch (\Exception $e) {
            Log::error('Failed to sync reviews', [
                'location_id' => $this->location->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SyncGoogleReviewsJob failed permanently', [
            'location_id' => $this->location->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
