<?php

namespace Modules\Media\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\MediaFile;

class StripExifJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public array $backoff = [30, 60, 120];

    private const STRIPPABLE_MIMES = ['image/jpeg', 'image/png'];

    public function __construct(public readonly int $mediaFileId)
    {
        $this->onQueue('media-light');
    }

    public function handle(): void
    {
        $mediaFile = MediaFile::query()->find($this->mediaFileId);

        if (! $mediaFile) {
            return;
        }

        if (! in_array($mediaFile->mime_type, self::STRIPPABLE_MIMES, true)) {
            return;
        }

        $disk = $mediaFile->disk ?? 'media';

        if (! Storage::disk($disk)->exists($mediaFile->url)) {
            return;
        }

        $content = Storage::disk($disk)->get($mediaFile->url);
        $tempPath = tempnam(sys_get_temp_dir(), 'media_exif_');

        try {
            file_put_contents($tempPath, $content);

            $image = $this->createImageResource($tempPath, $mediaFile->mime_type);

            if (! $image) {
                return;
            }

            $quality = (int) config('media.image_optimization.quality', 85);

            ob_start();
            match ($mediaFile->mime_type) {
                'image/jpeg' => imagejpeg($image, null, $quality),
                'image/png' => imagepng($image, null, (int) round((100 - $quality) / 11)),
            };
            $stripped = ob_get_clean();

            imagedestroy($image);

            Storage::disk($disk)->put($mediaFile->url, $stripped);
        } finally {
            @unlink($tempPath);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('StripExifJob failed permanently', [
            'media_file_id' => $this->mediaFileId,
            'error' => $exception->getMessage(),
        ]);
    }

    private function createImageResource(string $path, string $mimeType): \GdImage|false
    {
        return match ($mimeType) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            default => false,
        };
    }
}
