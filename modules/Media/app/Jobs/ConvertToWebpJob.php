<?php

namespace Modules\Media\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\MediaFile;

class ConvertToWebpJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public array $backoff = [30, 60, 120];

    private const CONVERTIBLE_MIMES = ['image/jpeg', 'image/png'];

    public function __construct(public readonly int $mediaFileId) {}

    public function handle(): void
    {
        $mediaFile = MediaFile::query()->find($this->mediaFileId);

        if (! $mediaFile) {
            return;
        }

        if (! in_array($mediaFile->mime_type, self::CONVERTIBLE_MIMES, true)) {
            return;
        }

        $disk = $mediaFile->disk ?? 'media';

        if (! Storage::disk($disk)->exists($mediaFile->url)) {
            return;
        }

        $originalContent = Storage::disk($disk)->get($mediaFile->url);
        $tempInput = tempnam(sys_get_temp_dir(), 'media_webp_in_');
        $tempOutput = $tempInput.'.webp';

        try {
            file_put_contents($tempInput, $originalContent);

            $image = $this->createImageResource($tempInput, $mediaFile->mime_type);

            if (! $image) {
                return;
            }

            imagewebp($image, $tempOutput, 85);
            imagedestroy($image);

            $webpPath = preg_replace('/\.(jpe?g|png)$/i', '.webp', $mediaFile->url);
            Storage::disk($disk)->put($webpPath, file_get_contents($tempOutput));

            $metadata = $mediaFile->metadata ?? [];
            $metadata['webp_path'] = $webpPath;
            $mediaFile->update(['metadata' => $metadata]);
        } finally {
            @unlink($tempInput);
            @unlink($tempOutput);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::warning('WebP conversion failed', [
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
