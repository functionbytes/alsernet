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
        try {
            $watermarked = $processor->applyWatermark(
                $this->imagePath,
                null,
                $this->position
            );

            // The image is modified in place, so just log success
            \Log::info('Watermark applied successfully', [
                'post_id' => $this->post->id,
                'image' => $this->imagePath,
            ]);
        } catch (\Exception $e) {
            \Log::error('Watermark application failed for post '.$this->post->id, [
                'error' => $e->getMessage(),
                'image' => $this->imagePath,
            ]);

            $this->fail($e);
        }
    }

    public function tags(): array
    {
        return ['watermark', 'post:'.$this->post->id];
    }
}
