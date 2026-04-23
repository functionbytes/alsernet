<?php

namespace Modules\Reviews\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Reviews\Http\Requests\StoreReplyTemplateRequest;
use Modules\Reviews\Models\ReviewGoogleLocation;
use Modules\Reviews\Models\ReviewReplyTemplate;
use Modules\Reviews\Models\ReviewTemplateTranslation;

class ReviewTemplateController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ReviewReplyTemplate::class);

        $query = ReviewReplyTemplate::query()->with(['createdBy', 'location']);

        // Search filter
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%");
            });
        }

        // Category filter
        if ($category = $request->get('category')) {
            $query->byCategory($category);
        }

        // Location filter
        if ($locationId = $request->get('location_id')) {
            $query->byLocation((int) $locationId);
        }

        // Active/Inactive filter
        if ($request->has('is_active') && $request->get('is_active') !== '') {
            $query->where('is_active', (bool) $request->get('is_active'));
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        if ($sortBy === 'usage_count') {
            $query->mostUsed();
        } elseif ($sortBy === 'name') {
            $query->orderBy('name');
        } else {
            $query->latest();
        }

        $templates = $query->paginate(config('pagination.reviews'))->withQueryString();

        $locations = ReviewGoogleLocation::query()->orderBy('name')->get(['id', 'name']);

        $statsRow = ReviewReplyTemplate::query()
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive,
                SUM(CASE WHEN category = \'positive\' THEN 1 ELSE 0 END) as positive,
                SUM(CASE WHEN category = \'negative\' THEN 1 ELSE 0 END) as negative,
                SUM(CASE WHEN category = \'neutral\' THEN 1 ELSE 0 END) as neutral,
                SUM(CASE WHEN category = \'general\' THEN 1 ELSE 0 END) as general,
                COALESCE(SUM(usage_count), 0) as total_usage
            ')
            ->first();

        $stats = [
            'total' => (int) $statsRow->total,
            'active' => (int) $statsRow->active,
            'inactive' => (int) $statsRow->inactive,
            'positive' => (int) $statsRow->positive,
            'negative' => (int) $statsRow->negative,
            'neutral' => (int) $statsRow->neutral,
            'general' => (int) $statsRow->general,
            'total_usage' => (int) $statsRow->total_usage,
        ];

        return view('reviews::replies.templates.index', compact('templates', 'stats', 'locations'));
    }

    public function create(): View
    {
        $this->authorize('create', ReviewReplyTemplate::class);

        $locations = ReviewGoogleLocation::query()->orderBy('name')->get(['id', 'name']);

        return view('reviews::replies.templates.create', compact('locations'));
    }

    public function store(StoreReplyTemplateRequest $request): RedirectResponse
    {
        $this->authorize('create', ReviewReplyTemplate::class);

        $template = ReviewReplyTemplate::query()->create([
            ...$request->validated(),
            'created_by' => auth()->id(),
        ]);

        activity()
            ->performedOn($template)
            ->causedBy(auth()->user())
            ->log('Template created');

        return redirect()
            ->route('settings.reviews.templates.index')
            ->with('success', 'Plantilla creada correctamente');
    }

    public function show(ReviewReplyTemplate $template): View
    {
        $this->authorize('view', $template);

        return view('reviews::replies.templates.show', compact('template'));
    }

    public function edit(ReviewReplyTemplate $template): View
    {
        $this->authorize('update', $template);

        $template->loadCount('createdBy');

        $locations = ReviewGoogleLocation::query()->orderBy('name')->get(['id', 'name']);

        return view('reviews::replies.templates.edit', compact('template', 'locations'));
    }

    public function update(StoreReplyTemplateRequest $request, ReviewReplyTemplate $template): RedirectResponse
    {
        $template->update($request->validated());

        $template->versions()->create([
            'content' => $template->body,
            'language' => $template->language ?? 'es',
            'version_number' => $template->versions()->max('version_number') + 1,
            'created_by' => auth()->id(),
        ]);

        activity()
            ->performedOn($template)
            ->causedBy(auth()->user())
            ->log('Template updated');

        return redirect()
            ->route('settings.reviews.templates.index')
            ->with('success', 'Plantilla actualizada correctamente');
    }

    public function versions(Request $request, ReviewReplyTemplate $template): JsonResponse|View
    {
        $this->authorize('view', $template);

        $versions = $template->versions()
            ->with('creator:id,name')
            ->orderByDesc('version_number')
            ->paginate(10);

        if ($request->expectsJson()) {
            return response()->json($versions);
        }

        return view('reviews::replies.templates.versions', compact('template', 'versions'));
    }

    public function destroy(ReviewReplyTemplate $template): RedirectResponse
    {
        $this->authorize('delete', $template);

        $template->delete();

        activity()
            ->performedOn($template)
            ->causedBy(auth()->user())
            ->log('Template deleted');

        return redirect()
            ->route('settings.reviews.templates.index')
            ->with('success', 'Plantilla eliminada correctamente');
    }

    public function toggleActive(ReviewReplyTemplate $template): RedirectResponse
    {
        $this->authorize('update', $template);

        $template->update(['is_active' => ! $template->is_active]);

        $status = $template->is_active ? 'activada' : 'desactivada';

        activity()
            ->performedOn($template)
            ->causedBy(auth()->user())
            ->log("Template {$status}");

        return redirect()
            ->route('settings.reviews.templates.index')
            ->with('success', "Plantilla {$status} correctamente");
    }

    public function addTranslation(Request $request, ReviewReplyTemplate $template): JsonResponse
    {
        $this->authorize('update', $template);

        $validated = $request->validate([
            'language' => ['required', 'string', 'size:2'],
            'template_text' => ['required', 'string'],
        ]);

        $translation = ReviewTemplateTranslation::query()->updateOrCreate(
            ['template_id' => $template->id, 'language' => $validated['language']],
            ['template_text' => $validated['template_text']],
        );

        activity()
            ->performedOn($template)
            ->causedBy(auth()->user())
            ->log("Translation added for language: {$validated['language']}");

        return response()->json([
            'message' => 'Traducción guardada correctamente',
            'translation' => $translation,
        ]);
    }

    public function removeTranslation(ReviewReplyTemplate $template, string $language): JsonResponse
    {
        $this->authorize('update', $template);

        $deleted = ReviewTemplateTranslation::query()
            ->where('template_id', $template->id)
            ->where('language', $language)
            ->delete();

        if (! $deleted) {
            return response()->json(['message' => 'Traducción no encontrada'], 404);
        }

        activity()
            ->performedOn($template)
            ->causedBy(auth()->user())
            ->log("Translation removed for language: {$language}");

        return response()->json(['message' => 'Traducción eliminada correctamente']);
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'string', 'in:activate,deactivate,delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $ids = $validated['ids'];

        match ($validated['action']) {
            'activate' => ReviewReplyTemplate::query()->whereIn('id', $ids)->update(['is_active' => true]),
            'deactivate' => ReviewReplyTemplate::query()->whereIn('id', $ids)->update(['is_active' => false]),
            'delete' => ReviewReplyTemplate::query()->whereIn('id', $ids)->get()->each->delete(),
        };

        return response()->json(['success' => true, 'count' => count($ids)]);
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => 'required|json',
        ]);

        $ids = json_decode($request->input('ids'), true);

        if (empty($ids) || ! is_array($ids)) {
            return redirect()
                ->route('settings.reviews.templates.index')
                ->with('error', 'No se seleccionaron plantillas para eliminar');
        }

        try {
            $templates = ReviewReplyTemplate::query()
                ->whereIn('id', $ids)
                ->get();

            $deletedCount = 0;

            foreach ($templates as $template) {
                // Check authorization for each template
                $this->authorize('delete', $template);

                activity()
                    ->performedOn($template)
                    ->causedBy(auth()->user())
                    ->log('Template deleted (bulk)');

                $template->delete();
                $deletedCount++;
            }

            return redirect()
                ->route('settings.reviews.templates.index')
                ->with('success', "Se eliminaron {$deletedCount} plantillas correctamente");
        } catch (\Exception $e) {
            Log::error('Error deleting review templates', [
                'code' => $e->getCode(),
                'file' => class_basename($e->getFile()),
                'line' => $e->getLine(),
            ]);

            return redirect()
                ->route('settings.reviews.templates.index')
                ->with('error', 'Ha ocurrido un error al eliminar las plantillas. Por favor intenta más tarde.');
        }
    }
}
