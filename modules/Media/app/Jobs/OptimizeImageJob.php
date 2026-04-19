<?php

namespace Modules\Media\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Modules\Media\Models\MediaFile;

/**
 * Background job that produces the `.webp` version + all `-{W}w.webp`
 * responsive variants of a freshly-uploaded image. Dispatched from
 * `MediaFileObserver::created()` so the operator never waits for
 * convert/srcset to finish while uploading.
 *
 * Doing this asynchronously keeps the upload endpoint snappy (<100 ms),
 * while the browser still eventually sees WebP + responsive srcset thanks
 * to `RvMedia::image()` detecting the new files in `public/`.
 */
class OptimizeImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public int $backoff = 10;

    /** @var array<int,int> */
    private const RESPONSIVE_WIDTHS = [480, 768, 1024, 1920];

    private const QUALITY = 82;

    public function __construct(
        private readonly int $mediaFileId,
    ) {
        $this->onQueue('media-optimize');
    }

    public function handle(): void
    {
        $file = MediaFile::find($this->mediaFileId);
        if (! $file) {
            return;
        }
        if ($file->type !== 'image') {
            return;
        }
        if (! in_array($file->mime_type, ['image/jpeg', 'image/jpg', 'image/png'], true)) {
            return;
        }

        if (! extension_loaded('gd') && ! extension_loaded('imagick')) {
            return;
        }

        $diskName = $file->disk ?: config('filesystems.default');
        $disk = Storage::disk($diskName);
        $relative = ltrim((string) $file->url, '/');

        if (! $disk->exists($relative)) {
            return;
        }

        $manager = ImageManager::gd();
        $contents = $disk->get($relative);
        $image = $manager->read($contents);
        $originalWidth = $image->width();

        // 1) Generate .webp sibling (simple conversion).
        $webpPath = preg_replace('/\.(jpe?g|png)$/i', '.webp', $relative);
        if ($webpPath && ! $disk->exists($webpPath)) {
            $disk->put($webpPath, (string) $image->toWebp(self::QUALITY));
        }

        // 2) Generate responsive variants (-480w.webp, -768w.webp, …).
        foreach (self::RESPONSIVE_WIDTHS as $w) {
            if ($w >= $originalWidth) {
                continue;
            }
            $variantPath = preg_replace('/\.(jpe?g|png)$/i', '-'.$w.'w.webp', $relative);
            if (! $variantPath || $disk->exists($variantPath)) {
                continue;
            }
            $variant = (clone $image)->scale(width: $w);
            $disk->put($variantPath, (string) $variant->toWebp(self::QUALITY));
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::warning('media.optimize_image.failed', [
            'media_file_id' => $this->mediaFileId,
            'error' => $e->getMessage(),
        ]);
    }
}
