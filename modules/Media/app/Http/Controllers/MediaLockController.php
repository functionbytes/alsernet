<?php

namespace Modules\Media\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Media\Models\MediaFile;
use Modules\Media\Services\MediaLockService;

class MediaLockController extends Controller
{
    public function __construct(
        private readonly MediaLockService $locks
    ) {}

    public function acquire(MediaFile $file): JsonResponse
    {
        $userId = auth()->id();
        $acquired = $this->locks->acquire($file->id, $userId);

        if ($acquired) {
            return response()->json(['success' => true, 'message' => 'Bloqueo adquirido.', 'holder_id' => $userId]);
        }

        $holderId = $this->locks->holder($file->id);

        return response()->json([
            'success' => false,
            'message' => 'El archivo está bloqueado por otro usuario.',
            'holder_id' => $holderId,
        ], 423);
    }

    public function release(MediaFile $file): JsonResponse
    {
        $released = $this->locks->release($file->id, auth()->id());

        if ($released) {
            return response()->json(['success' => true, 'message' => 'Bloqueo liberado.']);
        }

        return response()->json(['success' => false, 'message' => 'No eres el titular del bloqueo.'], 403);
    }

    public function status(MediaFile $file): JsonResponse
    {
        $holderId = $this->locks->holder($file->id);
        $holderName = null;

        if ($holderId !== null) {
            $holderName = User::query()->find($holderId)?->name;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'locked' => $holderId !== null,
                'holder_id' => $holderId,
                'holder_name' => $holderName,
            ],
        ]);
    }
}
