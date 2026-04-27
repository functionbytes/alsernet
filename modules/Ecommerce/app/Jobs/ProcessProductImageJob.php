<?php

namespace Modules\Ecommerce\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Ecommerce\Services\ImageProcessingService;

class ProcessProductImageJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public int $backoff = 10;

    public function __construct(public readonly string $imagePath)
    {
        $this->onQueue('images');
    }

    public function handle(ImageProcessingService $service): void
    {
        $service->processProductImage($this->imagePath);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Image processing job failed', [
            'path' => $this->imagePath,
            'error' => $exception->getMessage(),
        ]);
    }
}
