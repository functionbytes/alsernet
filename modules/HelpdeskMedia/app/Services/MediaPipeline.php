<?php

namespace Modules\HelpdeskMedia\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\HelpdeskMedia\Support\StoredFile;

/**
 * Antivirus -> imagen (optimizar) | audio (transcribir). Opera sobre un
 * StoredFile y devuelve un MediaResult; la persistencia en BD es del adaptador,
 * que después llama a finalize() para borrar el original ya sustituido.
 */
class MediaPipeline
{
    /** Contenedores que finfo confunde con vídeo/binario cuando son audio. */
    private const AMBIGUOUS_AUDIO = [
        'application/ogg', 'video/ogg', 'video/webm', 'video/mp4', 'video/3gpp', 'application/octet-stream',
    ];

    public function __construct(
        private readonly ClamAvScanner $scanner,
        private readonly ImageOptimizer $images,
        private readonly AudioTranscriber $audio,
    ) {}

    /**
     * @throws TransientMediaException si la transcripción debe reintentarse
     */
    public function process(StoredFile $file, ?string $declaredMime = null): MediaResult
    {
        $declaredMime = $declaredMime !== null ? strtolower($declaredMime) : null;
        $bytes = $file->size();
        $mime = $file->realMime() ?? $declaredMime ?? 'application/octet-stream';
        $kind = $this->classify($mime, $declaredMime);

        $result = new MediaResult($kind, $file->disk, $file->path, $mime, $bytes);

        $scan = $this->scanner->scan($file);
        $result->scan = $scan->status;
        $result->scanSignature = $scan->signature;

        if ($scan->isInfected()) {
            return $this->quarantine($file, $result, 'infected');
        }

        if ($scan->isUnavailable() && config('helpdeskmedia.fail_closed', false)) {
            return $this->quarantine($file, $result, 'scan_unavailable');
        }

        match ($kind) {
            'image' => $this->handleImage($file, $result),
            'audio' => $this->handleAudio($file, $result),
            default => null,
        };

        return $result;
    }

    /**
     * Borra el original sustituido. Llamar solo cuando la BD ya apunta al
     * fichero nuevo.
     */
    public function finalize(MediaResult $result): void
    {
        if ($result->obsoletePath === null) {
            return;
        }

        try {
            Storage::disk($result->disk)->delete($result->obsoletePath);
        } catch (\Throwable $e) {
            Log::warning('HelpdeskMedia: could not remove replaced original', [
                'disk' => $result->disk,
                'path' => $result->obsoletePath,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function classify(string $realMime, ?string $declaredMime): string
    {
        if ($this->images->supportsMime($realMime)) {
            return 'image';
        }

        if (str_starts_with($realMime, 'audio/')) {
            return 'audio';
        }

        if (in_array($realMime, self::AMBIGUOUS_AUDIO, true) && $declaredMime !== null && str_starts_with($declaredMime, 'audio/')) {
            return 'audio';
        }

        return 'file';
    }

    private function handleImage(StoredFile $file, MediaResult $result): void
    {
        if (! config('helpdeskmedia.images.enabled', true)) {
            return;
        }

        if ($result->originalBytes > (int) config('helpdeskmedia.images.max_mb', 25) * 1048576) {
            $result->note = 'image_too_large';

            return;
        }

        $optimized = $this->images->optimize($file->localPath(), $result->originalMime);

        if ($optimized->status === ImageResult::ANIMATED) {
            $result->note = 'animated';

            return;
        }

        if ($optimized->status === ImageResult::UNSUPPORTED) {
            $result->note = 'unsupported_format';

            return;
        }

        if ($optimized->status === ImageResult::FAILED) {
            $result->note = 'optimize_failed: '.Str::limit((string) $optimized->reason, 120, '');
            Log::warning('HelpdeskMedia: image optimization failed', ['path' => $file->path, 'reason' => $optimized->reason]);

            return;
        }

        $smaller = $optimized->bytes < $result->originalBytes;

        try {
            if (! ($smaller || $optimized->sourceIncompatible || $optimized->sourceHadGps)) {
                $result->width = $optimized->width;
                $result->height = $optimized->height;
                $result->note = 'kept_original_smaller';

                return;
            }

            $this->replaceWithOptimized($file, $result, $optimized);
        } finally {
            $optimized->discard();
        }
    }

    private function replaceWithOptimized(StoredFile $file, MediaResult $result, ImageResult $optimized): void
    {
        $newPath = $this->pathWithExtension($file->path, (string) $optimized->extension);
        $disk = Storage::disk($file->disk);

        if ($newPath !== $file->path && $disk->exists($newPath)) {
            $result->note = 'target_exists';

            return;
        }

        if (config('helpdeskmedia.keep_original', false)) {
            $this->archiveOriginal($file, $result);
        }

        $stream = fopen((string) $optimized->tempPath, 'rb');

        if ($stream === false || ! $disk->put($newPath, $stream)) {
            $result->note = 'write_failed';

            return;
        }

        fclose($stream);

        $result->replaced = true;
        $result->finalPath = $newPath;
        $result->finalMime = (string) $optimized->mime;
        $result->finalBytes = (int) $optimized->bytes;
        $result->width = $optimized->width;
        $result->height = $optimized->height;

        if ($result->originalMime !== $result->finalMime) {
            $result->convertedFrom = $result->originalMime;
        }

        if ($newPath !== $file->path) {
            $result->obsoletePath = $file->path;
        }
    }

    private function handleAudio(StoredFile $file, MediaResult $result): void
    {
        if (! config('helpdeskmedia.audio.enabled', true) || ! $this->audio->enabled()) {
            return;
        }

        if ($result->originalBytes > (int) config('helpdeskmedia.audio.max_mb', 25) * 1048576) {
            $result->note = 'audio_too_large';

            return;
        }

        $transcript = $this->audio->transcribe($file->localPath(), $result->originalMime);

        if (! $transcript->ok()) {
            $result->note = 'transcription_failed: '.($transcript->error ?? 'unknown');

            return;
        }

        $result->transcript = $transcript->text;
        $result->transcriptLang = $transcript->language;
        $result->transcriptModel = $transcript->model;
    }

    private function quarantine(StoredFile $file, MediaResult $result, string $reason): MediaResult
    {
        $disk = $this->privateDisk();
        $path = trim((string) config('helpdeskmedia.quarantine.path'), '/').'/'.now()->format('Y/m').'/'.Str::uuid().'.bin';

        $stream = $file->stream();

        $moved = $stream !== null && Storage::disk($disk)->writeStream($path, $stream);

        if ($stream !== null) {
            fclose($stream);
        }

        // Si no se pudo mover, se borra igualmente: no puede quedar servible.
        Storage::disk($file->disk)->delete($file->path);

        $result->quarantined = true;
        $result->quarantineReason = $reason;

        if ($moved) {
            $result->privateDisk = $disk;
            $result->privatePath = $path;
        } else {
            $result->note = 'quarantine_move_failed_deleted';
        }

        Log::warning('HelpdeskMedia: attachment quarantined', [
            'reason' => $reason,
            'signature' => $result->scanSignature,
            'from' => $file->disk.':'.$file->path,
            'to' => $disk.':'.$path,
        ]);

        return $result;
    }

    /** Copia el original (con EXIF) al disco privado antes de sustituirlo. */
    private function archiveOriginal(StoredFile $file, MediaResult $result): void
    {
        $extension = pathinfo($file->path, PATHINFO_EXTENSION);
        $target = trim((string) config('helpdeskmedia.originals_path'), '/').'/'.now()->format('Y/m').'/'.Str::uuid().($extension !== '' ? '.'.$extension : '');
        $stream = $file->stream();

        if ($stream === null) {
            return;
        }

        Storage::disk($this->privateDisk())->writeStream($target, $stream);
        fclose($stream);

        $result->privateDisk = $this->privateDisk();
        $result->privatePath = $target;
    }

    /** Disco de cuarentena/originales; nunca uno con URL pública. */
    private function privateDisk(): string
    {
        $disk = (string) config('helpdeskmedia.quarantine.disk', 'local');

        return filled(config("filesystems.disks.{$disk}.url")) ? 'local' : $disk;
    }

    private function pathWithExtension(string $path, string $extension): string
    {
        $directory = dirname($path);
        $name = pathinfo($path, PATHINFO_FILENAME);

        return ($directory === '.' ? '' : $directory.'/').$name.'.'.$extension;
    }
}
