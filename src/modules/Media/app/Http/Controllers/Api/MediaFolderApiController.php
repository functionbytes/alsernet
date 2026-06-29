<?php

namespace Modules\Media\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Media\Http\Requests\MoveMediaFolderRequest;
use Modules\Media\Http\Requests\StoreMediaFolderRequest;
use Modules\Media\Http\Requests\UpdateMediaFolderRequest;
use Modules\Media\Http\Resources\FolderResource;
use Modules\Media\Models\MediaFolder;
use Modules\Media\Services\MediaFolderService;

class MediaFolderApiController extends Controller
{
    public function __construct(protected MediaFolderService $service) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MediaFolder::class);

        $folders = MediaFolder::query()
            ->byUser()
            ->withCount('files')
            ->when($request->filled('parent_id'), fn ($q) => $q->where('parent_id', $request->integer('parent_id')))
            ->when($request->boolean('root'), fn ($q) => $q->root())
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%'))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Carpetas obtenidas',
            'data' => FolderResource::collection($folders)->response()->getData(true),
        ]);
    }

    public function show(MediaFolder $folder): JsonResponse
    {
        $this->authorize('view', $folder);

        $folder->loadCount('files');

        return response()->json([
            'success' => true,
            'message' => 'Carpeta obtenida',
            'data' => new FolderResource($folder),
        ]);
    }

    public function store(StoreMediaFolderRequest $request): JsonResponse
    {
        $disk = $request->input('disk', config('media.default_disk', 'media'));

        $folder = $this->service->create(
            $request->validated('name'),
            $request->validated('parent_id'),
            $disk,
            Auth::id(),
            $request->validated('color')
        );

        return response()->json([
            'success' => true,
            'message' => 'Carpeta creada',
            'data' => new FolderResource($folder),
        ], 201);
    }

    public function update(UpdateMediaFolderRequest $request, MediaFolder $folder): JsonResponse
    {
        $this->authorize('update', $folder);

        $updated = $this->service->rename($folder, $request->validated('name'));

        if ($request->has('color')) {
            $updated->update(['color' => $request->validated('color')]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Carpeta actualizada',
            'data' => new FolderResource($updated),
        ]);
    }

    public function destroy(MediaFolder $folder): JsonResponse
    {
        $this->authorize('delete', $folder);
        $this->service->delete($folder);

        return response()->json(null, 204);
    }

    public function move(MoveMediaFolderRequest $request, MediaFolder $folder): JsonResponse
    {
        $this->authorize('update', $folder);

        $moved = $this->service->move($folder, $request->validated('parent_id'));

        return response()->json([
            'success' => true,
            'message' => 'Carpeta movida',
            'data' => new FolderResource($moved),
        ]);
    }

    public function restore(MediaFolder $folder): JsonResponse
    {
        $this->authorize('restore', $folder);
        $this->service->restore($folder);

        return response()->json([
            'success' => true,
            'message' => 'Carpeta restaurada',
        ]);
    }
}
