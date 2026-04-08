<?php

namespace Modules\Seo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Seo\Http\Requests\StoreSeoTemplateRequest;
use Modules\Seo\Http\Requests\UpdateSeoTemplateRequest;
use Modules\Seo\Models\SeoMeta;
use Modules\Seo\Models\SeoTemplate;

class SeoTemplateController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:Seo.metas.index')->only('index', 'preview');
        $this->middleware('can:Seo.metas.update')->only('create', 'store', 'edit', 'update', 'toggleActive', 'bulkApply', 'bulkAction', 'destroy');
    }

    public function index(): View
    {
        $templates = SeoTemplate::query()->orderByDesc('priority')->orderBy('name')->paginate(20);

        $stats = [
            'total' => SeoTemplate::count(),
            'active' => SeoTemplate::active()->count(),
        ];

        return view('Seo::settings.templates.index', compact('templates', 'stats'));
    }

    public function create(): View
    {
        return view('Seo::settings.templates.create');
    }

    public function store(StoreSeoTemplateRequest $request): RedirectResponse
    {
        SeoTemplate::create($request->validated());

        return redirect()
            ->route('setting.seo.templates.index')
            ->with('success', 'Plantilla SEO creada correctamente.');
    }

    public function edit(SeoTemplate $template): View
    {
        return view('Seo::settings.templates.edit', compact('template'));
    }

    public function update(UpdateSeoTemplateRequest $request, SeoTemplate $template): RedirectResponse
    {
        $template->update($request->validated());

        return redirect()
            ->route('setting.seo.templates.index')
            ->with('success', 'Plantilla SEO actualizada correctamente.');
    }

    public function destroy(SeoTemplate $template): RedirectResponse
    {
        $template->delete();

        return back()->with('success', 'Plantilla SEO eliminada correctamente.');
    }

    /**
     * Bulk activate, deactivate or delete templates.
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'string', 'in:activate,deactivate,delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $templates = SeoTemplate::whereIn('id', $validated['ids'])->get();
        $count = 0;

        foreach ($templates as $template) {
            match ($validated['action']) {
                'activate' => $template->update(['is_active' => true]),
                'deactivate' => $template->update(['is_active' => false]),
                'delete' => $template->delete(),
            };
            $count++;
        }

        return response()->json(['success' => true, 'count' => $count]);
    }

    /**
     * Toggle the active status of a template.
     */
    public function toggleActive(SeoTemplate $template): JsonResponse
    {
        $template->update(['is_active' => ! $template->is_active]);

        return response()->json([
            'status' => true,
            'is_active' => $template->is_active,
        ]);
    }

    /**
     * Preview how many metas would be affected by a bulk apply.
     */
    public function preview(SeoTemplate $template): JsonResponse
    {
        $base = SeoMeta::query();

        if ($template->model_type) {
            $base->where('seoable_type', $template->model_type);
        }

        $affectedCount = (clone $base)->where(function ($q) {
            $q->whereNull('title')->orWhere('title', '');
        })->count();

        $descCount = (clone $base)->where(function ($q) {
            $q->whereNull('description')->orWhere('description', '');
        })->count();

        return response()->json([
            'affected_count' => $affectedCount,
            'desc_count' => $descCount,
            'template_name' => $template->name,
        ]);
    }

    /**
     * Apply template to all metas of matching model type that have empty fields.
     */
    public function bulkApply(SeoTemplate $template): JsonResponse
    {
        if (! $template->is_active) {
            return response()->json(['status' => false, 'message' => 'La plantilla no está activa'], 422);
        }

        $query = SeoMeta::query();

        if ($template->model_type) {
            $query->where('seoable_type', $template->model_type);
        }

        $updated = 0;

        $query->with('seoable')->each(function (SeoMeta $meta) use ($template, &$updated) {
            if (! $meta->seoable) {
                return;
            }

            $applied = $template->applyToModel($meta->seoable);
            $fillable = [];

            foreach ($applied as $field => $value) {
                if (empty($meta->$field)) {
                    $fillable[$field] = $value;
                }
            }

            if (! empty($fillable)) {
                $meta->update($fillable);
                $updated++;
            }
        });

        return response()->json([
            'status' => true,
            'message' => "Plantilla aplicada a {$updated} registros SEO.",
            'updated' => $updated,
        ]);
    }
}
