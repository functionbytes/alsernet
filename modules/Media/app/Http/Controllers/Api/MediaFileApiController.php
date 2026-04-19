<?php

namespace Modules\Media\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Media\Http\Requests\CopyMediaFileRequest;
use Modules\Media\Http\Requests\MoveMediaFileRequest;
use Modules\Media\Http\Requests\UpdateMediaFileRequest;
use Modules\Media\Http\Requests\UploadFromUrlRequest;
use Modules\Media\Http\Requests\UploadMediaFileRequest;
use Modules\Media\Http\Resources\FileResource;
use Modules\Media\Models\MediaFile;
use Modules\Media\Services\MediaFileService;

class MediaFileApiController extends Controller
{
    public function __construct(protected MediaFileService $service) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MediaFile::class);

        $files = MediaFile::query()
            ->byUser()
            ->when($request->filled('folder_id'), fn ($q) => $q->where('folder_id', $request->integer('folder_id')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%'))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Archivos obtenidos',
            'data' => FileResource::collection($files)->response()->getData(true),
        ]);
    }

    public function show(MediaFile $file): JsonResponse
    {
        $this->authorize('view', $file);

        return response()->json([
            'success' => true,
            'message' => 'Archivo obtenido',
            'data' => new FileResource($file),
        ]);
    }

    public function store(UploadMediaFileRequest $request): JsonResponse
    {
        $disk = $request->input('disk', config('media.default_disk', 'media'));

        $result = DB::transaction(fn () => $this->service->upload(
            $request->file('file'),
            $request->input('folder_id'),
            $disk,
            Auth::id()
        ));

        $isDuplicate = $result['duplicate'] ?? false;

        return response()->json([
            'success' => true,
            'message' => $isDuplicate ? 'Duplicado detectado' : 'Archivo subido',
            'data' => new FileResource($result['file']),
        ], 201);
    }

    public function update(UpdateMediaFileRequest $request, MediaFile $file): JsonResponse
    {
        $this->authorize('update', $file);

        $updated = $this->service->rename($file, $request->validated('name'));

        return response()->json([
            'success' => true,
            'message' => 'Archivo actualizado',
            'data' => new FileResource($updated),
        ]);
    }

    public function destroy(MediaFile $file): JsonResponse
    {
        $this->authorize('delete', $file);
        $this->service->delete($file);

        return response()->json(null, 204);
    }

    public function move(MoveMediaFileRequest $request, MediaFile $file): JsonResponse
    {
        $this->authorize('update', $file);

        $moved = $this->service->move($file, $request->validated('folder_id'));

        return response()->json([
            'success' => true,
            'message' => 'Archivo movido',
            'data' => new FileResource($moved),
        ]);
    }

    public function copy(CopyMediaFileRequest $request, MediaFile $file): JsonResponse
    {
        $this->authorize('update', $file);

        $copy = DB::transaction(fn () => $this->service->copy($file, Auth::id()));

        return response()->json([
            'success' => true,
            'message' => 'Archivo copiado',
            'data' => new FileResource($copy),
        ], 201);
    }

    public function restore(MediaFile $file): JsonResponse
    {
        $this->authorize('restore', $file);
        $this->service->restore($file);

        return response()->json([
            'success' => true,
            'message' => 'Archivo restaurado',
        ]);
    }

    public function similar(MediaFile $file): JsonResponse
    {
        $this->authorize('view', $file);

        $similar = $this->service->findSimilar($file);

        return response()->json([
            'success' => true,
            'message' => 'Archivos similares',
            'data' => FileResource::collection($similar),
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
            'message' => 'Enlace compartido generado',
            'data' => [
                'url' => route('media.share', ['token' => $safe]),
                'expires_at' => $expiresAt->toIso8601String(),
            ],
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MediaFile::class);

        $request->validate([
            'query' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'in:image,video,audio,document,pdf,zip'],
            'min_size' => ['nullable', 'integer', 'min:0'],
            'max_size' => ['nullable', 'integer', 'min:0'],
            'mime_type' => ['nullable', 'string', 'max:100'],
            'min_width' => ['nullable', 'integer', 'min:1'],
            'min_height' => ['nullable', 'integer', 'min:1'],
            'created_from' => ['nullable', 'date'],
            'created_to' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);

        $results = $this->service->search(
            $request->validated(),
            Auth::id(),
            $request->integer('per_page', 20)
        );

        return response()->json([
            'success' => true,
            'message' => 'Resultados de búsqueda',
            'data' => FileResource::collection($results)->response()->getData(true),
        ]);
    }

    public function uploadFromUrl(UploadFromUrlRequest $request): JsonResponse
    {
        $disk = $request->input('disk', config('media.default_disk', 'media'));

        $file = DB::transaction(fn () => $this->service->uploadFromUrl(
            $request->validated('url'),
            $request->validated('filename'),
            $request->validated('folder_id'),
            $disk,
            Auth::id()
        ));

        return response()->json([
            'success' => true,
            'message' => 'Archivo descargado',
            'data' => new FileResource($file),
        ], 201);
    }
}
