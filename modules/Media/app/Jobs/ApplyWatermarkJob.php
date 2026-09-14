<?php

namespace Modules\Media\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Modules\Media\Models\MediaFile;

class ApplyWatermarkJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public int $backoff = 15;

    public function __construct(private readonly int $mediaFileId)
    {
        $this->onQueue('media-heavy');
    }

    public function handle(): void
    {
        if (! config('media.watermark.enabled', false)) {
            return;
        }

        $source = config('media.watermark.source');

        if (! $source || ! file_exists($source)) {
            return;
        }

        $file = MediaFile::query()->find($this->mediaFileId);

        if (! $file || $file->type !== 'image') {
            return;
        }

        $disk = Storage::disk($file->disk);

        if (! $disk->exists($file->url)) {
            return;
        }

        $path = $disk->path($file->url);

        try {
            $manager = new ImageManager(new Driver);
            $image = $manager->read($path);
            $watermark = $manager->read($source);

            $sizePercent = (int) config('media.watermark.size', 10);
            $position = config('media.watermark.position', 'bottom-right');
            $targetWidth = (int) ($image->width() * $sizePercent / 100);

            $watermark->scale(width: $targetWidth);

            $image->place($watermark, $position, 10, 10);
            $image->save($path, quality: (int) config('media.image_optimization.quality', 85));
        } catch (\Throwable $e) {
            Log::warning('ApplyWatermarkJob: watermark failed', [
                'media_file_id' => $this->mediaFileId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ApplyWatermarkJob failed permanently', [
            'media_file_id' => $this->mediaFileId,
            'error' => $exception->getMessage(),
        ]);
    }
}
