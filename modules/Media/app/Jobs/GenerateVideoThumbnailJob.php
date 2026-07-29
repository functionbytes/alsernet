<?php

namespace Modules\Media\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\MediaFile;
use Symfony\Component\Process\Process;

class GenerateVideoThumbnailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 180;

    public int $backoff = 10;

    public function __construct(private readonly int $mediaFileId)
    {
        $this->onQueue('media-heavy');
    }

    public function handle(): void
    {
        $ffmpegPath = config('media.ffmpeg.path', 'ffmpeg');

        $checkProcess = new Process(['which', $ffmpegPath]);
        $checkProcess->run();

        if (! $checkProcess->isSuccessful()) {
            Log::info('FFmpeg no disponible, skip video thumbnail', [
                'module' => 'media',
                'job' => static::class,
                'media_file_id' => $this->mediaFileId,
            ]);

            return;
        }

        $file = MediaFile::query()->find($this->mediaFileId);

        if (! $file || $file->type !== 'video') {
            return;
        }

        $disk = Storage::disk($file->disk);

        if (! $disk->exists($file->url)) {
            return;
        }

        $sourcePath = $disk->path($file->url);
        $base = pathinfo($file->url, PATHINFO_FILENAME);
        $dir = pathinfo($file->url, PATHINFO_DIRNAME);
        $dir = $dir === '.' ? '' : rtrim($dir, '/').'/';
        $thumbPath = $dir.$base.'_thumb.jpg';
        $thumbFullPath = $disk->path($thumbPath);

        $process = new Process([
            $ffmpegPath,
            '-ss', '00:00:01',
            '-i', $sourcePath,
            '-frames:v', '1',
            '-q:v', '2',
            '-y',
            $thumbFullPath,
        ]);
        $process->setTimeout(120);
        $process->run();

        if ($process->isSuccessful()) {
            $metadata = $file->metadata ?? [];
            $metadata['video_thumbnail'] = $thumbPath;
            $file->update(['metadata' => $metadata]);
        } else {
            Log::warning('Video thumbnail failed', [
                'module' => 'media',
                'job' => static::class,
                'media_file_id' => $this->mediaFileId,
                'output' => $process->getErrorOutput(),
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateVideoThumbnailJob failed', [
            'module' => 'media',
            'job' => static::class,
            'id' => $this->mediaFileId,
            'error' => $exception->getMessage(),
        ]);
    }
}
