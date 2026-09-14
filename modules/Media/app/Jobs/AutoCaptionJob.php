<?php

namespace Modules\Media\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\MediaFile;
use Modules\Media\Services\AutoCaptionFactory;

class AutoCaptionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 60;

    public function __construct(private readonly int $mediaFileId)
    {
        $this->onQueue('media-heavy');
    }

    public function handle(): void
    {
        $file = MediaFile::query()->find($this->mediaFileId);

        if (! $file || $file->type !== 'image' || ! empty($file->alt)) {
            return;
        }

        $disk = Storage::disk($file->disk);

        if (! $disk->exists($file->url)) {
            return;
        }

        $caption = AutoCaptionFactory::make()->generateCaption($disk->path($file->url));

        if ($caption !== null) {
            $file->update(['alt' => $caption]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::warning('AutoCaptionJob failed', [
            'media_file_id' => $this->mediaFileId,
            'error' => $exception->getMessage(),
        ]);
    }
}
