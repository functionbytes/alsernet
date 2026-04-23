<?php

namespace Modules\Media\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Http\Requests\CopyMediaFileRequest;
use Modules\Media\Http\Requests\MoveMediaFileRequest;
use Modules\Media\Http\Requests\UpdateMediaFileRequest;
use Modules\Media\Http\Requests\UploadFromUrlRequest;
use Modules\Media\Http\Requests\UploadMediaFileRequest;
use Modules\Media\Models\MediaAccessLog;
use Modules\Media\Models\MediaFile;
use Modules\Media\Models\MediaFileVersion;
use Modules\Media\Models\MediaShareRevocation;
use Modules\Media\Services\MediaFileService;

class MediaFileController extends Controller
{
    public function __construct(private readonly MediaFileService $fileService) {}

    public function upload(UploadMediaFileRequest $request): JsonResponse
    {
        $disk = $this->fileService->getActiveDisk();
        $result = $this->fileService->upload(
            $request->file('file'),
            $request->integer('folder_id') ?: null,
            $disk,
            auth()->id()
        );

        return response()->json([
            'success' => true,
            'duplicate' => $result['duplicate'],
            'file' => $this->formatFile($result['file']),
        ]);
    }

    public function uploadFromUrl(UploadFromUrlRequest $request): JsonResponse
    {
        $rateLimitKey = 'media-url-upload:'.auth()->id();

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            return response()->json(['message' => 'Demasiadas descargas. Intenta más tarde.'], 429);
        }

        RateLimiter::hit($rateLimitKey, 60);

        try {
            $mediaFile = $this->fileService->uploadFromUrl(
                $request->string('url'),
                $request->string('filename') ?: null,
                $request->integer('folder_id') ?: null,
                $this->fileService->getActiveDisk(),
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'file' => $this->formatFile($mediaFile),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Descarga fallida: '.$e->getMessage()], 422);
        }
    }

    public function rename(UpdateMediaFileRequest $request, MediaFile $file): JsonResponse
    {
        $this->authorize('update', $file);

        $this->fileService->rename($file, $request->string('name'));

        return response()->json(['success' => true]);
    }

    public function copy(CopyMediaFileRequest $request, MediaFile $file): JsonResponse
    {
        $this->authorize('create', MediaFile::class);

        try {
            $newFile = $this->fileService->copy($file, auth()->id());

            return response()->json([
                'success' => true,
                'file' => $this->formatFile($newFile),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Media file copy failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Error al copiar. Por favor, inténtalo de nuevo.'], 500);
        }
    }

    public function delete(MediaFile $file): JsonResponse
    {
        $this->authorize('delete', $file);

        $this->fileService->delete($file);

        return response()->json(['success' => true, 'message' => 'Archivo eliminado']);
    }

    public function restore(MediaFile $file): JsonResponse
    {
        $this->authorize('update', $file);

        $this->fileService->restore($file);

        return response()->json(['success' => true]);
    }

    public function move(MoveMediaFileRequest $request, MediaFile $file): JsonResponse
    {
        $this->authorize('update', $file);

        $this->fileService->move($file, $request->integer('folder_id') ?: null);

        return response()->json(['success' => true]);
    }

    public function toggleFavorite(MediaFile $file): JsonResponse
    {
        $this->authorize('update', $file);

        $isFavorite = $this->fileService->toggleFavorite($file, auth()->id());

        return response()->json([
            'success' => true,
            'is_favorite' => $isFavorite,
            'message' => $isFavorite ? 'Agregado a favoritos' : 'Eliminado de favoritos',
        ]);
    }

    public function uploadChunk(Request $request): JsonResponse
    {
        $this->authorize('create', MediaFile::class);

        $chunkSizeKb = (int) (config('media.chunk.chunk_size', 1048576) * 2 / 1024);

        $request->validate([
            'upload_id' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{8,64}$/'],
            'chunk_index' => ['required', 'integer', 'min:0'],
            'total_chunks' => ['required', 'integer', 'min:1', 'max:10000'],
            'chunk' => ['required', 'file', "max:{$chunkSizeKb}"],
        ]);

        $uploadId = $request->string('upload_id');
        $chunkIndex = $request->integer('chunk_index');
        $chunkDisk = config('media.chunk.storage.disk', 'local');
        $chunkDir = config('media.chunk.storage.chunks', 'chunks').'/'.$uploadId;

        Storage::disk($chunkDisk)->putFileAs(
            $chunkDir,
            $request->file('chunk'),
            sprintf('%05d.part', $chunkIndex)
        );

        return response()->json([
            'success' => true,
            'upload_id' => $uploadId,
            'chunk_index' => $chunkIndex,
            'received' => count(Storage::disk($chunkDisk)->files($chunkDir)),
        ]);
    }

    public function completeChunkUpload(Request $request): JsonResponse
    {
        $this->authorize('create', MediaFile::class);

        $request->validate([
            'upload_id' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{8,64}$/'],
            'filename' => ['required', 'string', 'max:255'],
            'total_chunks' => ['required', 'integer', 'min:1'],
            'folder_id' => ['nullable', 'integer', 'exists:media_folders,id'],
        ]);

        return $this->fileService->completeChunkUpload(
            $request->string('upload_id'),
            $request->string('filename'),
            $request->integer('total_chunks'),
            $request->integer('folder_id') ?: null,
            $this->fileService->getActiveDisk(),
            auth()->id()
        );
    }

    public function abortChunkUpload(string $uploadId): JsonResponse
    {
        $this->authorize('create', MediaFile::class);

        if (! preg_match('/^[A-Za-z0-9_-]{8,64}$/', $uploadId)) {
            return response()->json(['message' => 'upload_id inválido'], 422);
        }

        $chunkDisk = config('media.chunk.storage.disk', 'local');
        $chunkDir = config('media.chunk.storage.chunks', 'chunks').'/'.$uploadId;

        Storage::disk($chunkDisk)->deleteDirectory($chunkDir);

        return response()->json(['success' => true]);
    }

    public function similar(MediaFile $file): JsonResponse
    {
        $this->authorize('view', $file);

        $similar = $this->fileService->findSimilar($file);

        return response()->json([
            'success' => true,
            'data' => $similar->map(fn (MediaFile $f) => [
                'id' => $f->id,
                'name' => $f->name,
                'url' => $f->url,
                'distance' => $f->hamming_distance,
            ]),
        ]);
    }

    public function createShareLink(Request $request, MediaFile $file): JsonResponse
    {
        $this->authorize('view', $file);

        $request->validate([
            'ttl_minutes' => ['nullable', 'integer', 'min:1', 'max:10080'],
        ]);

        $ttl = $request->integer('ttl_minutes', 60);
        $expiresAt = now()->addMinutes($ttl);

        $token = hash_hmac(
            'sha256',
            $file->id.'|'.$expiresAt->timestamp,
            config('app.key')
        );

        $payload = base64_encode(json_encode([
            'id' => $file->id,
            'exp' => $expiresAt->timestamp,
            'sig' => $token,
        ]));

        $safe = strtr(rtrim($payload, '='), '+/', '-_');

        return response()->json([
            'success' => true,
            'url' => route('media.share', ['token' => $safe]),
            'expires_at' => $expiresAt->toIso8601String(),
        ]);
    }

    public function presignedUpload(Request $request): JsonResponse
    {
        $this->authorize('create', MediaFile::class);

        $request->validate([
            'filename' => ['required', 'string', 'max:255'],
            'mime_type' => ['required', 'string', 'max:100'],
            'size' => ['required', 'integer', 'min:1'],
            'folder_id' => ['nullable', 'integer', 'exists:media_folders,id'],
        ]);

        $disk = $this->fileService->getActiveDisk();
        $diskConfig = config("filesystems.disks.{$disk}");

        if (($diskConfig['driver'] ?? '') !== 's3') {
            return response()->json(['message' => 'Presigned upload solo disponible para disk S3'], 400);
        }

        $maxBytes = (int) config('media.max_upload_size', 104857600);
        if ($request->integer('size') > $maxBytes) {
            return response()->json(['message' => 'Excede tamaño máximo'], 413);
        }

        $folderId = $request->integer('folder_id') ?: null;
        $key = ($folderId ?? 0).'/'.uniqid('mdl_', true).'_'.$request->string('filename');

        try {
            $s3 = Storage::disk($disk)->getClient();
            $command = $s3->getCommand('PutObject', [
                'Bucket' => config("filesystems.disks.{$disk}.bucket"),
                'Key' => $key,
                'ContentType' => $request->string('mime_type'),
            ]);

            $presignedUrl = (string) $s3->createPresignedRequest($command, '+15 minutes')->getUri();

            return response()->json([
                'success' => true,
                'url' => $presignedUrl,
                'key' => $key,
                'expires_in' => 900,
                'method' => 'PUT',
                'headers' => ['Content-Type' => $request->string('mime_type')],
                'callback' => route('media.files.presigned-complete'),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'No se pudo generar URL: '.$e->getMessage()], 500);
        }
    }

    public function presignedComplete(Request $request): JsonResponse
    {
        $this->authorize('create', MediaFile::class);

        $request->validate([
            'key' => ['required', 'string', 'max:512'],
            'filename' => ['required', 'string', 'max:255'],
            'size' => ['required', 'integer', 'min:1'],
            'mime_type' => ['required', 'string', 'max:100'],
            'folder_id' => ['nullable', 'integer', 'exists:media_folders,id'],
        ]);

        $disk = $this->fileService->getActiveDisk();

        if (! Storage::disk($disk)->exists($request->string('key'))) {
            return response()->json(['message' => 'Archivo no encontrado en S3'], 404);
        }

        $mediaFile = MediaFile::create([
            'name' => $request->string('filename'),
            'mime_type' => $request->string('mime_type'),
            'size' => $request->integer('size'),
            'url' => $request->string('key'),
            'folder_id' => $request->integer('folder_id') ?: null,
            'user_id' => auth()->id(),
            'disk' => $disk,
        ]);

        return response()->json([
            'success' => true,
            'file' => [
                'id' => $mediaFile->id,
                'uid' => $mediaFile->uid,
                'name' => $mediaFile->name,
            ],
        ], 201);
    }

    public function versions(MediaFile $file): JsonResponse
    {
        $this->authorize('view', $file);

        $versions = MediaFileVersion::query()
            ->where('media_file_id', $file->id)
            ->with('user:id,name')
            ->orderByDesc('version_number')
            ->limit(100)
            ->get();

        return response()->json(['versions' => $versions]);
    }

    public function restoreVersion(MediaFile $file, int $versionId): JsonResponse
    {
        $this->authorize('update', $file);

        $this->fileService->restoreVersion($file, $versionId);

        return response()->json(['success' => true, 'message' => 'Versión restaurada']);
    }

    public function accessLogs(MediaFile $file): JsonResponse
    {
        $this->authorize('view', $file);

        $logs = MediaAccessLog::query()
            ->where('media_file_id', $file->id)
            ->with('user:id,name,email')
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();

        return response()->json([
            'logs' => $logs->map(fn (MediaAccessLog $l) => [
                'id' => $l->id,
                'user_id' => $l->user_id,
                'user_name' => $l->user?->name,
                'action' => $l->action,
                'ip_address' => $l->ip_address,
                'user_agent' => $l->user_agent,
                'created_at' => $l->created_at?->toIso8601String(),
            ]),
        ]);
    }

    public function setExpiration(Request $request, MediaFile $file): JsonResponse
    {
        $this->authorize('update', $file);

        $request->validate(['expires_at' => ['nullable', 'date', 'after:now']]);

        $file->update(['expires_at' => $request->input('expires_at')]);

        return response()->json(['success' => true, 'expires_at' => $file->expires_at?->toIso8601String()]);
    }

    public function revokeShareLink(Request $request, MediaFile $file): JsonResponse
    {
        $this->authorize('update', $file);

        $request->validate(['token' => ['required', 'string']]);

        MediaShareRevocation::updateOrCreate(
            ['token_hash' => hash('sha256', $request->string('token'))],
            [
                'revoked_by_user_id' => auth()->id(),
                'reason' => $request->input('reason'),
                'revoked_at' => now(),
            ]
        );

        return response()->json(['success' => true]);
    }

    private function formatFile(MediaFile $file): array
    {
        return [
            'id' => $file->id,
            'uid' => $file->uid,
            'name' => $file->name,
            'type' => $file->type,
            'mime_type' => $file->mime_type,
            'size' => $file->human_size,
            'url' => $file->url,
            'alt' => $file->alt,
        ];
    }
}
