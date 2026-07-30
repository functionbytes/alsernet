<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Http\Requests\StoreAutoAssignmentRequest;
use Modules\Helpdesk\Services\AutoAssignmentService;

class AutoAssignmentController extends Controller
{
    public function __construct(
        private readonly AutoAssignmentService $service,
    ) {}

    /**
     * GET /panel/helpdesk/auto-assignment
     * Current auto-assignment strategy (#78 ve-auto-assign).
     */
    public function show(Request $request): JsonResponse
    {
        $this->authorize('helpdesk.manage');

        // config() ya incluye el `enabled` persistido (con fallback a la config file).
        return response()->json($this->service->config());
    }

    /**
     * PUT /panel/helpdesk/auto-assignment
     * Persist the strategy, retry delay and fallback.
     */
    public function update(StoreAutoAssignmentRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $enabled = array_key_exists('enabled', $validated)
            ? filter_var($validated['enabled'], FILTER_VALIDATE_BOOLEAN)
            : null;

        $this->service->saveConfig(
            $validated['strategy'],
            $validated['retry'],
            $validated['fallback'],
            $enabled,
        );

        return response()->json([
            'success' => true,
            'message' => 'Estrategia de auto-asignación guardada',
        ]);
    }
}
