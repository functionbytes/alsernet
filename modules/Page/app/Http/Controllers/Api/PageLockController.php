<?php

namespace Modules\Page\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Page\Models\Page;
use Modules\Page\Services\PageLockService;

class PageLockController extends Controller
{
    public function __construct(
        private readonly PageLockService $lockService
    ) {}

    /**
     * Obtener información del lock de una página
     * GET /api/v1/pages/{page}/lock
     */
    public function check(Page $page): JsonResponse
    {
        $this->authorize('view', $page);

        $lockInfo = $this->lockService->getLockInfo($page, auth()->user());

        if (! $lockInfo) {
            return response()->json([
                'success' => true,
                'message' => 'Página no está bloqueada',
                'data' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Página está bloqueada',
            'data' => $lockInfo,
        ]);
    }

    /**
     * Adquirir un lock para la página
     * POST /api/v1/pages/{page}/lock
     */
    public function acquire(Request $request, Page $page): JsonResponse
    {
        $this->authorize('update', $page);

        // Verificar si está bloqueada por otro usuario
        if ($this->lockService->isLockedByOther($page, auth()->user())) {
            $lockInfo = $this->lockService->getLockInfo($page, auth()->user());

            return response()->json([
                'success' => false,
                'message' => 'Página está siendo editada por otro usuario',
                'data' => $lockInfo,
            ], 423); // 423 Locked
        }

        // Adquirir o renovar lock
        $lock = $this->lockService->acquireLock(
            $page,
            auth()->user(),
            $request->session()?->getId()
        );

        return response()->json([
            'success' => true,
            'message' => 'Lock adquirido',
            'data' => [
                'lock_id' => $lock->id,
                'acquired_at' => $lock->locked_at->toIso8601String(),
                'expires_at' => $lock->expires_at->toIso8601String(),
                'expires_in_human' => $lock->expires_at->diffForHumans(),
            ],
        ]);
    }

    /**
     * Renovar un lock existente
     * PATCH /api/v1/pages/{page}/lock
     */
    public function renew(Page $page): JsonResponse
    {
        $this->authorize('update', $page);

        $lock = $this->lockService->renewLock($page, auth()->user());

        if (! $lock) {
            return response()->json([
                'success' => false,
                'message' => 'No hay lock activo para renovar',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lock renovado',
            'data' => [
                'lock_id' => $lock->id,
                'expires_at' => $lock->expires_at->toIso8601String(),
                'expires_in_human' => $lock->expires_at->diffForHumans(),
            ],
        ]);
    }

    /**
     * Liberar un lock
     * DELETE /api/v1/pages/{page}/lock
     */
    public function release(Page $page): JsonResponse
    {
        $this->authorize('update', $page);

        if (! $this->lockService->releaseLock($page, auth()->user())) {
            return response()->json([
                'success' => false,
                'message' => 'No hay lock para liberar',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lock liberado',
        ]);
    }
}
