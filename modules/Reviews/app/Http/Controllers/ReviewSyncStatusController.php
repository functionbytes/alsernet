<?php

namespace Modules\Reviews\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\Reviews\Jobs\SyncGoogleReviewsJob;
use Modules\Reviews\Models\ReviewGoogleConnection;
use Modules\Reviews\Models\ReviewGoogleLocation;

class ReviewSyncStatusController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:reviews.reviews.view');
    }

    public function index(): JsonResponse
    {
        $connections = ReviewGoogleConnection::query()
            ->where('user_id', auth()->id())
            ->withCount(['locations as active_locations_count' => fn ($q) => $q->where('is_active', true)])
            ->with(['locations' => fn ($q) => $q->where('is_active', true)->select('id', 'connection_id', 'name', 'synced_at')])
            ->get()
            ->map(fn (ReviewGoogleConnection $connection) => [
                'id' => $connection->id,
                'name' => $connection->name,
                'google_email' => $connection->google_email,
                'status' => $connection->status->value,
                'status_color' => $connection->status->color(),
                'is_active' => $connection->isActive(),
                'active_locations_count' => $connection->active_locations_count,
                'last_sync_at' => $connection->locations->max('synced_at'),
                'next_sync_estimate' => $this->estimateNextSync($connection->locations->min('synced_at')),
                'locations' => $connection->locations->map(fn (ReviewGoogleLocation $loc) => [
                    'id' => $loc->id,
                    'name' => $loc->name,
                    'synced_at' => $loc->synced_at?->toIso8601ZuluString(),
                    'synced_ago' => $loc->synced_at ? $loc->synced_at->diffForHumans() : null,
                ]),
            ]);

        return response()->json([
            'success' => true,
            'data' => $connections,
            'sync_interval_minutes' => config('reviews.general.sync_interval_minutes', 15),
        ]);
    }

    public function syncNow(int $connectionId): JsonResponse
    {
        $connection = ReviewGoogleConnection::query()
            ->where('id', $connectionId)
            ->where('user_id', auth()->id())
            ->first();

        if (! $connection) {
            return response()->json(['success' => false, 'message' => 'Conexion no encontrada'], 404);
        }

        if (! $connection->isActive()) {
            return response()->json(['success' => false, 'message' => 'La conexion no esta activa'], 422);
        }

        $locations = ReviewGoogleLocation::query()
            ->where('connection_id', $connection->id)
            ->where('is_active', true)
            ->get();

        if ($locations->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No hay ubicaciones activas en esta conexion'], 422);
        }

        try {
            foreach ($locations as $location) {
                SyncGoogleReviewsJob::dispatch($location);
            }

            activity()
                ->performedOn($connection)
                ->causedBy(auth()->user())
                ->log("Manual sync triggered for {$locations->count()} locations from sync status widget");

            return response()->json([
                'success' => true,
                'message' => "Sincronizacion iniciada para {$locations->count()} ubicaciones.",
            ]);
        } catch (\Exception $e) {
            Log::error('ReviewSyncStatusController: syncNow failed', [
                'connection_id' => $connection->id,
                'code' => $e->getCode(),
                'file' => class_basename($e->getFile()),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ha ocurrido un error al iniciar la sincronizacion. Por favor intenta mas tarde.',
            ], 500);
        }
    }

    private function estimateNextSync(?string $oldestSyncedAt): ?string
    {
        if (! $oldestSyncedAt) {
            return null;
        }

        $intervalMinutes = config('reviews.general.sync_interval_minutes', 15);

        return Carbon::parse($oldestSyncedAt)
            ->addMinutes($intervalMinutes)
            ->toIso8601ZuluString();
    }
}
