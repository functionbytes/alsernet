<?php

namespace Modules\Supplier\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Supplier\Http\Requests\Api\TriggerSyncRequest;
use Modules\Supplier\Http\Resources\SyncBatchResource;
use Modules\Supplier\Models\Supplier\Supplier;
use Modules\Supplier\Models\Sync\SyncBatch;
use Modules\Supplier\Services\SyncCoordinatorAgent;
use Modules\Supplier\Services\SyncStatusService;

class SyncApiController extends Controller
{
    public function __construct(
        private SyncCoordinatorAgent $coordinator,
        private SyncStatusService $syncStatusService
    ) {}

    public function batches(Request $request): AnonymousResourceCollection
    {
        $batches = SyncBatch::query()
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->supplier_id, fn ($q, $id) => $q->where('supplier_id', $id))
            ->when($request->date_from, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($request->date_to, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return SyncBatchResource::collection($batches);
    }

    public function showBatch(int $id): SyncBatchResource
    {
        $batch = SyncBatch::with(['logs', 'failures'])->findOrFail($id);

        return new SyncBatchResource($batch);
    }

    public function trigger(TriggerSyncRequest $request): JsonResponse
    {
        $supplierId = null;

        if ($request->filled('suppliers')) {
            // coordinateSync() only supports a single supplier; take the first matching UID.
            $supplierId = Supplier::query()->whereIn('uid', $request->suppliers)->value('id');
        }

        $result = $this->coordinator->coordinateSync(
            syncType: $request->sync_type,
            supplierId: $supplierId,
            triggeredBy: 'api',
        );

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'No se pudo iniciar la sincronizacion.',
            ], 422);
        }

        $batch = isset($result['batch_id']) ? SyncBatch::find($result['batch_id']) : null;

        return response()->json([
            'success' => true,
            'message' => 'Sincronizacion iniciada.',
            'batch' => $batch ? new SyncBatchResource($batch) : null,
        ], 201);
    }
}
