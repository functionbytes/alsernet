<?php

namespace Modules\Remarketing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Remarketing\Http\Requests\Web\StoreSegmentRequest;
use Modules\Remarketing\Http\Requests\Web\UpdateSegmentRequest;
use Modules\Remarketing\Models\Segment;
use Modules\Remarketing\Models\Store;
use Modules\Remarketing\Services\SegmentService;

class SegmentController extends Controller
{
    public function __construct(
        private readonly SegmentService $segmentService
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Segment::class);

        $user = auth()->user();

        $storeIds = Store::query()
            ->when(! $user->can('remarketing.manage'), fn ($q) => $q->where('user_id', $user->id))
            ->pluck('id');

        $segments = Segment::query()
            ->with('store')
            ->whereIn('store_id', $storeIds)
            ->latest()
            ->paginate(20);

        return view('remarketing::segments.index', compact('segments'));
    }

    public function create(): View
    {
        $this->authorize('create', Segment::class);

        $stores = $this->getUserStores();

        return view('remarketing::segments.create', compact('stores'));
    }

    public function store(StoreSegmentRequest $request): RedirectResponse
    {
        Segment::query()->create($request->validated());

        return redirect()->route('remarketing.segments.index')
            ->with('success', 'Segmento creado correctamente.');
    }

    public function edit(Segment $segment): View
    {
        $this->authorize('update', $segment);

        $stores = $this->getUserStores();

        return view('remarketing::segments.edit', compact('segment', 'stores'));
    }

    public function update(UpdateSegmentRequest $request, Segment $segment): RedirectResponse
    {
        $this->authorize('update', $segment);

        $segment->update($request->validated());

        return redirect()->route('remarketing.segments.index')
            ->with('success', 'Segmento actualizado correctamente.');
    }

    public function destroy(Segment $segment): RedirectResponse
    {
        $this->authorize('delete', $segment);

        $segment->delete();

        return redirect()->route('remarketing.segments.index')
            ->with('success', 'Segmento eliminado correctamente.');
    }

    public function preview(Segment $segment): JsonResponse
    {
        $this->authorize('view', $segment);

        $members = $this->segmentService->getMembers($segment)
            ->select(['id', 'email', 'first_name', 'last_name', 'status', 'country'])
            ->limit(20)
            ->get();

        $count = $this->segmentService->getMemberCount($segment);

        return response()->json([
            'total' => $count,
            'sample' => $members,
        ]);
    }

    private function getUserStores(): Collection
    {
        $user = auth()->user();

        return Store::query()
            ->when(! $user->can('remarketing.manage'), fn ($q) => $q->where('user_id', $user->id))
            ->get(['id', 'name']);
    }
}
