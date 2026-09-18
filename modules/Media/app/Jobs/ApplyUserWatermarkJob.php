<?php

namespace Modules\Media\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Modules\Media\Models\MediaFile;

class ApplyUserWatermarkJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 120;

    public int $backoff = 10;

    public function __construct(
        private readonly int $mediaFileId,
        private readonly int $viewerUserId,
    ) {
        $this->onQueue('media-heavy');
    }

    public function handle(): void
    {
        if (! config('media.user_watermark.enabled', false)) {
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

        $manager = new ImageManager(new Driver);
        $image = $manager->read($disk->path($file->url));

        $text = "User ID: {$this->viewerUserId} · ".now()->format('Ymd');
        $fontSize = (int) config('media.user_watermark.font_size', 12);

        $image->text($text, $image->width() - 10, $image->height() - 10, function ($font) use ($fontSize) {
            $font->size($fontSize);
            $font->color('#ffffff40');
            $font->align('right');
            $font->valign('bottom');
        });

        $dir = dirname($file->url);
        $prefix = ($dir === '.' || $dir === '') ? '' : $dir.'/';
        $filename = pathinfo($file->url, PATHINFO_FILENAME);
        $ext = pathinfo($file->url, PATHINFO_EXTENSION);
        $outputPath = "{$prefix}{$filename}_wm_u{$this->viewerUserId}.{$ext}";

        $image->save($disk->path($outputPath), quality: (int) config('media.image_optimization.quality', 85));

        Log::info('User watermark applied', [
            'file_id' => $file->id,
            'user_id' => $this->viewerUserId,
            'output' => $outputPath,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ApplyUserWatermarkJob failed', [
            'media_file_id' => $this->mediaFileId,
            'viewer_user_id' => $this->viewerUserId,
            'error' => $exception->getMessage(),
        ]);
    }
}
