<?php

namespace Modules\Reviews\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Reviews\Jobs\SyncGoogleReviewsJob;
use Modules\Reviews\Models\ReviewGoogleLocation;

class GoogleLocationController extends Controller
{
    public function index(Request $request): View
    {
        $query = ReviewGoogleLocation::query()
            ->with(['connection' => function ($q) {
                $q->select('id', 'name', 'google_email', 'status');
            }])
            ->withCount('reviews');

        // Search filter
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $locations = $query->latest('last_synced_at')->paginate(20)->withQueryString();

        // Calculate stats
        $stats = [
            'total' => ReviewGoogleLocation::count(),
            'active' => ReviewGoogleLocation::where('is_active', true)->count(),
            'avg_rating' => ReviewGoogleLocation::where('is_active', true)->avg('average_rating') ?? 0,
            'total_reviews' => ReviewGoogleLocation::withCount('reviews')->get()->sum('reviews_count'),
        ];

        return view('reviews::settings.locations.index', compact('locations', 'stats'));
    }

    public function update(Request $request, ReviewGoogleLocation $location): JsonResponse
    {
        $this->authorize('manage', $location->connection);

        $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $location->update([
            'is_active' => $request->boolean('is_active'),
        ]);

        activity()
            ->performedOn($location)
            ->causedBy(auth()->user())
            ->log('Location status updated: '.($location->is_active ? 'active' : 'inactive'));

        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado correctamente',
            'is_active' => $location->is_active,
        ]);
    }

    public function sync(ReviewGoogleLocation $location): JsonResponse
    {
        $this->authorize('manage', $location->connection);

        if (! $location->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'La ubicación está desactivada',
            ], 422);
        }

        SyncGoogleReviewsJob::dispatch($location);

        activity()
            ->performedOn($location)
            ->causedBy(auth()->user())
            ->log('Manual sync triggered');

        return response()->json([
            'success' => true,
            'message' => 'Sincronización iniciada. Los cambios aparecerán en unos momentos.',
        ]);
    }

    public function syncAll(): JsonResponse
    {
        $locations = ReviewGoogleLocation::query()
            ->where('is_active', true)
            ->get();

        if ($locations->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No hay ubicaciones activas para sincronizar',
            ], 422);
        }

        $count = 0;
        foreach ($locations as $location) {
            if (auth()->user()->can('manage', $location->connection)) {
                SyncGoogleReviewsJob::dispatch($location);
                $count++;
            }
        }

        activity()
            ->causedBy(auth()->user())
            ->log("Bulk sync triggered for {$count} locations");

        return response()->json([
            'success' => true,
            'message' => "Sincronización iniciada para {$count} ubicaciones. Los cambios aparecerán en unos momentos.",
        ]);
    }

    public function bulkSync(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|json',
        ]);

        $ids = json_decode($request->input('ids'), true);

        if (empty($ids) || ! is_array($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'No se seleccionaron ubicaciones para sincronizar',
            ], 422);
        }

        $locations = ReviewGoogleLocation::query()
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->get();

        if ($locations->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No hay ubicaciones activas seleccionadas',
            ], 422);
        }

        $count = 0;
        foreach ($locations as $location) {
            if (auth()->user()->can('manage', $location->connection)) {
                SyncGoogleReviewsJob::dispatch($location);
                $count++;
            }
        }

        activity()
            ->causedBy(auth()->user())
            ->log("Bulk sync triggered for {$count} selected locations");

        return response()->json([
            'success' => true,
            'message' => "Sincronización iniciada para {$count} ubicaciones seleccionadas.",
        ]);
    }
}
