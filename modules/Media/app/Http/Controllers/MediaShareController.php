<?php

namespace Modules\Media\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Media\Http\Requests\StoreMediaShareRequest;
use Modules\Media\Models\MediaFile;
use Modules\Media\Models\MediaFolder;
use Modules\Media\Models\MediaShare;

class MediaShareController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $shares = MediaShare::query()
            ->with(['shareable', 'sharedBy', 'sharedWith'])
            ->where(fn ($q) => $q
                ->where('shared_by_user_id', $userId)
                ->orWhere('shared_with_user_id', $userId)
            )
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json(['success' => true, 'data' => $shares]);
    }

    public function store(StoreMediaShareRequest $request): JsonResponse
    {
        $data = $request->validated();

        $shareable = $data['shareable_type'] === 'file'
            ? MediaFile::query()->findOrFail($data['shareable_id'])
            : MediaFolder::query()->findOrFail($data['shareable_id']);

        $share = MediaShare::query()->updateOrCreate(
            [
                'shareable_id' => $shareable->id,
                'shareable_type' => $shareable::class,
                'shared_with_user_id' => $data['shared_with_user_id'],
            ],
            [
                'shared_by_user_id' => $request->user()->id,
                'role' => $data['role'],
                'expires_at' => $data['expires_at'] ?? null,
            ]
        );

        $share->load(['sharedBy', 'sharedWith']);

        return response()->json(['success' => true, 'message' => 'Compartido correctamente.', 'data' => $share], 201);
    }

    public function destroy(MediaShare $share): JsonResponse
    {
        if ($share->shared_by_user_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'No tienes permiso para revocar este acceso.'], 403);
        }

        $share->delete();

        return response()->json(['success' => true, 'message' => 'Acceso revocado correctamente.']);
    }
}
