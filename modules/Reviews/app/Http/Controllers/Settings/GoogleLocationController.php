<?php

namespace Modules\Reviews\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Reviews\Enums\ConnectionStatus;
use Modules\Reviews\Http\Requests\StoreLocationRequest;
use Modules\Reviews\Jobs\SyncGoogleReviewsJob;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewGoogleConnection;
use Modules\Reviews\Models\ReviewGoogleLocation;

class GoogleLocationController extends Controller
{
    public function create(): View
    {
        $this->authorize('reviews.settings.update');

        $connections = ReviewGoogleConnection::query()
            ->where('status', ConnectionStatus::ACTIVE)
            ->get(['id', 'name', 'google_email']);

        return view('reviews::settings.locations.create', compact('connections'));
    }

    public function store(StoreLocationRequest $request): JsonResponse
    {
        $data = $request->safe()->all();

        if ($data['sync_strategy'] !== 'oauth') {
            $data['connection_id'] = null;
            $data['google_location_id'] = 'manual_'.Str::uuid();
            $data['google_account_id'] = 'manual';
        }

        $location = ReviewGoogleLocation::query()->create($data);

        activity()
            ->performedOn($location)
            ->causedBy(auth()->user())
            ->log('Location created manually');

        return response()->json([
            'success' => true,
            'message' => 'Ubicación creada correctamente.',
            'redirect' => route('settings.reviews.locations.index'),
        ]);
    }

    public function index(Request $request): View
    {
        $this->authorize('reviews.settings.view');

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

        $locations = $query->latest('synced_at')->paginate(20)->withQueryString();

        $locationStats = ReviewGoogleLocation::query()
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
                COALESCE(AVG(CASE WHEN is_active = 1 THEN average_rating END), 0) as avg_rating
            ')
            ->first();

        $stats = [
            'total' => (int) $locationStats->total,
            'active' => (int) $locationStats->active,
            'avg_rating' => round((float) $locationStats->avg_rating, 2),
            'total_reviews' => Review::query()->count(),
        ];

        return view('reviews::settings.locations.index', compact('locations', 'stats'));
    }

    public function update(Request $request, ReviewGoogleLocation $location): JsonResponse
    {
        if ($location->connection) {
            $this->authorize('manage', $location->connection);
        } else {
            $this->authorize('reviews.settings.update');
        }

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
        if ($location->connection) {
            $this->authorize('manage', $location->connection);
        } else {
            $this->authorize('reviews.settings.update');
        }

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
        $this->authorize('reviews.settings.update');

        $locations = ReviewGoogleLocation::query()
            ->with('connection')
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
            $canSync = $location->connection
                ? auth()->user()->can('manage', $location->connection)
                : auth()->user()->can('reviews.settings.update');

            if ($canSync) {
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

    public function bulkAction(Request $request): JsonResponse
    {
        $this->authorize('reviews.settings.update');

        $validated = $request->validate([
            'action' => ['required', 'string', 'in:activate,deactivate,sync'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $ids = $validated['ids'];

        match ($validated['action']) {
            'activate' => ReviewGoogleLocation::query()->whereIn('id', $ids)->update(['is_active' => true]),
            'deactivate' => ReviewGoogleLocation::query()->whereIn('id', $ids)->update(['is_active' => false]),
            'sync' => ReviewGoogleLocation::query()->whereIn('id', $ids)->get()->each(
                fn ($location) => SyncGoogleReviewsJob::dispatch($location)
            ),
        };

        return response()->json(['success' => true, 'count' => count($ids)]);
    }

    public function tags(ReviewGoogleLocation $location): JsonResponse
    {
        $this->authorize('reviews.settings.view');

        return response()->json([
            'success' => true,
            'tags' => $location->available_tags ?? [],
        ]);
    }

    public function storeTag(Request $request, ReviewGoogleLocation $location): JsonResponse
    {
        $this->authorize('reviews.settings.update');

        $data = $request->validate([
            'label' => ['required', 'string', 'max:50'],
            'slug' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9\-_]+$/'],
            'icon' => ['nullable', 'string', 'max:80'],
        ]);

        $tags = $location->available_tags ?? [];

        if (collect($tags)->firstWhere('slug', $data['slug'])) {
            return response()->json(['success' => false, 'message' => 'Ya existe un tag con ese slug.'], 422);
        }

        $tags[] = [
            'slug' => $data['slug'],
            'label' => $data['label'],
            'icon' => $data['icon'] ?? null,
        ];

        $location->update(['available_tags' => $tags]);

        return response()->json(['success' => true, 'tags' => $tags]);
    }

    public function destroyTag(ReviewGoogleLocation $location, string $slug): JsonResponse
    {
        $this->authorize('reviews.settings.update');

        $tags = collect($location->available_tags ?? [])
            ->reject(fn ($t) => $t['slug'] === $slug)
            ->values()
            ->all();

        $location->update(['available_tags' => $tags]);

        return response()->json(['success' => true, 'tags' => $tags]);
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
            SyncGoogleReviewsJob::dispatch($location);
            $count++;
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
