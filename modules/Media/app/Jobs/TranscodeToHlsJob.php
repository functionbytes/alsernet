<?php

namespace Modules\Media\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\MediaFile;
use Symfony\Component\Process\Process;

class TranscodeToHlsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 1800;

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
            Log::info('FFmpeg no disponible, skip HLS transcode', ['media_file_id' => $this->mediaFileId]);

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
        $dir = $dir === '.' ? '' : $dir.'/';

        $hlsDir = $dir.$base.'_hls';
        $hlsFullDir = $disk->path($hlsDir);

        if (! is_dir($hlsFullDir)) {
            mkdir($hlsFullDir, 0755, true);
        }

        $playlistPath = $hlsFullDir.'/master.m3u8';

        $process = new Process([
            $ffmpegPath,
            '-i', $sourcePath,
            '-codec:v', 'libx264',
            '-codec:a', 'aac',
            '-f', 'hls',
            '-hls_time', '10',
            '-hls_list_size', '0',
            '-hls_segment_filename', $hlsFullDir.'/segment_%03d.ts',
            '-y',
            $playlistPath,
        ]);
        $process->setTimeout(1800);
        $process->run();

        if (! $process->isSuccessful()) {
            Log::warning('HLS transcode failed', [
                'media_file_id' => $this->mediaFileId,
                'output' => $process->getErrorOutput(),
            ]);

            return;
        }

        $metadata = $file->metadata ?? [];
        $metadata['hls_playlist'] = $hlsDir.'/master.m3u8';
        $file->update(['metadata' => $metadata]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('TranscodeToHlsJob failed', [
            'media_file_id' => $this->mediaFileId,
            'error' => $exception->getMessage(),
        ]);
    }
}
