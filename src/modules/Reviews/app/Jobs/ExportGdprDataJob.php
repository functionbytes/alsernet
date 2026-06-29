<?php

namespace Modules\Reviews\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Reviews\Notifications\GdprExportReadyNotification;

class ExportGdprDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 300;

    public int $backoff = 60;

    public function __construct(public readonly User $user)
    {
        $this->onQueue('exports');
    }

    public function handle(): void
    {
        $exitCode = \Artisan::call('reviews:gdpr-export', ['user_id' => $this->user->id]);

        if ($exitCode !== 0) {
            throw new \RuntimeException("GDPR export command failed for user {$this->user->id}");
        }

        $date = now()->format('Y-m-d');
        $path = "gdpr/user_{$this->user->id}_reviews_{$date}.json";

        Log::info('GDPR export job completed', ['user_id' => $this->user->id, 'path' => $path]);

        if (class_exists(GdprExportReadyNotification::class)) {
            $this->user->notify(new GdprExportReadyNotification($path));
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ExportGdprDataJob failed permanently', [
            'user_id' => $this->user->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
