<?php

namespace Modules\Media\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Media\Http\Requests\MoveMediaFolderRequest;
use Modules\Media\Http\Requests\StoreMediaFolderRequest;
use Modules\Media\Http\Requests\UpdateMediaFolderRequest;
use Modules\Media\Models\MediaFolder;
use Modules\Media\Services\MediaFolderService;

class MediaFolderController extends Controller
{
    public function __construct(private readonly MediaFolderService $folderService) {}

    public function store(StoreMediaFolderRequest $request): JsonResponse
    {
        $parentId = $request->integer('parent_id') ?: null;
        $disk = session('media_active_disk', config('media.default_disk', 'media'));

        $folder = $this->folderService->create(
            $request->string('name'),
            $parentId,
            $disk,
            auth()->id(),
            $request->string('color') ?: null
        );

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

    public function rename(UpdateMediaFolderRequest $request, MediaFolder $folder): JsonResponse
    {
        $this->authorize('update', $folder);

        $this->folderService->rename($folder, $request->string('name'));

        return response()->json(['success' => true]);
    }

    public function delete(MediaFolder $folder): JsonResponse
    {
        $this->authorize('delete', $folder);

        $this->folderService->delete($folder);

        return response()->json(['success' => true, 'message' => 'Carpeta eliminada']);
    }

    public function restore(MediaFolder $folder): JsonResponse
    {
        $this->authorize('update', $folder);

        $this->folderService->restore($folder);

        return response()->json(['success' => true]);
    }

    public function move(MoveMediaFolderRequest $request, MediaFolder $folder): JsonResponse
    {
        $this->authorize('update', $folder);

        $this->folderService->move($folder, $request->integer('parent_id') ?: null);

        return response()->json(['success' => true]);
    }
}
