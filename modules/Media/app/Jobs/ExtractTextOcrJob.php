<?php

namespace Modules\Media\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\MediaFile;
use Symfony\Component\Process\Process;

class ExtractTextOcrJob implements ShouldQueue
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
        $tesseractPath = config('media.tesseract.path', 'tesseract');

        $checkProcess = new Process(['which', $tesseractPath]);
        $checkProcess->run();

        if (! $checkProcess->isSuccessful()) {
            Log::info('Tesseract no disponible, skip OCR', [
                'module' => 'media',
                'job' => static::class,
                'media_file_id' => $this->mediaFileId,
            ]);

            return;
        }

        $file = MediaFile::query()->find($this->mediaFileId);

        if (! $file || ! in_array($file->type, ['image', 'pdf'], true)) {
            return;
        }

        $disk = Storage::disk($file->disk);

        if (! $disk->exists($file->url)) {
            return;
        }

        // Only process images directly; PDF support requires pdftoppm
        if ($file->type !== 'image') {
            Log::info('OCR solo soporta imágenes en este stub, skip PDF', [
                'module' => 'media',
                'job' => static::class,
                'media_file_id' => $this->mediaFileId,
            ]);

            return;
        }

        $sourcePath = $disk->path($file->url);
        $languages = config('media.tesseract.languages', 'spa+eng');

        $process = new Process([$tesseractPath, $sourcePath, 'stdout', '-l', $languages]);
        $process->setTimeout(120);
        $process->run();

        if ($process->isSuccessful()) {
            $metadata = $file->metadata ?? [];
            $metadata['extracted_text'] = trim($process->getOutput());
            $file->update(['metadata' => $metadata]);
        } else {
            Log::warning('OCR extraction failed', [
                'module' => 'media',
                'job' => static::class,
                'media_file_id' => $this->mediaFileId,
                'output' => $process->getErrorOutput(),
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ExtractTextOcrJob failed', [
            'module' => 'media',
            'job' => static::class,
            'id' => $this->mediaFileId,
            'error' => $exception->getMessage(),
        ]);
    }
}
