<?php

namespace Modules\Supplier\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Supplier\Models\Product\Product;
use Modules\Supplier\Models\Sync\SyncBatch;
use Modules\Supplier\Models\Sync\SyncFailure;

class HealthController extends Controller
{
    /**
     * Health probe for sync subsystem — safe for unauthenticated monitoring.
     */
    public function sync(): JsonResponse
    {
        $now = now();

        $lastSync = Product::query()->whereNotNull('last_sync_at')->max('last_sync_at');
        $lastSyncAgeMinutes = $lastSync ? $now->diffInMinutes($lastSync) : null;

        $retryableFailures = SyncFailure::query()
            ->where('failure_status', 'pending')
            ->whereColumn('retry_count', '<', 'max_retries')
            ->where('failure_type', '!=', 'sin_proveedor')
            ->count();

        $stuckBatches = SyncBatch::query()
            ->whereIn('status', ['running', 'pending'])
            ->where('updated_at', '<', $now->copy()->subMinutes(30))
            ->count();

        $status = match (true) {
            $stuckBatches > 0 => 'degraded',
            $retryableFailures > 50 => 'degraded',
            $lastSyncAgeMinutes !== null && $lastSyncAgeMinutes > 1440 => 'degraded',
            default => 'ok',
        };

        // 29-sep-2026: ruta pública -> respuesta mínima (solo el estado). Las
        // métricas internas (cola, fallos, lotes) no se exponen sin login.
        return response()->json([
            'status' => $status,
        ], $status === 'ok' ? 200 : 503);
    }
}
