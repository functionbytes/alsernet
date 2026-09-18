<?php

namespace Modules\Media\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\MediaFile;
use Symfony\Component\Process\Process;

class ScanForVirusJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 300;

    public int $backoff = 10;

    public function __construct(private readonly int $mediaFileId)
    {
        $this->onQueue('media-scan');
    }

    public function handle(): void
    {
        if (! config('media.virus_scan.enabled', false)) {
            return;
        }

        $clamscanPath = config('media.virus_scan.clamscan_path', 'clamscan');

        $checkProcess = new Process(['which', $clamscanPath]);
        $checkProcess->run();

        if (! $checkProcess->isSuccessful()) {
            Log::warning('ClamAV no disponible, skip virus scan', ['path' => $clamscanPath]);

            return;
        }

        $file = MediaFile::query()->find($this->mediaFileId);

        if (! $file) {
            return;
        }

        $disk = Storage::disk($file->disk);

        if (! $disk->exists($file->url)) {
            return;
        }

        $filePath = $disk->path($file->url);

        $process = new Process([$clamscanPath, '--no-summary', $filePath]);
        $process->setTimeout(300);
        $process->run();

        // Exit code 1 = virus found
        if ($process->getExitCode() === 1) {
            Log::critical('Archivo infectado detectado', [
                'id' => $file->id,
                'name' => $file->name,
                'output' => $process->getOutput(),
            ]);

            $disk->delete($file->url);
            $file->forceDelete();
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ScanForVirusJob failed', [
            'id' => $this->mediaFileId,
            'error' => $exception->getMessage(),
        ]);
    }
}
