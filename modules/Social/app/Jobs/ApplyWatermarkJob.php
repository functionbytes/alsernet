<?php

namespace Modules\Social\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Social\Models\Post;
use Modules\Social\Services\MediaProcessingService;

class ApplyWatermarkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 300;

    public $backoff = [60, 300, 900];

    public function __construct(
        public Post $post,
        public string $imagePath,
        public string $position = 'bottom-right'
    ) {}

    public function handle(MediaProcessingService $processor): void
    {
        $processor->applyWatermark(
            $this->imagePath,
            null,
            $this->position
        );

        // The image is modified in place, so just log success
        \Log::info('Watermark applied successfully', [
            'post_id' => $this->post->id,
            'image' => $this->imagePath,
        ]);
    }

    public function tags(): array
    {
        return ['watermark', 'post:'.$this->post->id];
    }

    public function failed(\Throwable $exception): void
    {
        // El catch-and-fail() inmediato anterior anulaba tries=3/backoff:
        // cualquier error (incl. uno transitorio de E/S) fallaba en el primer
        // intento sin reintentar.
        \Log::error('ApplyWatermarkJob failed permanently for post '.$this->post->id, [
            'error' => $exception->getMessage(),
            'image' => $this->imagePath,
        ]);
    }
}
