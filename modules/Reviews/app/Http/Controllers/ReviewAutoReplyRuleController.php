<?php

namespace Modules\Reviews\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Reviews\Models\ReviewAutoReplyRule;
use Modules\Reviews\Models\ReviewGoogleLocation;
use Modules\Reviews\Models\ReviewReplyTemplate;
use Modules\Reviews\Services\AutoReplyService;

class ReviewAutoReplyRuleController extends Controller
{
    public function __construct(private readonly AutoReplyService $service) {}

    public function index(Request $request): View|JsonResponse
    {
        $locationIds = ReviewGoogleLocation::query()
            ->whereHas('connection', fn ($q) => $q->where('user_id', auth()->id()))
            ->pluck('id');

        if (! $request->expectsJson()) {
            $baseQuery = ReviewAutoReplyRule::query()->whereIn('location_id', $locationIds);

            $statsRow = (clone $baseQuery)
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive
                ')
                ->first();

            $stats = [
                'total' => (int) $statsRow->total,
                'active' => (int) $statsRow->active,
                'inactive' => (int) $statsRow->inactive,
            ];

            return view('reviews::auto-reply-rules.index', compact('stats'));
        }

        $query = ReviewAutoReplyRule::query()
            ->with('template')
            ->whereIn('location_id', $locationIds)
            ->orderBy('location_id')
            ->orderBy('id');

        if ($request->filled('location_id')) {
            $query->where('location_id', $request->integer('location_id'));
        }

        return response()->json([
            'success' => true,
            'data' => $query->get(),
        ]);
    }

    public function create(): View
    {
        $locations = $this->userLocations();
        $templates = ReviewReplyTemplate::query()->orderBy('name')->get(['id', 'name']);

        return view('reviews::auto-reply-rules.create', compact('locations', 'templates'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'location_id' => 'required|integer|exists:review_google_locations,id',
            'name' => 'required|string|max:150',
            'is_active' => 'boolean',
            'condition_type' => 'required|in:star_rating,has_no_reply,keyword_match',
            'min_rating' => 'nullable|integer|min:1|max:5',
            'max_rating' => 'nullable|integer|min:1|max:5',
            'condition_value' => 'nullable|string|max:200',
            'template_id' => 'nullable|integer|exists:review_reply_templates,id',
            'delay_minutes' => 'integer|min:0',
        ]);

        $this->authorizeLocationAccess((int) $validated['location_id']);

        $conditions = $this->buildConditions($validated);

        $rule = ReviewAutoReplyRule::query()->create([
            'location_id' => $validated['location_id'],
            'name' => $validated['name'],
            'is_active' => $request->boolean('is_active'),
            'conditions' => $conditions,
            'template_id' => $validated['template_id'] ?? null,
            'delay_minutes' => $validated['delay_minutes'] ?? 0,
        ]);

        activity()
            ->performedOn($rule)
            ->causedBy(auth()->user())
            ->log("Auto-reply rule created: {$rule->name}");

        return redirect()->route('reviews.autoreply.index')
            ->with('success', 'Regla de auto-respuesta creada correctamente.');
    }

    public function edit(ReviewAutoReplyRule $autoReplyRule): View
    {
        $this->authorizeLocationAccess($autoReplyRule->location_id);

        $locations = $this->userLocations();
        $templates = ReviewReplyTemplate::query()->orderBy('name')->get(['id', 'name']);

        return view('reviews::auto-reply-rules.edit', compact('autoReplyRule', 'locations', 'templates'));
    }

    public function update(Request $request, ReviewAutoReplyRule $autoReplyRule): RedirectResponse
    {
        $this->authorizeLocationAccess($autoReplyRule->location_id);

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'is_active' => 'boolean',
            'condition_type' => 'required|in:star_rating,has_no_reply,keyword_match',
            'min_rating' => 'nullable|integer|min:1|max:5',
            'max_rating' => 'nullable|integer|min:1|max:5',
            'condition_value' => 'nullable|string|max:200',
            'template_id' => 'nullable|integer|exists:review_reply_templates,id',
        ]);

        $conditions = $this->buildConditions($validated);

        $autoReplyRule->update([
            'name' => $validated['name'],
            'is_active' => $request->boolean('is_active'),
            'conditions' => $conditions,
            'template_id' => $validated['template_id'] ?? null,
        ]);

        activity()
            ->performedOn($autoReplyRule)
            ->causedBy(auth()->user())
            ->log("Auto-reply rule updated: {$autoReplyRule->name}");

        return redirect()->route('reviews.autoreply.index')
            ->with('success', 'Regla actualizada correctamente.');
    }

    public function destroy(ReviewAutoReplyRule $autoReplyRule): JsonResponse
    {
        $this->authorizeLocationAccess($autoReplyRule->location_id);

        activity()
            ->performedOn($autoReplyRule)
            ->causedBy(auth()->user())
            ->log("Auto-reply rule deleted: {$autoReplyRule->name}");

        $autoReplyRule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Regla eliminada correctamente',
        ]);
    }

    public function toggle(ReviewAutoReplyRule $autoReplyRule): JsonResponse
    {
        $this->authorizeLocationAccess($autoReplyRule->location_id);

        $updated = $this->service->toggleRule($autoReplyRule);

        $state = $updated->is_active ? 'activada' : 'desactivada';

        activity()
            ->performedOn($updated)
            ->causedBy(auth()->user())
            ->log("Auto-reply rule {$state}: {$updated->name}");

        return response()->json([
            'success' => true,
            'message' => "Regla {$state} correctamente",
            'data' => ['is_active' => $updated->is_active],
        ]);
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'string', 'in:activate,deactivate,delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $locationIds = ReviewGoogleLocation::query()
            ->whereHas('connection', fn ($q) => $q->where('user_id', auth()->id()))
            ->pluck('id');

        $ids = $validated['ids'];
        $query = ReviewAutoReplyRule::query()
            ->whereIn('id', $ids)
            ->whereIn('location_id', $locationIds);

        match ($validated['action']) {
            'activate' => $query->update(['is_active' => true]),
            'deactivate' => $query->update(['is_active' => false]),
            'delete' => ReviewAutoReplyRule::query()
                ->whereIn('id', $ids)
                ->whereIn('location_id', $locationIds)
                ->get()
                ->each->delete(),
        };

        return response()->json(['success' => true, 'count' => count($ids)]);
    }

    private function buildConditions(array $validated): array
    {
        $conditions = [];

        if ($validated['condition_type'] === 'star_rating') {
            if (! empty($validated['min_rating'])) {
                $conditions['min_rating'] = (int) $validated['min_rating'];
            }
            if (! empty($validated['max_rating'])) {
                $conditions['max_rating'] = (int) $validated['max_rating'];
            }
        } elseif ($validated['condition_type'] === 'has_no_reply') {
            $conditions['has_comment'] = false;
        } elseif ($validated['condition_type'] === 'keyword_match') {
            $conditions['keyword'] = $validated['condition_value'] ?? '';
        }

        return $conditions;
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

    private function authorizeLocationAccess(int $locationId): void
    {
        $owned = ReviewGoogleLocation::query()
            ->whereHas('connection', fn ($q) => $q->where('user_id', auth()->id()))
            ->where('id', $locationId)
            ->exists();

        abort_unless($owned || auth()->user()->hasRole('super-admin'), 403);
    }
}
