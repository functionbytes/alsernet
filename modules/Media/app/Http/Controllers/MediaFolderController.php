<?php

namespace Modules\Media\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Media\Models\MediaFolder;
use Modules\Media\Repositories\Interfaces\MediaFolderInterface;

class MediaFolderController extends Controller
{
    public function __construct(protected MediaFolderInterface $folderRepository) {}

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', MediaFolder::class);

        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:media_folders,id',
        ]);

        $parentId = $request->integer('parent_id') ?: null;
        $disk = session('media_active_disk', config('media.default_disk', 'media'));

        $folder = MediaFolder::create([
            'name' => $this->folderRepository->createName($request->string('name'), $parentId),
            'slug' => $this->folderRepository->createSlug($request->string('name'), $parentId),
            'parent_id' => $parentId,
            'user_id' => auth()->id(),
            'disk' => $disk,
        ]);

        return response()->json([
            'success' => true,
            'folder' => [
                'id' => $folder->id,
                'uid' => $folder->uid,
                'name' => $folder->name,
                'color' => $folder->color,
                'slug' => $folder->slug,
            ],
        ]);
    }

    public function rename(Request $request, MediaFolder $folder): JsonResponse
    {
        $this->authorize('update', $folder);

        $request->validate(['name' => 'required|string|max:255']);

        $folder->update(['name' => $request->string('name')]);

        return response()->json(['success' => true]);
    }

    public function delete(MediaFolder $folder): JsonResponse
    {
        $this->authorize('delete', $folder);
        $folder->delete();

        return response()->json(['success' => true, 'message' => 'Carpeta eliminada']);
    }

    public function restore(MediaFolder $folder): JsonResponse
    {
        $this->authorize('update', $folder);
        $this->folderRepository->restoreFolder($folder->id);

        return response()->json(['success' => true]);
    }

    public function move(Request $request, MediaFolder $folder): JsonResponse
    {
        $this->authorize('update', $folder);

        $request->validate(['parent_id' => 'nullable|exists:media_folders,id']);

        $folder->update(['parent_id' => $request->integer('parent_id') ?: null]);

        return response()->json(['success' => true]);
    }
}
