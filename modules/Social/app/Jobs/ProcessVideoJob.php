<?php

namespace Modules\Social\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Social\Models\Post;
use Modules\Social\Services\MediaProcessingService;

class ProcessVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;

    public $tries = 3;

    public $backoff = [60, 300, 900];

    public function __construct(
        public Post $post,
        public string $videoPath,
        public string $network
    ) {}

    public function handle(MediaProcessingService $processor): void
    {
        try {
            $optimized = $processor->processVideo($this->videoPath, $this->network);

            if ($optimized) {
                $thumbnail = $processor->generateVideoThumbnail($optimized);

                $this->post->addMedia($optimized)
                    ->preservingOriginal()
                    ->toMediaCollection('videos');

                if ($thumbnail) {
                    $this->post->addMedia($thumbnail)
                        ->toMediaCollection('images');
                }
            }
        } catch (\Exception $e) {
            \Log::error('Video processing failed for post '.$this->post->id, [
                'error' => $e->getMessage(),
                'video' => $this->videoPath,
                'network' => $this->network,
            ]);

            $this->fail($e);
        }
    }

    public function tags(): array
    {
        return ['video-processing', 'post:'.$this->post->id, 'network:'.$this->network];
    }

    public function failed(\Throwable $exception): void
    {
        // Se mantiene el fail() inmediato dentro de handle() (no retry): a
        // diferencia de Translate/Watermark, addMedia() de Spatie es aditivo,
        // no upsert — reintentar tras un fallo parcial (p.ej. la miniatura)
        // duplicaría el vídeo ya adjuntado al post. $this->fail() ya invoca
        // este método; se deja para que quede registrado el log terminal.
        \Log::error('ProcessVideoJob failed permanently for post '.$this->post->id, [
            'error' => $exception->getMessage(),
            'video' => $this->videoPath,
            'network' => $this->network,
        ]);
    }
}
