<?php

namespace Modules\Supplier\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
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

        $pendingFailures = SyncFailure::query()
            ->where('failure_status', 'pending')
            ->count();

        $retryableFailures = SyncFailure::query()
            ->where('failure_status', 'pending')
            ->whereColumn('retry_count', '<', 'max_retries')
            ->where('failure_type', '!=', 'sin_proveedor')
            ->count();

        $stuckBatches = SyncBatch::query()
            ->whereIn('status', ['running', 'pending'])
            ->where('updated_at', '<', $now->copy()->subMinutes(30))
            ->count();

        $queueJobs = DB::table('jobs')->count();

        $status = match (true) {
            $stuckBatches > 0 => 'degraded',
            $retryableFailures > 50 => 'degraded',
            $lastSyncAgeMinutes !== null && $lastSyncAgeMinutes > 1440 => 'degraded',
            default => 'ok',
        };

        return response()->json([
            'status' => $status,
            'timestamp' => $now->toIso8601String(),
            'metrics' => [
                'last_sync_at' => $lastSync,
                'last_sync_age_minutes' => $lastSyncAgeMinutes,
                'pending_failures' => $pendingFailures,
                'retryable_failures' => $retryableFailures,
                'stuck_batches' => $stuckBatches,
                'queue_jobs' => $queueJobs,
            ],
        ], $status === 'ok' ? 200 : 503);
    }
}
