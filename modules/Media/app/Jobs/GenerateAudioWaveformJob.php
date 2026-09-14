<?php

namespace Modules\Media\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\MediaFile;
use Symfony\Component\Process\Process;

class GenerateAudioWaveformJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 120;

    public int $backoff = 10;

    public function __construct(private readonly int $mediaFileId)
    {
        $this->onQueue('media-heavy');
    }

    public function handle(): void
    {
        $ffprobePath = config('media.ffmpeg.ffprobe_path', 'ffprobe');

        $checkProcess = new Process(['which', $ffprobePath]);
        $checkProcess->run();

        if (! $checkProcess->isSuccessful()) {
            Log::info('ffprobe no disponible, skip audio waveform', [
                'module' => 'media',
                'job' => static::class,
                'media_file_id' => $this->mediaFileId,
            ]);

            return;
        }

        $file = MediaFile::query()->find($this->mediaFileId);

        if (! $file || $file->type !== 'audio') {
            return;
        }

        $disk = Storage::disk($file->disk);

        if (! $disk->exists($file->url)) {
            return;
        }

        $sourcePath = $disk->path($file->url);
        $peaks = $this->extractPeaks($ffprobePath, $sourcePath);

        if ($peaks === null) {
            return;
        }

        $metadata = $file->metadata ?? [];
        $metadata['waveform'] = $peaks;
        $file->update(['metadata' => $metadata]);
    }

    /**
     * Extract 100 normalized (0-1) amplitude peaks using ffprobe.
     *
     * @return array<int, float>|null
     */
    private function extractPeaks(string $ffprobePath, string $sourcePath): ?array
    {
        $process = new Process([
            $ffprobePath,
            '-f', 'lavfi',
            '-i', "amovie={$sourcePath},asetnsamples=n=256,astats=metadata=1:reset=1",
            '-show_entries', 'frame_tags=lavfi.astats.Overall.RMS_level',
            '-of', 'csv',
            '-v', 'quiet',
        ]);
        $process->setTimeout(90);
        $process->run();

        if (! $process->isSuccessful()) {
            Log::warning('Audio waveform extraction failed', [
                'module' => 'media',
                'job' => static::class,
                'media_file_id' => $this->mediaFileId,
                'output' => $process->getErrorOutput(),
            ]);

            return null;
        }

        $lines = array_filter(explode("\n", trim($process->getOutput())));
        $rmsValues = [];

        foreach ($lines as $line) {
            // CSV format: frame,{value}
            $parts = explode(',', $line);
            $rms = isset($parts[1]) ? trim($parts[1]) : null;

            if ($rms !== null && is_numeric($rms) && $rms !== '-inf') {
                // RMS level in dBFS, typical range -60 to 0; normalize to 0-1
                $normalized = max(0.0, min(1.0, (((float) $rms) + 60) / 60));
                $rmsValues[] = round($normalized, 4);
            }
        }

        if (empty($rmsValues)) {
            return null;
        }

        // Downsample to 100 peaks
        return $this->downsample($rmsValues, 100);
    }

    /**
     * @param  array<int, float>  $values
     * @return array<int, float>
     */
    private function downsample(array $values, int $targetCount): array
    {
        $count = count($values);

        if ($count <= $targetCount) {
            return $values;
        }

        $peaks = [];
        $chunkSize = $count / $targetCount;

        for ($i = 0; $i < $targetCount; $i++) {
            $start = (int) floor($i * $chunkSize);
            $end = (int) floor(($i + 1) * $chunkSize);
            $chunk = array_slice($values, $start, $end - $start);
            $peaks[] = round(array_sum($chunk) / count($chunk), 4);
        }

        return $peaks;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateAudioWaveformJob failed', [
            'module' => 'media',
            'job' => static::class,
            'id' => $this->mediaFileId,
            'error' => $exception->getMessage(),
        ]);
    }
}
