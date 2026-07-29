<?php

namespace Modules\Supplier\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Supplier\Http\Requests\Template\BulkActionTemplateRequest;
use Modules\Supplier\Http\Requests\Template\CloneTemplateRequest;
use Modules\Supplier\Http\Requests\Template\StoreTemplateRequest;
use Modules\Supplier\Http\Requests\Template\UpdateTemplateRequest;
use Modules\Supplier\Models\Category\Category;
use Modules\Supplier\Models\Prompt\Prompt;
use Modules\Supplier\Models\Supplier\Supplier;

class PromptTemplatesController extends Controller
{
    public function index(Request $request): View
    {
        $pageTitle = 'Biblioteca de Plantillas de Prompts';
        $breadcrumb = 'Configuración / Proveedores / Plantillas';

        // Get filter parameters
        $search = $request->get('search');
        $category = $request->get('category');
        $contentType = $request->get('content_type');

        // Build query
        $query = Prompt::templates()
            ->orderBy('template_category')
            ->orderBy('content_type')
            ->orderBy('label');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('label', 'like', "%{$search}%")
                    ->orWhere('template_category', 'like', "%{$search}%")
                    ->orWhere('content_type', 'like', "%{$search}%");
            });
        }

        if ($category) {
            $query->where('template_category', $category);
        }

        if ($contentType) {
            $query->where('content_type', $contentType);
        }

        $perPage = (int) $request->input('per_page', 20);
        $perPage = in_array($perPage, [10, 20, 50, 100, 200]) ? $perPage : 20;

        $templates = $query->withCount('clonedPrompts')->paginate($perPage)->withQueryString();

        // Get available categories and content types for filters
        $categories = Prompt::templates()
            ->distinct()
            ->pluck('template_category')
            ->filter()
            ->sort()
            ->values();

        $contentTypes = Prompt::templates()
            ->distinct()
            ->pluck('content_type')
            ->filter()
            ->sort()
            ->values();

        // Get stats
        $stats = [
            'total_templates' => Prompt::templates()->count(),
            'total_cloned' => Prompt::notTemplates()->whereNotNull('cloned_from_template_id')->count(),
            'categories_count' => Prompt::templates()->distinct()->count('template_category'),
        ];

        // Get suppliers and categories for clone modal
        $suppliers = Supplier::where('available', true)->orderBy('label')->get();
        $allCategories = Category::where('available', true)->orderBy('name')->get();

        return view('supplier::settings.views.templates.index', compact(
            'templates',
            'categories',
            'contentTypes',
            'stats',
            'suppliers',
            'allCategories',
            'pageTitle',
            'breadcrumb'
        ));
    }

    /**
     * Show create template form
     */
    public function create(): View
    {
        $pageTitle = 'Crear Plantilla de Prompt';
        $breadcrumb = 'Configuración / Proveedores / Plantillas / Crear';

        // Get active categories
        $categories = Category::active()->orderBy('name')->get();

        return view('supplier::settings.views.templates.create', compact(
            'pageTitle',
            'breadcrumb',
            'categories'
        ));
    }

    /**
     * Store new template
     */
    public function store(StoreTemplateRequest $request)
    {
        try {
            $validated = $request->validated();
            Prompt::create([
                'uid' => Str::ulid(),
                'label' => $validated['label'],
                'template_category' => $validated['template_category'] ?? null,
                'content_type' => $validated['content_type'],
                'prompt_template' => $validated['prompt_template'],
                'scope' => 'global',
                'output_language' => $validated['output_language'],
                'tone' => $validated['tone'],
                'seo_focus' => $validated['seo_focus'] ?? false,
                'priority' => $validated['priority'] ?? 0,
                'version' => 1,
                'is_active' => true,
                'is_template' => true,
                'notes' => $validated['notes'] ?? null,
            ]);

            return redirect()
                ->route('settings.suppliers.templates.index')
                ->with('success', 'Plantilla creada exitosamente');

        } catch (\Exception $e) {
            Log::error('Error creating template: '.$e->getMessage());

            return back()
                ->withInput()
                ->with('error', 'Error al crear la plantilla');
        }
    }

    /**
     * Show edit template form
     */
    public function edit(string $templateUid): View
    {
        $template = Prompt::where('uid', $templateUid)
            ->where('is_template', true)
            ->firstOrFail();

        $pageTitle = 'Editar Plantilla: '.$template->label;
        $breadcrumb = 'Configuración / Proveedores / Plantillas / Editar';

        // Get active categories
        $categories = Category::active()->orderBy('name')->get();

        return view('supplier::settings.views.templates.edit', compact(
            'template',
            'pageTitle',
            'breadcrumb',
            'categories'
        ));
    }

    /**
     * Update template
     */
    public function update(UpdateTemplateRequest $request, string $templateUid)
    {
        try {
            $validated = $request->validated();
            $template = Prompt::where('uid', $templateUid)
                ->where('is_template', true)
                ->firstOrFail();

            $template->update([
                'label' => $validated['label'],
                'template_category' => $validated['template_category'] ?? null,
                'content_type' => $validated['content_type'],
                'prompt_template' => $validated['prompt_template'],
                'output_language' => $validated['output_language'],
                'tone' => $validated['tone'],
                'seo_focus' => $validated['seo_focus'] ?? false,
                'priority' => $validated['priority'] ?? 0,
                'notes' => $validated['notes'] ?? null,
            ]);

            return redirect()
                ->route('settings.suppliers.templates.index')
                ->with('success', 'Plantilla actualizada exitosamente');

        } catch (\Exception $e) {
            Log::error('Error updating template: '.$e->getMessage());

            return back()
                ->withInput()
                ->with('error', 'Error al actualizar la plantilla');
        }
    }

    /**
     * Delete template
     */
    public function destroy(string $templateUid)
    {
        try {
            $template = Prompt::where('uid', $templateUid)
                ->where('is_template', true)
                ->firstOrFail();

            $clonedCount = $template->clonedPrompts()->count();

            if ($clonedCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "No se puede eliminar: tiene {$clonedCount} prompt(s) creado(s) desde esta plantilla",
                ], 422);
            }

            $template->delete();

            return response()->json(['success' => true, 'message' => 'Plantilla eliminada exitosamente']);

        } catch (\Exception $e) {
            Log::error('Error deleting template: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Error al eliminar la plantilla'], 500);
        }
    }

    public function bulkAction(BulkActionTemplateRequest $request): JsonResponse
    {
        $uids = $request->validated('uids');

        $templates = Prompt::whereIn('uid', $uids)->where('is_template', true)->get();

        $deleted = 0;
        $skipped = 0;

        foreach ($templates as $template) {
            if ($template->clonedPrompts()->count() > 0) {
                $skipped++;

                continue;
            }
            $template->delete();
            $deleted++;
        }

        $message = "{$deleted} plantilla(s) eliminada(s).";
        if ($skipped > 0) {
            $message .= " {$skipped} omitida(s) por tener prompts asociados.";
        }

        return response()->json(['message' => $message, 'count' => $deleted]);
    }

    /**
     * Clone a template into a new prompt
     */
    public function clone(CloneTemplateRequest $request, string $templateUid)
    {
        try {
            $validated = $request->validated();
            $template = Prompt::where('uid', $templateUid)
                ->where('is_template', true)
                ->firstOrFail();

            // Prepare overrides
            $overrides = [
                'label' => $validated['label'],
                'scope' => $validated['scope'],
                'priority' => $validated['priority'] ?? 0,
            ];

            // Add supplier_id and category_id based on scope
            if (in_array($validated['scope'], ['supplier', 'supplier_category'])) {
                if (empty($validated['supplier_id'])) {
                    return back()->with('error', 'El proveedor es requerido para este ambito');
                }
                $overrides['supplier_id'] = $validated['supplier_id'];
            }

            if (in_array($validated['scope'], ['category', 'supplier_category'])) {
                if (empty($validated['category_id'])) {
                    return back()->with('error', 'La categoria es requerida para este ambito');
                }
                $overrides['category_id'] = $validated['category_id'];
            }

            // Clone template
            $newPrompt = $template->cloneFromTemplate($overrides);

            return back()->with('success', "Prompt creado exitosamente desde plantilla: {$newPrompt->label}");

        } catch (\Exception $e) {
            Log::error('Error cloning template: '.$e->getMessage());

            return back()->with('error', 'Error al clonar la plantilla');
        }
    }
}
