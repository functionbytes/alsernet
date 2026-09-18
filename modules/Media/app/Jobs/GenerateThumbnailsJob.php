<?php

namespace Modules\Media\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\MediaFile;

class GenerateThumbnailsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public array $backoff = [30, 60, 120];

    private const THUMBNAILABLE_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    public function __construct(public readonly int $mediaFileId)
    {
        $this->onQueue('media-light');
    }

    public function handle(): void
    {
        if (! config('media.generate_thumbnails_enabled', true)) {
            return;
        }

        $mediaFile = MediaFile::query()->find($this->mediaFileId);

        if (! $mediaFile || $mediaFile->type !== 'image') {
            return;
        }

        if (! in_array($mediaFile->mime_type, self::THUMBNAILABLE_MIMES, true)) {
            return;
        }

        $disk = $mediaFile->disk ?? 'media';

        if (! Storage::disk($disk)->exists($mediaFile->url)) {
            return;
        }

        $content = Storage::disk($disk)->get($mediaFile->url);
        $tempPath = tempnam(sys_get_temp_dir(), 'media_thumb_');

        try {
            file_put_contents($tempPath, $content);

            $sizes = config('media.sizes', []);
            $generated = [];

            foreach ($sizes as $key => $dim) {
                [$w, $h] = array_pad(explode('x', $dim), 2, null);
                $w = (int) $w;
                $h = (int) $h;

                if ($w <= 0 || $h <= 0) {
                    continue;
                }

                $ext = strtolower(pathinfo($mediaFile->url, PATHINFO_EXTENSION));
                $base = pathinfo($mediaFile->url, PATHINFO_FILENAME);
                $dir = pathinfo($mediaFile->url, PATHINFO_DIRNAME);
                $dir = $dir === '.' ? '' : rtrim($dir, '/').'/';
                $thumbPath = $dir.$base.'_'.$key.'.'.$ext;

                try {
                    $thumbnail = $this->createCroppedThumbnail($tempPath, $mediaFile->mime_type, $w, $h);

                    if (! $thumbnail) {
                        continue;
                    }

                    $quality = (int) config('media.image_optimization.quality', 85);

                    ob_start();
                    match ($mediaFile->mime_type) {
                        'image/jpeg' => imagejpeg($thumbnail, null, $quality),
                        'image/png' => imagepng($thumbnail, null, (int) round((100 - $quality) / 11)),
                        'image/webp' => imagewebp($thumbnail, null, $quality),
                        'image/gif' => imagegif($thumbnail),
                    };
                    $thumbContent = ob_get_clean();

                    imagedestroy($thumbnail);

                    Storage::disk($disk)->put($thumbPath, $thumbContent);
                    $generated[$key] = $thumbPath;
                } catch (\Throwable $e) {
                    Log::warning('Thumbnail generation failed for size', [
                        'media_file_id' => $this->mediaFileId,
                        'size' => $key,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if (! empty($generated)) {
                $metadata = $mediaFile->metadata ?? [];
                $metadata['thumbnails'] = $generated;
                $mediaFile->update(['metadata' => $metadata]);
            }
        } finally {
            @unlink($tempPath);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateThumbnailsJob failed permanently', [
            'media_file_id' => $this->mediaFileId,
            'error' => $exception->getMessage(),
        ]);
    }

    private function createCroppedThumbnail(string $path, string $mimeType, int $w, int $h): \GdImage|false
    {
        $source = match ($mimeType) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            'image/gif' => @imagecreatefromgif($path),
            default => false,
        };

        if (! $source) {
            return false;
        }

        $srcW = imagesx($source);
        $srcH = imagesy($source);

        // Calculate cover crop (fill target dimensions, centered)
        $srcRatio = $srcW / $srcH;
        $dstRatio = $w / $h;

        if ($srcRatio > $dstRatio) {
            $cropH = $srcH;
            $cropW = (int) round($srcH * $dstRatio);
            $cropX = (int) round(($srcW - $cropW) / 2);
            $cropY = 0;
        } else {
            $cropW = $srcW;
            $cropH = (int) round($srcW / $dstRatio);
            $cropX = 0;
            $cropY = (int) round(($srcH - $cropH) / 2);
        }

        $thumb = imagecreatetruecolor($w, $h);

        // Preserve transparency for PNG/GIF/WebP
        if (in_array($mimeType, ['image/png', 'image/gif', 'image/webp'], true)) {
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
        }

        imagecopyresampled($thumb, $source, 0, 0, $cropX, $cropY, $w, $h, $cropW, $cropH);
        imagedestroy($source);

        return $thumb;
    }
}
