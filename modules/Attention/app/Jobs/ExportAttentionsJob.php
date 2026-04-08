<?php

namespace Modules\Attention\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Attention\Models\Attention;
use Modules\Attention\Notifications\AttentionExportReadyNotification;
use Modules\Attention\Services\AttentionExportService;

class ExportAttentionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public int $backoff = 60;

    public function __construct(
        public readonly User $user,
        public readonly array $filters = [],
        public readonly string $format = 'excel'
    ) {
        $this->onQueue('exports');
    }

    public function handle(): void
    {
        $query = Attention::query()->with(['type', 'category', 'department', 'sede', 'assignedUser']);

        if (! empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (! empty($this->filters['date_from']) && ! empty($this->filters['date_to'])) {
            $query->whereBetween('created_at', [$this->filters['date_from'], $this->filters['date_to']]);
        }

        $attentions = $query->latest()->get();

        $token = match ($this->format) {
            'pdf' => AttentionExportService::exportToPdf($attentions),
            'csv' => AttentionExportService::exportToCsv($attentions),
            default => AttentionExportService::exportToExcel($attentions),
        };

        Log::info('Attention export completed', [
            'user_id' => $this->user->id,
            'format' => $this->format,
            'token' => $token,
            'count' => $attentions->count(),
        ]);

        $this->user->notify(new AttentionExportReadyNotification($token, $this->format));
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ExportAttentionsJob failed permanently', [
            'user_id' => $this->user->id,
            'format' => $this->format,
            'error' => $exception->getMessage(),
        ]);
    }
}
