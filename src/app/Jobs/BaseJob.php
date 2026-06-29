<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

abstract class BaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $maxExceptions = 3;

    /**
     * Exponential backoff: 30s, 5min, 30min
     */
    public function backoff(): array
    {
        return [30, 300, 1800];
    }

    /**
     * Log final failure to a dedicated channel.
     */
    public function failed(\Throwable $e): void
    {
        Log::channel('failed_jobs')->error('Job failed after all retries', [
            'job' => static::class,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
    }
}
