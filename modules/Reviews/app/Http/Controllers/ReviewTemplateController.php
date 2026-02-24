<?php

namespace Modules\Reviews\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Reviews\Http\Requests\StoreReplyTemplateRequest;
use Modules\Reviews\Models\ReviewReplyTemplate;

class ReviewTemplateController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ReviewReplyTemplate::class);

        $query = ReviewReplyTemplate::query()->with('createdBy');

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

        $templates = $query->paginate(20)->withQueryString();

        // Calculate stats
        $stats = [
            'total' => ReviewReplyTemplate::count(),
            'active' => ReviewReplyTemplate::where('is_active', true)->count(),
            'inactive' => ReviewReplyTemplate::where('is_active', false)->count(),
            'positive' => ReviewReplyTemplate::where('category', 'positive')->count(),
            'negative' => ReviewReplyTemplate::where('category', 'negative')->count(),
            'neutral' => ReviewReplyTemplate::where('category', 'neutral')->count(),
            'general' => ReviewReplyTemplate::where('category', 'general')->count(),
            'total_usage' => ReviewReplyTemplate::sum('usage_count'),
        ];

        return view('reviews::replies.templates.index', compact('templates', 'stats'));
    }

    public function create(): View
    {
        $this->authorize('create', ReviewReplyTemplate::class);

        return view('reviews::replies.templates.create');
    }

    public function store(StoreReplyTemplateRequest $request): RedirectResponse
    {
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
        return view('reviews::replies.templates.show', compact('template'));
    }

    public function edit(ReviewReplyTemplate $template): View
    {
        $this->authorize('update', $template);

        $template->loadCount('createdBy');

        return view('reviews::replies.templates.edit', compact('template'));
    }

    public function update(StoreReplyTemplateRequest $request, ReviewReplyTemplate $template): RedirectResponse
    {
        $template->update($request->validated());

        activity()
            ->performedOn($template)
            ->causedBy(auth()->user())
            ->log('Template updated');

        return redirect()
            ->route('settings.reviews.templates.index')
            ->with('success', 'Plantilla actualizada correctamente');
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
            return redirect()
                ->route('settings.reviews.templates.index')
                ->with('error', 'Error al eliminar plantillas: '.$e->getMessage());
        }
    }
}
