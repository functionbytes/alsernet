<?php

namespace Modules\Media\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\MediaFile;
use Symfony\Component\Process\Process;

class ExtractSubtitlesJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(private readonly int $mediaFileId)
    {
        $this->onQueue('media-heavy');
    }

    public function handle(): void
    {
        $ffmpegPath = config('media.ffmpeg.path', 'ffmpeg');

        $check = new Process(['which', $ffmpegPath]);
        $check->run();

        if (! $check->isSuccessful()) {
            Log::info('FFmpeg no disponible, skip subtitle extraction', ['media_file_id' => $this->mediaFileId]);

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
        $srtRelPath = preg_replace('/\.[^.]+$/', '.srt', $file->url);
        $srtFullPath = $disk->path($srtRelPath);

        $process = new Process([
            $ffmpegPath,
            '-i', $sourcePath,
            '-map', '0:s:0',
            '-c:s', 'srt',
            '-y',
            $srtFullPath,
        ]);
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            Log::info('No subtitle stream found or extraction failed', [
                'media_file_id' => $this->mediaFileId,
            ]);

            return;
        }

        $metadata = $file->metadata ?? [];
        $metadata['subtitles'] = $srtRelPath;
        $file->update(['metadata' => $metadata]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ExtractSubtitlesJob failed', [
            'media_file_id' => $this->mediaFileId,
            'error' => $exception->getMessage(),
        ]);
    }
}
