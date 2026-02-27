<?php

namespace Modules\Reviews\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Reviews\Notifications\ExportReadyNotification;
use Modules\Reviews\Services\ReviewExportService;

class ExportReviewsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 600; // 10 minutes for large exports

    public int $backoff = 60;

    public function __construct(
        public User $user,
        public array $filters = [],
        public string $format = 'csv'
    ) {
        $this->onQueue('exports');
    }

    public function handle(ReviewExportService $service): void
    {
        try {
            // Export to CSV (returns file path)
            $filePath = $service->exportToCsv($this->filters);

            Log::info('Reviews export completed', [
                'user_id' => $this->user->id,
                'format' => $this->format,
                'file_path' => $filePath,
            ]);

            // Notify user that export is ready
            $this->user->notify(new ExportReadyNotification($filePath));
        } catch (\Exception $e) {
            Log::error('Reviews export failed', [
                'user_id' => $this->user->id,
                'format' => $this->format,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ExportReviewsJob failed permanently', [
            'user_id' => $this->user->id,
            'format' => $this->format,
            'error' => $exception->getMessage(),
        ]);
    }
}
