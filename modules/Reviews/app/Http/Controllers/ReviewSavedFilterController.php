<?php

namespace Modules\Reviews\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Reviews\Http\Requests\StoreSavedFilterRequest;
use Modules\Reviews\Models\ReviewSavedFilter;

class ReviewSavedFilterController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(ReviewSavedFilter::class, 'saved_filter');
    }

    public function index(Request $request): View|JsonResponse
    {
        $userId = auth()->id();

        $filters = ReviewSavedFilter::query()
            ->availableFor($userId)
            ->with('sharedBy:id,firstname,lastname')
            ->latest()
            ->get();

        // Return JSON for AJAX requests
        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json($filters->map(function ($filter) {
                return [
                    'id' => $filter->id,
                    'name' => $filter->name,
                    'is_default' => $filter->is_default,
                    'is_shared' => $filter->is_shared,
                    'created_at' => $filter->created_at->toISOString(),
                ];
            }));
        }

        $myFilters = $filters->where('user_id', $userId);
        $stats = [
            'total' => $filters->count(),
            'defaults' => $myFilters->where('is_default', true)->count(),
        ];

        return view('reviews::saved-filters.index', compact('filters', 'stats', 'userId'));
    }

    public function store(StoreSavedFilterRequest $request): JsonResponse
    {
        $data = [
            ...$request->validated(),
            'user_id' => auth()->id(),
            'filters_json' => json_decode($request->input('filters_json'), true),
        ];

        if ($request->boolean('is_shared')) {
            $data['is_shared'] = true;
            $data['shared_by'] = auth()->id();
        }

        $filter = ReviewSavedFilter::query()->create($data);

        if ($request->boolean('is_default')) {
            $filter->setAsDefault();
        }

        activity()
            ->performedOn($filter)
            ->causedBy(auth()->user())
            ->log('Saved filter created');

        return response()->json([
            'success' => true,
            'message' => 'Filtro guardado correctamente',
            'filter' => [
                'id' => $filter->id,
                'name' => $filter->name,
                'is_default' => $filter->is_default,
            ],
        ], 201);
    }

    public function show(ReviewSavedFilter $savedFilter): JsonResponse
    {
        return response()->json([
            'success' => true,
            'filter' => [
                'id' => $savedFilter->id,
                'name' => $savedFilter->name,
                'filters' => $savedFilter->filters_json,
                'is_default' => $savedFilter->is_default,
            ],
        ]);
    }

    public function update(StoreSavedFilterRequest $request, ReviewSavedFilter $savedFilter): JsonResponse
    {
        $data = ['name' => $request->input('name')];

        // Only update filters_json if provided with valid content
        $filtersJson = $request->input('filters_json');
        if ($filtersJson && $filtersJson !== '{}') {
            $data['filters_json'] = json_decode($filtersJson, true);
        }

        $savedFilter->update($data);

        if ($request->boolean('is_default')) {
            $savedFilter->setAsDefault();
        }

        activity()
            ->performedOn($savedFilter)
            ->causedBy(auth()->user())
            ->log('Saved filter updated');

        return response()->json([
            'success' => true,
            'message' => 'Filtro actualizado correctamente',
            'filter' => [
                'id' => $savedFilter->id,
                'name' => $savedFilter->name,
                'is_default' => $savedFilter->is_default,
            ],
        ]);
    }

    public function destroy(ReviewSavedFilter $savedFilter): JsonResponse
    {
        activity()
            ->performedOn($savedFilter)
            ->causedBy(auth()->user())
            ->log('Saved filter deleted');

        $savedFilter->delete();

        return response()->json([
            'success' => true,
            'message' => 'Filtro eliminado correctamente',
        ]);
    }

    public function apply(ReviewSavedFilter $savedFilter): RedirectResponse
    {
        $this->authorize('view', $savedFilter);

        $filters = $savedFilter->filters_json;
        $queryParams = [];

        if (isset($filters['location_id'])) {
            $queryParams['location_id'] = $filters['location_id'];
        }

        if (isset($filters['star_rating']) && is_array($filters['star_rating'])) {
            $queryParams['rating'] = implode(',', $filters['star_rating']);
        }

        if (isset($filters['has_comment'])) {
            $queryParams['has_comment'] = $filters['has_comment'] ? '1' : '0';
        }

        if (isset($filters['has_google_reply'])) {
            $queryParams['has_reply'] = $filters['has_google_reply'] ? '1' : '0';
        }

        if (isset($filters['has_our_reply'])) {
            $queryParams['has_our_reply'] = $filters['has_our_reply'] ? '1' : '0';
        }

        if (isset($filters['is_visible'])) {
            $queryParams['visibility'] = $filters['is_visible'] ? '1' : '0';
        }

        if (isset($filters['is_featured'])) {
            $queryParams['featured'] = $filters['is_featured'] ? '1' : '0';
        }

        if (isset($filters['date_from'])) {
            $queryParams['date_from'] = $filters['date_from'];
        }

        if (isset($filters['date_to'])) {
            $queryParams['date_to'] = $filters['date_to'];
        }

        if (isset($filters['sort_by'])) {
            $queryParams['sort_by'] = $filters['sort_by'];
        }

        if (isset($filters['sort_order'])) {
            $queryParams['sort_order'] = $filters['sort_order'];
        }

        return redirect()
            ->route('reviews.index', $queryParams)
            ->with('success', "Filtro '{$savedFilter->name}' aplicado");
    }

    public function share(ReviewSavedFilter $savedFilter): JsonResponse
    {
        $this->authorize('update', $savedFilter);

        $isNowShared = ! $savedFilter->is_shared;

        $savedFilter->update([
            'is_shared' => $isNowShared,
            'shared_by' => $isNowShared ? auth()->id() : null,
        ]);

        activity()
            ->performedOn($savedFilter)
            ->causedBy(auth()->user())
            ->log($isNowShared ? 'Saved filter shared' : 'Saved filter unshared');

        return response()->json([
            'success' => true,
            'is_shared' => $isNowShared,
            'message' => $isNowShared ? 'Filtro compartido correctamente' : 'Filtro dejado de compartir',
        ]);
    }

    public function setDefault(Request $request, ReviewSavedFilter $savedFilter): JsonResponse
    {
        $this->authorize('update', $savedFilter);

        $savedFilter->setAsDefault();

        activity()
            ->performedOn($savedFilter)
            ->causedBy(auth()->user())
            ->log('Saved filter set as default');

        return response()->json([
            'success' => true,
            'message' => 'Filtro establecido como predeterminado',
        ]);
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $count = ReviewSavedFilter::query()
            ->whereIn('id', $validated['ids'])
            ->where('user_id', auth()->id())
            ->delete();

        return response()->json(['success' => true, 'count' => $count]);
    }
}
