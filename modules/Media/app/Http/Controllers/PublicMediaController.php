<?php

namespace Modules\Media\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\MediaAccessLog;
use Modules\Media\Models\MediaFile;
use Modules\Media\Models\MediaFolder;
use Modules\Media\Models\MediaShareRevocation;

class PublicMediaController extends Controller
{
    public function showShared(string $token): mixed
    {
        try {
            $padded = strtr($token, '-_', '+/');
            $padded .= str_repeat('=', (4 - strlen($padded) % 4) % 4);
            $payload = json_decode(base64_decode($padded), true, 5, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            abort(404);
        }

        abort_unless(isset($payload['id'], $payload['exp'], $payload['sig']), 404);
        abort_if($payload['exp'] < time(), 410, 'Enlace expirado');

        $expectedSig = hash_hmac(
            'sha256',
            $payload['id'].'|'.$payload['exp'],
            config('app.key')
        );

        abort_unless(hash_equals($expectedSig, $payload['sig']), 404);

        if (MediaShareRevocation::where('token_hash', hash('sha256', $token))->exists()) {
            abort(410, 'Enlace revocado');
        }

        $file = MediaFile::query()->whereKey($payload['id'])->firstOrFail();

        $this->logAccess($file, 'share_access', $token);

        $disk = $file->disk ?? config('media.default_disk', 'media');

        return $this->serveFile(
            Storage::disk($disk)->path($file->url),
            $file,
            'private, max-age=3600'
        );
    }

    /**
     * Serve a file via indirect URL (hash-based, secure).
     */
    public function show(string $hash, string $id): mixed
    {
        abort_if(sha1($id) !== $hash, 404);

        $mediaFile = MediaFile::query()->whereKey($id)->firstOrFail();

        $this->logAccess($mediaFile, 'view');

        $disk = $mediaFile->disk ?? config('media.default_disk', 'media');
        $filePath = Storage::disk($disk)->path($mediaFile->url);

        if ($mediaFile->visibility === 'private') {
            abort_unless(auth()->check(), 403);
            $this->authorize('view', $mediaFile);

            return $this->serveFile($filePath, $mediaFile, 'private, max-age=3600');
        }

        // Content negotiation for images: serve modern formats if available
        if (str_starts_with($mediaFile->mime_type, 'image/')) {
            $accept = request()->header('Accept', '');
            $thumbs = $mediaFile->metadata['thumbnails'] ?? [];

            if (str_contains($accept, 'image/avif') && isset($thumbs['avif'])) {
                return $this->serveFile(
                    Storage::disk($disk)->path($thumbs['avif']),
                    $mediaFile,
                    'public, max-age=31536000, immutable',
                    'image/avif'
                );
            }

            if (str_contains($accept, 'image/webp') && isset($thumbs['webp'])) {
                return $this->serveFile(
                    Storage::disk($disk)->path($thumbs['webp']),
                    $mediaFile,
                    'public, max-age=31536000, immutable',
                    'image/webp'
                );
            }
        }

        return $this->serveFile($filePath, $mediaFile, 'public, max-age=31536000, immutable');
    }

    private function serveFile(string $path, MediaFile $mediaFile, string $cacheControl, ?string $contentType = null): mixed
    {
        $etag = '"'.md5($mediaFile->file_hash.'|'.$mediaFile->updated_at->timestamp).'"';

        if (request()->header('If-None-Match') === $etag) {
            return response()->noContent(304, [
                'Cache-Control' => $cacheControl,
                'ETag' => $etag,
            ]);
        }

        $headers = [
            'Cache-Control' => $cacheControl,
            'ETag' => $etag,
        ];

        if ($contentType) {
            $headers['Content-Type'] = $contentType;
        }

        // Gzip compression for SVGs when the browser accepts it
        if ($mediaFile->mime_type === 'image/svg+xml' && str_contains(request()->header('Accept-Encoding', ''), 'gzip')) {
            $compressed = gzencode(file_get_contents($path), 9);
            if ($compressed !== false) {
                $headers['Content-Encoding'] = 'gzip';
                $headers['Content-Length'] = strlen($compressed);
                $headers['Vary'] = 'Accept-Encoding';

                return response($compressed, 200, $headers);
            }
        }

        return response()->file($path, $headers);
    }

    /**
     * Serve all non-private files in a shared folder (token-based).
     */
    public function showSharedFolder(string $token): mixed
    {
        try {
            $padded = strtr($token, '-_', '+/');
            $padded .= str_repeat('=', (4 - strlen($padded) % 4) % 4);
            $payload = json_decode(base64_decode($padded), true, 5, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            abort(404);
        }

        abort_unless(isset($payload['fid'], $payload['exp'], $payload['sig']), 404);
        abort_if($payload['exp'] < time(), 410, 'Enlace expirado');

        $expectedSig = hash_hmac(
            'sha256',
            'folder:'.$payload['fid'].'|'.$payload['exp'],
            config('app.key')
        );

        abort_unless(hash_equals($expectedSig, $payload['sig']), 404);

        $folder = MediaFolder::query()->findOrFail($payload['fid']);

        $files = MediaFile::query()
            ->where('folder_id', $folder->id)
            ->where('visibility', '!=', 'private')
            ->get(['id', 'uid', 'name', 'mime_type', 'size', 'url', 'created_at']);

        return response()->json([
            'success' => true,
            'folder' => [
                'id' => $folder->id,
                'name' => $folder->name,
            ],
            'files' => $files->map(fn (MediaFile $f) => [
                'id' => $f->id,
                'uid' => $f->uid,
                'name' => $f->name,
                'mime_type' => $f->mime_type,
                'size' => $f->size,
                'created_at' => $f->created_at->toIso8601String(),
            ]),
        ]);
    }

    private function logAccess(MediaFile $file, string $action, ?string $shareToken = null): void
    {
        try {
            MediaAccessLog::create([
                'media_file_id' => $file->id,
                'user_id' => auth()->id(),
                'action' => $action,
                'ip_address' => request()->ip(),
                'user_agent' => substr((string) request()->userAgent(), 0, 512),
                'referer' => substr((string) request()->header('referer'), 0, 512),
                'share_token' => $shareToken ? hash('sha256', $shareToken) : null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('MediaAccessLog failed', ['error' => $e->getMessage()]);
        }
    }
}
