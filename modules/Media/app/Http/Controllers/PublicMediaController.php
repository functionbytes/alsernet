<?php

namespace Modules\Media\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\MediaFile;

class PublicMediaController extends Controller
{
    /**
     * Serve a file via indirect URL (hash-based, secure).
     */
    public function show(string $hash, string $id): mixed
    {
        abort_if(sha1($id) !== $hash, 404);

        $mediaFile = MediaFile::query()->whereKey($id)->firstOrFail();

        if ($mediaFile->visibility === 'private') {
            abort_unless(auth()->check(), 403);
            $this->authorize('view', $mediaFile);

            $disk = $mediaFile->disk ?? config('media.default_disk', 'media');

            return response()->download(Storage::disk($disk)->path($mediaFile->url));
        }

        $disk = $mediaFile->disk ?? config('media.default_disk', 'media');

        return response()->file(Storage::disk($disk)->path($mediaFile->url));
    }
}
