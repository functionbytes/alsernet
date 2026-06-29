<?php

namespace Modules\Reviews\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Reviews\Models\ReviewCompetitor;
use Modules\Reviews\Models\ReviewGoogleLocation;
use Modules\Reviews\Services\CompetitorTrackingService;

class ReviewCompetitorController extends Controller
{
    public function __construct(private readonly CompetitorTrackingService $trackingService)
    {
        $this->middleware('can:reviews.reviews.view');
    }

    public function index(): View
    {
        $locationIds = ReviewGoogleLocation::query()
            ->whereHas('connection', fn ($q) => $q->where('user_id', auth()->id()))
            ->where('is_active', true)
            ->pluck('id');

        $competitors = ReviewCompetitor::query()
            ->with(['location:id,name', 'latestSnapshot'])
            ->whereIn('location_id', $locationIds)
            ->orderBy('location_id')
            ->orderBy('name')
            ->get();

        $locations = ReviewGoogleLocation::query()
            ->whereIn('id', $locationIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        $stats = [
            'total' => $competitors->count(),
            'with_data' => $competitors->filter(fn ($c) => $c->latestSnapshot !== null)->count(),
            'without_data' => $competitors->filter(fn ($c) => $c->latestSnapshot === null)->count(),
            'locations' => $competitors->pluck('location_id')->unique()->count(),
        ];

        return view('reviews::competitors.index', compact('competitors', 'locations', 'stats'));
    }

    public function create(): View
    {
        $locations = $this->userLocations();

        return view('reviews::competitors.create', compact('locations'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'location_id' => ['required', 'integer', 'exists:review_google_locations,id'],
            'name' => ['required', 'string', 'max:150'],
            'google_maps_url' => ['required', 'string', 'url', 'max:500'],
            'place_id' => ['nullable', 'string', 'max:200'],
        ]);

        if (! $this->ownsLocation($validated['location_id'])) {
            abort(403, 'Ubicacion no autorizada.');
        }

        ReviewCompetitor::query()->create($validated);

        return redirect()->route('reviews.competitors.index')
            ->with('success', 'Competidor agregado correctamente.');
    }

    public function edit(ReviewCompetitor $competitor): View
    {
        if (! $this->ownsLocation($competitor->location_id)) {
            abort(403, 'Accion no autorizada.');
        }

        $locations = $this->userLocations();

        return view('reviews::competitors.edit', compact('competitor', 'locations'));
    }

    public function update(Request $request, ReviewCompetitor $competitor): RedirectResponse
    {
        if (! $this->ownsLocation($competitor->location_id)) {
            abort(403, 'Accion no autorizada.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'google_maps_url' => ['required', 'string', 'url', 'max:500'],
            'place_id' => ['nullable', 'string', 'max:200'],
        ]);

        $competitor->update($validated);

        return redirect()->route('reviews.competitors.index')
            ->with('success', 'Competidor actualizado correctamente.');
    }

    public function destroy(ReviewCompetitor $competitor): RedirectResponse
    {
        if (! $this->ownsLocation($competitor->location_id)) {
            abort(403, 'Accion no autorizada.');
        }

        $competitor->delete();

        return redirect()->route('reviews.competitors.index')
            ->with('success', 'Competidor eliminado correctamente.');
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $locationIds = ReviewGoogleLocation::query()
            ->whereHas('connection', fn ($q) => $q->where('user_id', auth()->id()))
            ->where('is_active', true)
            ->pluck('id');

        $count = ReviewCompetitor::query()
            ->whereIn('id', $validated['ids'])
            ->whereIn('location_id', $locationIds)
            ->delete();

        return response()->json(['success' => true, 'count' => $count]);
    }

    public function comparePage(Request $request): View
    {
        $locations = $this->userLocations();

        $selectedId = $request->integer('location_id') ?: $locations->first()?->id;
        $data = null;

        if ($selectedId) {
            $data = $this->trackingService->getComparisonData($selectedId);
        }

        return view('reviews::competitors.comparison', compact('locations', 'selectedId', 'data'));
    }

    public function comparison(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'location_id' => ['required', 'integer', 'exists:review_google_locations,id'],
        ]);

        $locationId = (int) $validated['location_id'];

        if (! $this->ownsLocation($locationId)) {
            return response()->json(['success' => false, 'message' => 'Ubicacion no autorizada.'], 403);
        }

        $data = $this->trackingService->getComparisonData($locationId);

        return response()->json(['success' => true, 'data' => $data]);
    }

    private function userLocations()
    {
        $locationIds = ReviewGoogleLocation::query()
            ->whereHas('connection', fn ($q) => $q->where('user_id', auth()->id()))
            ->where('is_active', true)
            ->pluck('id');

        return ReviewGoogleLocation::query()
            ->whereIn('id', $locationIds)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function ownsLocation(int $locationId): bool
    {
        return ReviewGoogleLocation::query()
            ->whereHas('connection', fn ($q) => $q->where('user_id', auth()->id()))
            ->where('id', $locationId)
            ->exists();
    }
}
