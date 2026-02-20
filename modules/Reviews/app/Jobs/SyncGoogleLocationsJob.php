<?php

namespace Modules\Reviews\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Reviews\Models\ReviewGoogleConnection;
use Modules\Reviews\Services\GoogleLocationService;

class SyncGoogleLocationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300; // 5 minutes

    public int $backoff = 120;

    public function __construct(
        public ReviewGoogleConnection $connection
    ) {
        $this->onQueue('google-sync');
    }

    public function handle(GoogleLocationService $service): void
    {
        try {
            $count = $service->syncLocations($this->connection);

            Log::info("Synced {$count} locations for connection {$this->connection->id}");
        } catch (\Exception $e) {
            Log::error('Failed to sync locations', [
                'connection_id' => $this->connection->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SyncGoogleLocationsJob failed permanently', [
            'connection_id' => $this->connection->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
