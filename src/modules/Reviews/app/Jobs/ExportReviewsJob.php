<?php

namespace Modules\Reviews\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Reviews\Events\ExportCompleted;
use Modules\Reviews\Exports\ReviewsExport;
use Modules\Reviews\Models\ReviewExportHistory;
use Modules\Reviews\Notifications\ExportReadyNotification;
use Modules\Reviews\Services\ReviewExportService;

class ExportReviewsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 600; // 10 minutes for large exports

    public int $backoff = 60;

    private ?int $historyId = null;

    public function __construct(
        public User $user,
        public array $filters = [],
        public string $format = 'csv'
    ) {
        $this->onQueue('exports');
    }

    /**
     * @return array<int, string>
     */
    public function tags(): array
    {
        return ['reviews', 'export', "user:{$this->user->id}"];
    }

    public function handle(ReviewExportService $service): void
    {
        $history = ReviewExportHistory::create([
            'user_id' => $this->user->id,
            'filters' => $this->filters,
            'format' => $this->format,
            'status' => 'processing',
        ]);

        $this->historyId = $history->id;

        try {
            $filePath = $this->format === 'excel'
                ? $service->queueExcelExport($this->filters, $this->user->id)
                : $service->exportToCsv($this->filters, $this->user->id);

            $rowCount = (new ReviewsExport($this->filters))->query()->count();

            $history->update([
                'status' => 'completed',
                'file_path' => $filePath,
                'file_name' => basename($filePath),
                'row_count' => $rowCount,
                'expires_at' => now()->addDays(7),
            ]);

            Log::info('Reviews export completed', [
                'user_id' => $this->user->id,
                'format' => $this->format,
                'file_path' => $filePath,
            ]);

            event(new ExportCompleted($this->user->id, $filePath, $rowCount));

            $this->user->notify(new ExportReadyNotification($filePath));
        } catch (\Exception $e) {
            $history->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

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

        if ($this->historyId) {
            ReviewExportHistory::where('id', $this->historyId)->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);
        }
    }
}
