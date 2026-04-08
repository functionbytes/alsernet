<?php

namespace Modules\Media\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Jobs\ConvertToWebpJob;
use Modules\Media\Models\MediaFile;
use Modules\Media\Repositories\Interfaces\MediaSettingInterface;
use Symfony\Component\HttpFoundation\File\File;

class MediaFileController extends Controller
{
    public function __construct(protected MediaSettingInterface $settingRepository) {}

    public function upload(Request $request): JsonResponse
    {
        $this->authorize('create', MediaFile::class);

        $request->validate([
            'file' => 'required|file|max:102400|mimes:jpg,jpeg,png,gif,webp,svg,ico,pdf,doc,docx,xls,xlsx,zip,mp4,mp3,txt,csv',
            'folder_id' => 'nullable|exists:media_folders,id',
        ]);

        $file = $request->file('file');
        $folderId = $request->integer('folder_id') ?: null;
        $disk = $this->getActiveDisk();

        $hash = hash_file('sha256', $file->getRealPath());

        $existing = MediaFile::query()
            ->where('file_hash', $hash)
            ->where('disk', $disk)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => true,
                'duplicate' => true,
                'file' => $this->formatFile($existing),
            ]);
        }

        $storedPath = $file->store((string) ($folderId ?? 0), $disk);

        $mediaFile = MediaFile::create([
            'name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'url' => $storedPath,
            'folder_id' => $folderId,
            'user_id' => auth()->id(),
            'disk' => $disk,
            'file_hash' => $hash,
            'metadata' => $this->extractImageMetadata($file),
        ]);

        ConvertToWebpJob::dispatch($mediaFile->id);

        return response()->json([
            'success' => true,
            'duplicate' => false,
            'file' => $this->formatFile($mediaFile),
        ]);
    }

    public function uploadFromUrl(Request $request): JsonResponse
    {
        $this->authorize('create', MediaFile::class);

        $request->validate([
            'url' => 'required|url|max:2048',
            'filename' => 'nullable|string|max:255',
            'folder_id' => 'nullable|exists:media_folders,id',
        ]);

        try {
            $url = $request->string('url');
            $folderId = $request->integer('folder_id') ?: null;
            $disk = $this->getActiveDisk();

            $this->validateExternalUrl($url);

            $response = Http::timeout(10)->get($url);

            if (! $response->successful()) {
                return response()->json(['message' => 'No se pudo descargar el archivo desde la URL'], 400);
            }

            $content = $response->body();

            $filename = $request->string('filename') ?: basename(parse_url($url, PHP_URL_PATH)) ?: 'descargado-'.time();
            if (! str_contains($filename, '.')) {
                $filename .= '.bin';
            }

            $size = strlen($content);
            if ($size > 104857600) {
                return response()->json(['message' => 'El archivo excede el límite de 100MB'], 413);
            }

            $tempPath = sys_get_temp_dir().'/'.bin2hex(random_bytes(8)).'_'.$filename;
            file_put_contents($tempPath, $content);

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
                @unlink($tempPath);

                return response()->json(['message' => 'Tipo de archivo no permitido: '.$mimeType], 422);
            }

            $storedPath = Storage::disk($disk)->putFileAs(
                (string) ($folderId ?? 0),
                new File($tempPath),
                $filename
            );

            @unlink($tempPath);

            $mediaFile = MediaFile::create([
                'name' => $filename,
                'mime_type' => $mimeType,
                'size' => $size,
                'url' => $storedPath,
                'folder_id' => $folderId,
                'user_id' => auth()->id(),
                'disk' => $disk,
            ]);

            return response()->json([
                'success' => true,
                'file' => $this->formatFile($mediaFile),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error. Por favor, inténtalo de nuevo.'], 500);
        }
    }

    public function rename(Request $request, MediaFile $file): JsonResponse
    {
        $this->authorize('update', $file);

        $request->validate(['name' => 'required|string|max:255']);

        $file->update(['name' => $request->string('name')]);

        return response()->json(['success' => true]);
    }

    public function copy(MediaFile $file): JsonResponse
    {
        $this->authorize('create', MediaFile::class);

        try {
            $disk = $file->disk ?? 'media';
            $originalPath = preg_replace('/^\d+\//', '', $file->url);

            if (! Storage::disk($disk)->exists($originalPath)) {
                return response()->json(['message' => 'El archivo original no existe'], 404);
            }

            $ext = pathinfo($file->name, PATHINFO_EXTENSION);
            $base = pathinfo($file->name, PATHINFO_FILENAME);
            $newName = $base.'-(copia).'.$ext;
            $dir = dirname($originalPath);
            $newPath = $dir.'/'.pathinfo($base, PATHINFO_FILENAME).'-copia-'.uniqid().'.'.$ext;

            Storage::disk($disk)->copy($originalPath, $newPath);

            $newFile = MediaFile::create([
                'name' => $newName,
                'mime_type' => $file->mime_type,
                'size' => $file->size,
                'url' => $newPath,
                'folder_id' => $file->folder_id,
                'user_id' => auth()->id(),
                'disk' => $disk,
                'metadata' => $file->metadata ?? [],
            ]);

            return response()->json([
                'success' => true,
                'file' => $this->formatFile($newFile),
            ]);
        } catch (\Exception $e) {
            Log::error('Media file copy failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Error al copiar. Por favor, inténtalo de nuevo.'], 500);
        }
    }

    public function delete(MediaFile $file): JsonResponse
    {
        $this->authorize('delete', $file);
        $file->delete();

        return response()->json(['success' => true, 'message' => 'Archivo eliminado']);
    }

    public function restore(MediaFile $file): JsonResponse
    {
        $this->authorize('update', $file);
        $file->restore();

        return response()->json(['success' => true]);
    }

    public function move(Request $request, MediaFile $file): JsonResponse
    {
        $this->authorize('update', $file);

        $request->validate(['folder_id' => 'nullable|exists:media_folders,id']);

        $file->update(['folder_id' => $request->integer('folder_id') ?: null]);

        return response()->json(['success' => true]);
    }

    public function toggleFavorite(MediaFile $file): JsonResponse
    {
        $this->authorize('update', $file);

        $userId = auth()->id();
        $favorites = $this->settingRepository->getFavorites($userId);
        $item = ['id' => $file->id, 'is_folder' => false];
        $isFavorite = collect($favorites)->contains(fn ($f) => $f['id'] == $file->id && ! $f['is_folder']);

        if ($isFavorite) {
            $this->settingRepository->removeFavorites($userId, [$item]);
        } else {
            $this->settingRepository->addFavorites($userId, [$item]);
        }

        return response()->json([
            'success' => true,
            'is_favorite' => ! $isFavorite,
            'message' => ! $isFavorite ? 'Agregado a favoritos' : 'Eliminado de favoritos',
        ]);
    }

    private function validateExternalUrl(string $url): void
    {
        $parsed = parse_url($url);

        if (! in_array($parsed['scheme'] ?? '', ['http', 'https'], true)) {
            abort(422, 'Solo se permiten URLs http/https');
        }

        $host = $parsed['host'] ?? '';
        $ip = gethostbyname($host);

        $isPrivate = ! filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );

        if ($isPrivate) {
            abort(422, 'No se permiten URLs de redes privadas');
        }
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

    private function extractImageMetadata(UploadedFile $file): array
    {
        if (! str_starts_with($file->getMimeType(), 'image/')) {
            return [];
        }

        $dimensions = @getimagesize($file->getRealPath());

        return $dimensions ? ['width' => $dimensions[0], 'height' => $dimensions[1]] : [];
    }

    private function getActiveDisk(): string
    {
        return session('media_active_disk', config('media.default_disk', 'media'));
    }
}
