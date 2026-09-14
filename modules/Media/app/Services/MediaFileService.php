<?php

namespace Modules\Media\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;
use Modules\Media\Events\MediaFileDeleted;
use Modules\Media\Events\MediaFileMoved;
use Modules\Media\Events\MediaFileUploaded;
use Modules\Media\Jobs\ApplyWatermarkJob;
use Modules\Media\Jobs\AutoCaptionJob;
use Modules\Media\Jobs\AutoTagImageJob;
use Modules\Media\Jobs\ConvertToWebpJob;
use Modules\Media\Jobs\DetectPiiJob;
use Modules\Media\Jobs\ExtractSubtitlesJob;
use Modules\Media\Jobs\GenerateThumbnailsJob;
use Modules\Media\Jobs\ScanForVirusJob;
use Modules\Media\Jobs\StripExifJob;
use Modules\Media\Jobs\TranscodeToHlsJob;
use Modules\Media\Jobs\TranscribeAudioJob;
use Modules\Media\Models\MediaFavorite;
use Modules\Media\Models\MediaFile;
use Modules\Media\Models\MediaFileVersion;
use Symfony\Component\HttpFoundation\File\File;

class MediaFileService
{
    /**
     * Upload a file, deduplicating by hash.
     *
     * @return array{duplicate: bool, file: MediaFile}
     */
    public function upload(UploadedFile $file, ?int $folderId, string $disk, int $userId): array
    {
        if (! $this->hasQuotaFor($userId, $file->getSize())) {
            abort(413, 'Cuota de almacenamiento excedida');
        }

        $hash = hash_file('sha256', $file->getRealPath());

        $existing = MediaFile::query()
            ->where('file_hash', $hash)
            ->where('disk', $disk)
            ->first();

        if ($existing) {
            return ['duplicate' => true, 'file' => $existing];
        }

        $storedPath = $file->store((string) ($folderId ?? 0), $disk);
        $mimeType = $file->getMimeType();
        $fileSize = $file->getSize();
        $fileName = $file->getClientOriginalName();
        $fileHash = $hash;
        $converted = null;

        if (config('media.convert_to_avif', true) && in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true) && extension_loaded('imagick')) {
            $converted = $this->convertStoredFileToAvif($storedPath, $disk);

            if ($converted) {
                Storage::disk($disk)->delete($storedPath);
                $storedPath = $converted['path'];
                $mimeType = 'image/avif';
                $fileSize = $converted['size'];
                $fileHash = $converted['hash'];
                $fileName = $this->replaceExtensionWithAvif($fileName);

                // Re-check deduplication with the new AVIF hash
                $existingAvif = MediaFile::query()
                    ->where('file_hash', $fileHash)
                    ->where('disk', $disk)
                    ->first();

                if ($existingAvif) {
                    Storage::disk($disk)->delete($storedPath);
                    if (isset($converted['fallback_webp'])) {
                        Storage::disk($disk)->delete($converted['fallback_webp']);
                    }

                    return ['duplicate' => true, 'file' => $existingAvif];
                }
            }
        } elseif (config('media.convert_to_webp', true) && in_array($mimeType, ['image/jpeg', 'image/png'], true)) {
            $converted = $this->convertStoredFileToWebp($storedPath, $disk);

            if ($converted) {
                Storage::disk($disk)->delete($storedPath);
                $storedPath = $converted['path'];
                $mimeType = 'image/webp';
                $fileSize = $converted['size'];
                $fileHash = $converted['hash'];
                $fileName = $this->replaceExtensionWithWebp($fileName);

                // Re-check deduplication with the new WebP hash
                $existingWebP = MediaFile::query()
                    ->where('file_hash', $fileHash)
                    ->where('disk', $disk)
                    ->first();

                if ($existingWebP) {
                    Storage::disk($disk)->delete($storedPath);

                    return ['duplicate' => true, 'file' => $existingWebP];
                }
            }
        }

        $metadata = $this->extractImageMetadata($file);

        if (str_starts_with($mimeType, 'image/')) {
            $placeholder = $this->generatePlaceholder($storedPath, $disk);
            if ($placeholder) {
                $metadata['placeholder'] = $placeholder;
            }
        }

        if (! empty($converted['fallback_webp'])) {
            $metadata['fallback_webp'] = $converted['fallback_webp'];
        }

        $mediaFile = MediaFile::create([
            'name' => $fileName,
            'mime_type' => $mimeType,
            'size' => $fileSize,
            'url' => $storedPath,
            'folder_id' => $folderId,
            'user_id' => $userId,
            'disk' => $disk,
            'file_hash' => $fileHash,
            'metadata' => $metadata,
        ]);

        if (str_starts_with($mediaFile->mime_type, 'image/')) {
            $phash = $this->calculatePhash($file->getRealPath());

            if ($phash !== null) {
                $mediaFile->update(['phash' => $phash]);
            }
        }

        MediaFileUploaded::dispatch($mediaFile);

        ConvertToWebpJob::dispatch($mediaFile->id);

        if (config('media.virus_scan.enabled', false)) {
            ScanForVirusJob::dispatch($mediaFile->id);
        }

        if (str_starts_with($mediaFile->mime_type, 'image/')) {
            StripExifJob::dispatch($mediaFile->id);
            GenerateThumbnailsJob::dispatch($mediaFile->id);

            if (config('media.watermark.enabled', false)) {
                ApplyWatermarkJob::dispatch($mediaFile->id)->delay(now()->addSeconds(5));
            }

            if (config('media.ai.auto_tagging.driver', 'null') !== 'null') {
                AutoTagImageJob::dispatch($mediaFile->id)->delay(now()->addSeconds(10));
            }

            if (config('media.ai.pii_detection.driver', 'null') !== 'null') {
                DetectPiiJob::dispatch($mediaFile->id)->delay(now()->addSeconds(15));
            }

            if (config('media.ai.auto_caption.driver', 'null') !== 'null' && empty($mediaFile->alt)) {
                AutoCaptionJob::dispatch($mediaFile->id)->delay(now()->addSeconds(20));
            }
        }

        if ($mediaFile->type === 'audio' && config('media.ai.transcription.driver', 'null') !== 'null') {
            TranscribeAudioJob::dispatch($mediaFile->id);
        }

        if ($mediaFile->type === 'video') {
            if (config('media.hls.enabled', false)) {
                TranscodeToHlsJob::dispatch($mediaFile->id);
            }

            ExtractSubtitlesJob::dispatch($mediaFile->id);
        }

        return ['duplicate' => false, 'file' => $mediaFile];
    }

    /**
     * Reassemble chunks and store the final file.
     */
    public function completeChunkUpload(
        string $uploadId,
        string $filename,
        int $totalChunks,
        ?int $folderId,
        string $disk,
        int $userId
    ): JsonResponse {
        $chunkDisk = config('media.chunk.storage.disk', 'local');
        $chunkDir = config('media.chunk.storage.chunks', 'chunks').'/'.$uploadId;

        $chunks = Storage::disk($chunkDisk)->files($chunkDir);

        if (count($chunks) !== $totalChunks) {
            return response()->json([
                'message' => "Chunks incompletos: {$totalChunks} esperados, ".count($chunks).' recibidos',
            ], 422);
        }

        sort($chunks);

        $maxBytes = (int) config('media.max_upload_size', 104857600);

        // Pre-check quota before assembly
        $estimatedSize = array_sum(array_map(
            fn ($path) => Storage::disk($chunkDisk)->size($path),
            $chunks
        ));

        if (! $this->hasQuotaFor($userId, $estimatedSize)) {
            return response()->json(['message' => 'Cuota de almacenamiento excedida'], 413);
        }
        $assemblyPath = tempnam(sys_get_temp_dir(), 'chunk_asm');
        $handle = fopen($assemblyPath, 'wb');
        $written = 0;

        try {
            foreach ($chunks as $chunkPath) {
                $chunkData = Storage::disk($chunkDisk)->get($chunkPath);
                $written += strlen($chunkData);

                if ($written > $maxBytes) {
                    fclose($handle);
                    @unlink($assemblyPath);

                    return response()->json(['message' => 'Tamaño total excede el límite'], 413);
                }

                fwrite($handle, $chunkData);
            }

            fclose($handle);

            $uploadedFile = new UploadedFile(
                $assemblyPath,
                $filename,
                mime_content_type($assemblyPath) ?: 'application/octet-stream',
                null,
                true
            );

            $result = $this->upload($uploadedFile, $folderId, $disk, $userId);

            Storage::disk($chunkDisk)->deleteDirectory($chunkDir);

            return response()->json([
                'success' => true,
                'duplicate' => $result['duplicate'] ?? false,
                'file' => $this->formatFileArray($result['file']),
            ], 201);
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
            @unlink($assemblyPath);
        }
    }

    private function formatFileArray(MediaFile $file): array
    {
        return [
            'id' => $file->id,
            'uid' => $file->uid,
            'name' => $file->name,
            'type' => $file->type,
            'mime_type' => $file->mime_type,
            'size' => $file->human_size ?? $file->size,
            'url' => $file->url,
        ];
    }

    /**
     * Snapshot the current state of a file as a new version entry.
     */
    public function createVersion(MediaFile $file, ?string $notes = null): MediaFileVersion
    {
        $lastVersion = MediaFileVersion::where('media_file_id', $file->id)->max('version_number') ?? 0;

        return MediaFileVersion::create([
            'media_file_id' => $file->id,
            'user_id' => auth()->id() ?? $file->user_id,
            'name' => $file->name,
            'mime_type' => $file->mime_type,
            'size' => $file->size,
            'url' => $file->url,
            'disk' => $file->disk,
            'file_hash' => $file->file_hash,
            'version_number' => $lastVersion + 1,
            'notes' => $notes,
        ]);
    }

    /**
     * Restore a file to a prior version, auto-saving current state first.
     */
    public function restoreVersion(MediaFile $file, int $versionId): MediaFile
    {
        $version = MediaFileVersion::where('media_file_id', $file->id)->findOrFail($versionId);

        $this->createVersion($file, "Autoguardado antes de restaurar v{$version->version_number}");

        $file->update([
            'name' => $version->name,
            'mime_type' => $version->mime_type,
            'size' => $version->size,
            'url' => $version->url,
            'disk' => $version->disk,
            'file_hash' => $version->file_hash,
        ]);

        return $file->fresh();
    }

    /**
     * Download and store a file from an external URL (SSRF-protected).
     */
    public function uploadFromUrl(string $url, ?string $filename, ?int $folderId, string $disk, int $userId): MediaFile
    {
        $failuresKey = 'media-url-upload-fails';
        $failures = (int) Cache::get($failuresKey, 0);

        if ($failures >= 10) {
            abort(503, 'Servicio de descarga externa temporalmente deshabilitado.');
        }

        $this->validateExternalUrl($url);

        $filename = $filename ?: basename(parse_url($url, PHP_URL_PATH)) ?: 'descargado-'.time();
        if (! str_contains($filename, '.')) {
            $filename .= '.bin';
        }

        $maxBytes = (int) config('media.max_upload_size', 104857600);
        $tempPath = tempnam(sys_get_temp_dir(), 'mdl');

        try {
            $response = Http::timeout(30)
                ->connectTimeout(5)
                ->withOptions([
                    'sink' => $tempPath,
                    'allow_redirects' => ['max' => 3, 'strict' => true],
                    'progress' => function ($dlTotal, $dlNow) use ($maxBytes): void {
                        if ($dlNow > $maxBytes) {
                            throw new \RuntimeException('Archivo excede el límite');
                        }
                    },
                ])
                ->get($url);

            if (! $response->successful()) {
                throw new \RuntimeException('No se pudo descargar el archivo desde la URL');
            }

            $size = filesize($tempPath);

            if ($size > $maxBytes) {
                throw new \RuntimeException('El archivo excede el límite de 100MB');
            }

            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($tempPath) ?: 'application/octet-stream';

            $allowedMimes = [
                'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/zip', 'application/x-zip-compressed',
                'video/mp4', 'audio/mpeg',
                'text/plain', 'text/csv',
            ];

            if (! in_array($mimeType, $allowedMimes, true)) {
                throw new \RuntimeException('Tipo de archivo no permitido: '.$mimeType);
            }

            $storedPath = Storage::disk($disk)->putFileAs(
                (string) ($folderId ?? 0),
                new File($tempPath),
                $filename
            );

            $fileName = $filename;
            $fileSize = $size;
            $fileHash = null;
            $converted = null;

            if (config('media.convert_to_avif', true) && in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true) && extension_loaded('imagick')) {
                $converted = $this->convertStoredFileToAvif($storedPath, $disk);

                if ($converted) {
                    Storage::disk($disk)->delete($storedPath);
                    $storedPath = $converted['path'];
                    $mimeType = 'image/avif';
                    $fileSize = $converted['size'];
                    $fileHash = $converted['hash'];
                    $fileName = $this->replaceExtensionWithAvif($fileName);
                }
            } elseif (config('media.convert_to_webp', true) && in_array($mimeType, ['image/jpeg', 'image/png'], true)) {
                $converted = $this->convertStoredFileToWebp($storedPath, $disk);

                if ($converted) {
                    Storage::disk($disk)->delete($storedPath);
                    $storedPath = $converted['path'];
                    $mimeType = 'image/webp';
                    $fileSize = $converted['size'];
                    $fileHash = $converted['hash'];
                    $fileName = $this->replaceExtensionWithWebp($fileName);
                }
            }

            if (! $fileHash) {
                $fileHash = hash_file('sha256', Storage::disk($disk)->path($storedPath));
            }

            $existing = MediaFile::query()
                ->where('file_hash', $fileHash)
                ->where('disk', $disk)
                ->first();

            if ($existing) {
                Storage::disk($disk)->delete($storedPath);
                if (! empty($converted['fallback_webp'])) {
                    Storage::disk($disk)->delete($converted['fallback_webp']);
                }

                return $existing;
            }

            $metadata = [];

            if (str_starts_with($mimeType, 'image/')) {
                $dimensions = @getimagesize(Storage::disk($disk)->path($storedPath));
                if ($dimensions) {
                    $metadata = ['width' => $dimensions[0], 'height' => $dimensions[1]];
                }

                $placeholder = $this->generatePlaceholder($storedPath, $disk);
                if ($placeholder) {
                    $metadata['placeholder'] = $placeholder;
                }
            }

            if (! empty($converted['fallback_webp'])) {
                $metadata['fallback_webp'] = $converted['fallback_webp'];
            }

            $mediaFile = MediaFile::create([
                'name' => $fileName,
                'mime_type' => $mimeType,
                'size' => $fileSize,
                'url' => $storedPath,
                'folder_id' => $folderId,
                'user_id' => $userId,
                'disk' => $disk,
                'file_hash' => $fileHash,
                'metadata' => $metadata,
            ]);

            if (str_starts_with($mimeType, 'image/')) {
                $phash = $this->calculatePhash($tempPath);

                if ($phash !== null) {
                    $mediaFile->update(['phash' => $phash]);
                }

                StripExifJob::dispatch($mediaFile->id);
                GenerateThumbnailsJob::dispatch($mediaFile->id);
            }

            MediaFileUploaded::dispatch($mediaFile);

            return $mediaFile;
        } catch (\Throwable $e) {
            $count = (int) Cache::get($failuresKey, 0) + 1;
            Cache::put($failuresKey, $count, 300);
            throw $e;
        } finally {
            @unlink($tempPath);
        }
    }

    /**
     * Copy a file within the same disk (with path traversal protection).
     */
    public function copy(MediaFile $file, int $userId): MediaFile
    {
        $disk = $file->disk ?? 'media';
        $ext = strtolower(pathinfo($file->name, PATHINFO_EXTENSION));

        if (in_array($ext, ['php', 'phtml', 'phar', 'htaccess'], true)) {
            throw new \RuntimeException('Tipo de archivo no permitido para copiar');
        }

        $diskRoot = realpath(Storage::disk($disk)->path(''));
        $realOriginal = realpath(Storage::disk($disk)->path($file->url));

        if ($realOriginal === false || $diskRoot === false || ! str_starts_with($realOriginal, $diskRoot)) {
            throw new \RuntimeException('Ruta de archivo inválida');
        }

        if (! Storage::disk($disk)->exists($file->url)) {
            throw new \RuntimeException('El archivo original no existe');
        }

        $base = pathinfo($file->name, PATHINFO_FILENAME);
        $newName = $base.'-(copia).'.$ext;
        $dir = dirname($file->url);
        $newPath = $dir.'/'.pathinfo($base, PATHINFO_FILENAME).'-copia-'.uniqid().'.'.$ext;

        Storage::disk($disk)->copy($file->url, $newPath);

        return MediaFile::create([
            'name' => $newName,
            'mime_type' => $file->mime_type,
            'size' => $file->size,
            'url' => $newPath,
            'folder_id' => $file->folder_id,
            'user_id' => $userId,
            'disk' => $disk,
            'metadata' => $file->metadata ?? [],
        ]);
    }

    public function rename(MediaFile $file, string $name): MediaFile
    {
        $file->update(['name' => $name]);

        return $file;
    }

    public function move(MediaFile $file, ?int $folderId): MediaFile
    {
        $previousFolderId = $file->folder_id;

        $file->update(['folder_id' => $folderId]);

        MediaFileMoved::dispatch($file, $previousFolderId, $file->folder_id);

        return $file;
    }

    public function delete(MediaFile $file): void
    {
        MediaFileDeleted::dispatch($file);

        $file->delete();
    }

    /**
     * Permanently delete a file: removes physical storage, thumbnails, and DB row.
     */
    public function forceDelete(MediaFile $file): void
    {
        DB::transaction(function () use ($file): void {
            Storage::disk($file->disk)->delete($file->url);
            $this->deleteThumbnails($file);
            $file->forceDelete();
        });
    }

    /**
     * Delete thumbnail files stored in metadata.
     */
    private function deleteThumbnails(MediaFile $file): void
    {
        $thumbnails = $file->metadata['thumbnails'] ?? [];

        foreach ($thumbnails as $path) {
            Storage::disk($file->disk)->delete($path);
        }
    }

    public function restore(MediaFile $file): void
    {
        $file->restore();
    }

    /**
     * Toggle favorite status using the polymorphic media_favorites table.
     * Returns true if now favorited, false if removed.
     */
    public function toggleFavorite(MediaFile $file, int $userId): bool
    {
        $existing = MediaFavorite::where([
            'user_id' => $userId,
            'favoritable_id' => $file->id,
            'favoritable_type' => MediaFile::class,
        ])->first();

        if ($existing) {
            $existing->delete();

            return false;
        }

        MediaFavorite::create([
            'user_id' => $userId,
            'favoritable_id' => $file->id,
            'favoritable_type' => MediaFile::class,
        ]);

        return true;
    }

    public function getUserUsage(int $userId): int
    {
        return (int) MediaFile::query()->byUser($userId)->sum('size');
    }

    public function getUserQuota(int $userId): int
    {
        return (int) config('media.quota.default_bytes', 1073741824);
    }

    public function hasQuotaFor(int $userId, int $additionalBytes): bool
    {
        if (! config('media.quota.enabled', false)) {
            return true;
        }

        return ($this->getUserUsage($userId) + $additionalBytes) <= $this->getUserQuota($userId);
    }

    /**
     * @return LengthAwarePaginator<MediaFile>
     */
    public function search(array $filters, int $userId, int $perPage = 20): LengthAwarePaginator
    {
        return MediaFile::query()
            ->byUser($userId)
            ->when(! empty($filters['query']), fn ($q) => $q->where('name', 'like', '%'.$filters['query'].'%'))
            ->when(! empty($filters['type']), fn ($q) => $q->where('type', $filters['type']))
            ->when(! empty($filters['min_size']), fn ($q) => $q->where('size', '>=', (int) $filters['min_size']))
            ->when(! empty($filters['max_size']), fn ($q) => $q->where('size', '<=', (int) $filters['max_size']))
            ->when(! empty($filters['mime_type']), fn ($q) => $q->where('mime_type', $filters['mime_type']))
            ->when(isset($filters['min_width']), fn ($q) => $q->whereRaw("JSON_EXTRACT(metadata, '$.width') >= ?", [(int) $filters['min_width']]))
            ->when(isset($filters['min_height']), fn ($q) => $q->whereRaw("JSON_EXTRACT(metadata, '$.height') >= ?", [(int) $filters['min_height']]))
            ->when(! empty($filters['created_from']), fn ($q) => $q->where('created_at', '>=', $filters['created_from']))
            ->when(! empty($filters['created_to']), fn ($q) => $q->where('created_at', '<=', $filters['created_to']))
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getActiveDisk(): string
    {
        return session('media_active_disk', config('media.default_disk', 'media'));
    }

    /**
     * @throws \RuntimeException|HttpResponseException
     */
    public function validateExternalUrl(string $url): void
    {
        $parsed = parse_url($url);

        if (! in_array($parsed['scheme'] ?? '', ['http', 'https'], true)) {
            abort(422, 'Solo se permiten URLs http/https');
        }

        $host = $parsed['host'] ?? '';

        // Double DNS resolution to mitigate DNS rebinding attacks
        $ip1 = gethostbyname($host);
        usleep(50000);
        $ip2 = gethostbyname($host);

        if ($ip1 !== $ip2) {
            abort(422, 'Resolución DNS inestable');
        }

        $isPrivate = ! filter_var(
            $ip1,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );

        if ($isPrivate) {
            abort(422, 'No se permiten URLs de redes privadas');
        }

        // Check IPv6 AAAA records for private/loopback ranges
        $records = @dns_get_record($host, DNS_AAAA) ?: [];

        foreach ($records as $record) {
            if (isset($record['ipv6']) && preg_match('/^(::1|fc|fd|fe80:|ff)/i', $record['ipv6'])) {
                abort(422, 'No se permiten URLs a IPv6 de redes privadas/local');
            }
        }
    }

    public function extractImageMetadata(UploadedFile $file): array
    {
        if (! str_starts_with($file->getMimeType(), 'image/')) {
            return [];
        }

        $dimensions = @getimagesize($file->getRealPath());

        return $dimensions ? ['width' => $dimensions[0], 'height' => $dimensions[1]] : [];
    }

    /**
     * Calculate a 64-bit perceptual hash (pHash) for an image file.
     * Returns 16 lowercase hex characters, or null on failure.
     */
    public function calculatePhash(string $imagePath): ?string
    {
        try {
            $img = (new ImageManager(new GdDriver))
                ->read($imagePath)
                ->resize(8, 8)
                ->greyscale();

            $pixels = [];

            for ($y = 0; $y < 8; $y++) {
                for ($x = 0; $x < 8; $x++) {
                    $color = $img->pickColor($x, $y);
                    $pixels[] = (int) (($color->red()->value() + $color->green()->value() + $color->blue()->value()) / 3);
                }
            }

            $avg = array_sum($pixels) / 64;
            $bits = array_map(fn ($p) => $p > $avg ? 1 : 0, $pixels);

            $hex = '';

            for ($i = 0; $i < 64; $i += 4) {
                $nibble = ($bits[$i] << 3) | ($bits[$i + 1] << 2) | ($bits[$i + 2] << 1) | $bits[$i + 3];
                $hex .= dechex($nibble);
            }

            return $hex;
        } catch (\Throwable $e) {
            Log::warning('pHash calculation failed', ['path' => $imagePath, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Generate a tiny base64-encoded WebP placeholder (LQIP) for an image.
     * Uses Imagick if available (supports AVIF/WebP/PNG/JPEG), otherwise GD.
     */
    private function generatePlaceholder(string $path, string $disk): ?string
    {
        if (! Storage::disk($disk)->exists($path)) {
            return null;
        }

        try {
            $content = Storage::disk($disk)->get($path);
            $driver = extension_loaded('imagick') ? new ImagickDriver : new GdDriver;
            $manager = new ImageManager($driver);
            $image = $manager->read($content);

            $tiny = $image->scale(width: 20);
            $encoded = (string) $tiny->toWebp(30);

            return 'data:image/webp;base64,'.base64_encode($encoded);
        } catch (\Throwable $e) {
            Log::warning('Placeholder generation failed', [
                'path' => $path,
                'disk' => $disk,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Convert a stored image file to WebP. Returns new path, size and hash,
     * or null on failure. Original file is NOT deleted here.
     */
    private function convertStoredFileToWebp(string $path, string $disk): ?array
    {
        if (! Storage::disk($disk)->exists($path)) {
            return null;
        }

        try {
            $content = Storage::disk($disk)->get($path);
            $manager = new ImageManager(new GdDriver);
            $image = $manager->read($content);

            $webpContent = (string) $image->toWebp((int) config('media.image_optimization.quality', 85));

            $webpPath = preg_replace('/\.(jpe?g|png)$/i', '.webp', $path);
            Storage::disk($disk)->put($webpPath, $webpContent);

            $fullPath = Storage::disk($disk)->path($webpPath);

            return [
                'path' => $webpPath,
                'size' => filesize($fullPath),
                'hash' => hash_file('sha256', $fullPath),
            ];
        } catch (\Throwable $e) {
            Log::warning('WebP conversion failed during upload', [
                'path' => $path,
                'disk' => $disk,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function replaceExtensionWithWebp(string $filename): string
    {
        return preg_replace('/\.(jpe?g|png)$/i', '.webp', $filename);
    }

    private function replaceExtensionWithAvif(string $filename): string
    {
        return preg_replace('/\.(jpe?g|png|webp)$/i', '.avif', $filename);
    }

    /**
     * Convert a stored image to AVIF using Imagick. Returns path, size, hash
     * and optionally generates a WebP fallback. Original is NOT deleted.
     */
    private function convertStoredFileToAvif(string $path, string $disk): ?array
    {
        if (! Storage::disk($disk)->exists($path)) {
            return null;
        }

        try {
            $content = Storage::disk($disk)->get($path);
            $manager = new ImageManager(new ImagickDriver);
            $image = $manager->read($content);

            $quality = (int) config('media.avif_quality', 60);
            $avifContent = (string) $image->toAvif($quality);

            $avifPath = preg_replace('/\.(jpe?g|png|webp)$/i', '.avif', $path);
            Storage::disk($disk)->put($avifPath, $avifContent);

            $fullPath = Storage::disk($disk)->path($avifPath);
            $avifSize = filesize($fullPath);
            $avifHash = hash_file('sha256', $fullPath);

            // Generate WebP fallback
            $webpContent = (string) $image->toWebp((int) config('media.image_optimization.quality', 85));
            $webpPath = preg_replace('/\.(jpe?g|png|webp)$/i', '.webp', $path);
            Storage::disk($disk)->put($webpPath, $webpContent);

            return [
                'path' => $avifPath,
                'size' => $avifSize,
                'hash' => $avifHash,
                'fallback_webp' => $webpPath,
            ];
        } catch (\Throwable $e) {
            Log::warning('AVIF conversion failed during upload', [
                'path' => $path,
                'disk' => $disk,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Find files visually similar to the given file using Hamming distance on pHash.
     *
     * @return Collection<int, MediaFile>
     */
    public function findSimilar(MediaFile $file, int $maxDistance = 10, int $limit = 20): Collection
    {
        if (! $file->phash) {
            return collect();
        }

        $prefix = substr($file->phash, 0, 4);

        $candidates = MediaFile::query()
            ->where('id', '!=', $file->id)
            ->whereNotNull('phash')
            ->where('phash', 'like', $prefix.'%')
            ->where('user_id', $file->user_id)
            ->limit(500)
            ->get();

        return $candidates
            ->map(function (MediaFile $candidate) use ($file): MediaFile {
                $candidate->hamming_distance = $this->hammingDistance($file->phash, $candidate->phash);

                return $candidate;
            })
            ->filter(fn (MediaFile $c) => $c->hamming_distance <= $maxDistance)
            ->sortBy('hamming_distance')
            ->take($limit)
            ->values();
    }

    private function hammingDistance(string $a, string $b): int
    {
        if (strlen($a) !== strlen($b)) {
            return 64;
        }

        $distance = 0;

        for ($i = 0; $i < strlen($a); $i++) {
            $xor = hexdec($a[$i]) ^ hexdec($b[$i]);
            $distance += substr_count(decbin($xor), '1');
        }

        return $distance;
    }
}
