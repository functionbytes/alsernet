<?php

namespace Modules\Reviews\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Reviews\Models\ReviewGoogleLocation;
use Modules\Reviews\Services\GoogleReviewService;

class SyncGoogleReviewsJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function uniqueId(): string
    {
        return 'sync-reviews-'.$this->location->id;
    }

    public int $timeout = 120;

    public array $backoff = [30, 60, 120];

    public function __construct(
        public ReviewGoogleLocation $location
    ) {
        $this->onQueue('reviews-sync');
    }

    /**
     * @return array<int, string>
     */
    public function tags(): array
    {
        return ['reviews', 'sync'];
    }

    public function handle(GoogleReviewService $service): void
    {
        $count = $service->syncReviews($this->location);

        Log::info("Synced {$count} reviews for location {$this->location->id}");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SyncGoogleReviewsJob failed permanently', [
            'location_id' => $this->location->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
