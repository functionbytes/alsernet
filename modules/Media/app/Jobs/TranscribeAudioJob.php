<?php

namespace Modules\Media\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\MediaFile;
use Modules\Media\Services\TranscriptionFactory;

class TranscribeAudioJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 300;

    public function __construct(private readonly int $mediaFileId)
    {
        $this->onQueue('media-heavy');
    }

    public function handle(): void
    {
        $file = MediaFile::query()->find($this->mediaFileId);

        if (! $file || $file->type !== 'audio') {
            return;
        }

        $disk = Storage::disk($file->disk);

        if (! $disk->exists($file->url)) {
            return;
        }

        $transcript = TranscriptionFactory::make()->transcribe($disk->path($file->url));

        if ($transcript === null) {
            return;
        }

        $metadata = $file->metadata ?? [];
        $metadata['transcript'] = $transcript;
        $file->update(['metadata' => $metadata]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::warning('TranscribeAudioJob failed', [
            'media_file_id' => $this->mediaFileId,
            'error' => $exception->getMessage(),
        ]);
    }
}
