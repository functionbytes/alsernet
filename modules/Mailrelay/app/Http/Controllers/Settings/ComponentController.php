<?php

namespace Modules\Mailrelay\Http\Controllers\Settings;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Modules\Mailrelay\Entities\MailrelayLayout;
use Modules\Mailrelay\Http\Controllers\Controller;
use Modules\Mailrelay\Services\TemplateRendererService;
use Modules\Mailrelay\Services\VariableReplacementService;

class ComponentController extends Controller
{
    protected TemplateRendererService $rendererService;

    protected VariableReplacementService $variableService;

    public function __construct(
        TemplateRendererService $rendererService,
        VariableReplacementService $variableService
    ) {
        $this->rendererService = $rendererService;
        $this->variableService = $variableService;
    }

    /**
     * Display a listing of layouts/components.
     */
    public function index(Request $request): View
    {
        Gate::authorize('mailrelay.settings.components.view');

        $query = MailrelayLayout::query();

        // Filter by search
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            });
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $components = $query->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('mailrelay::settings.components.index', [
            'components' => $components,
        ]);
    }

    /**
     * Show the form for creating a new layout/component.
     */
    public function create(): View
    {
        Gate::authorize('mailrelay.settings.components.create');

        return view('mailrelay::settings.components.create');
    }

    /**
     * Store a newly created layout/component.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('mailrelay.settings.components.create');

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'type' => 'required|in:partial,layout,component',
                'content' => 'required|string',
                'description' => 'nullable|string',
                'status' => 'in:active,inactive',
                'is_default' => 'boolean',
                'sort_order' => 'nullable|integer',
            ]);

            $component = MailrelayLayout::create($validated);

            // If this is set as default, ensure others are not
            if ($component->is_default && $component->type === 'layout') {
                MailrelayLayout::where('id', '!=', $component->id)
                    ->where('type', 'layout')
                    ->update(['is_default' => false]);
            }

            return redirect()
                ->route('managers.settings.mailrelay.components.index')
                ->with('success', 'Componente creado correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al crear el componente: '.$e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified layout/component.
     */
    public function edit(int $id): View
    {
        Gate::authorize('mailrelay.settings.components.edit');

        $component = MailrelayLayout::findOrFail($id);

        return view('mailrelay::settings.components.edit', [
            'component' => $component,
        ]);
    }

    /**
     * Update the specified layout/component.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        Gate::authorize('mailrelay.settings.components.edit');

        try {
            $component = MailrelayLayout::findOrFail($id);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'type' => 'required|in:partial,layout,component',
                'content' => 'required|string',
                'description' => 'nullable|string',
                'status' => 'in:active,inactive',
                'is_default' => 'boolean',
                'sort_order' => 'nullable|integer',
            ]);

            $component->update($validated);

            // If this is set as default, ensure others are not
            if ($component->is_default && $component->type === 'layout') {
                MailrelayLayout::where('id', '!=', $component->id)
                    ->where('type', 'layout')
                    ->update(['is_default' => false]);
            }

            return redirect()
                ->route('managers.settings.mailrelay.components.index')
                ->with('success', 'Componente actualizado correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al actualizar el componente: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified layout/component.
     */
    public function destroy(int $id): RedirectResponse
    {
        Gate::authorize('mailrelay.settings.components.delete');

        try {
            $component = MailrelayLayout::findOrFail($id);

            // Check if this layout is being used by any templates
            $templatesCount = $component->templates()->count();

            if ($templatesCount > 0) {
                return redirect()
                    ->back()
                    ->with('error', "No se puede eliminar este componente porque está siendo usado por {$templatesCount} plantilla(s).");
            }

            $component->delete();

            return redirect()
                ->route('managers.settings.mailrelay.components.index')
                ->with('success', 'Componente eliminado correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al eliminar el componente: '.$e->getMessage());
        }
    }

    /**
     * Preview layout/component with AJAX.
     */
    public function previewAjax(Request $request, int $id): JsonResponse
    {
        Gate::authorize('mailrelay.settings.components.view');

        try {
            $component = MailrelayLayout::findOrFail($id);

            // Get temporary content from request (for live preview while editing)
            $tempContent = $request->input('content', $component->content);

            // Preview with example variables
            $rendered = $this->variableService->previewWithExamples($tempContent);

            // Beautify the HTML
            $rendered = $this->rendererService->beautifyHtml($rendered);

            return response()->json([
                'success' => true,
                'html' => $rendered,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle layout/component status (active/inactive).
     */
    public function toggleStatus(int $id): JsonResponse
    {
        Gate::authorize('mailrelay.settings.components.edit');

        try {
            $component = MailrelayLayout::findOrFail($id);

            $newStatus = $component->status === 'active' ? 'inactive' : 'active';
            $component->status = $newStatus;
            $component->save();

            return response()->json([
                'success' => true,
                'status' => $newStatus,
                'message' => $newStatus === 'active'
                    ? 'Componente activado correctamente'
                    : 'Componente desactivado correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Duplicate the specified layout/component.
     */
    public function duplicate(int $id): RedirectResponse
    {
        Gate::authorize('mailrelay.settings.components.create');

        try {
            $component = MailrelayLayout::findOrFail($id);

            $newComponent = $component->replicate();
            $newComponent->name = $component->name.' (Copia)';
            $newComponent->is_default = false;
            $newComponent->status = 'inactive';
            $newComponent->save();

            return redirect()
                ->route('managers.settings.mailrelay.components.edit', $newComponent->id)
                ->with('success', 'Componente duplicado correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al duplicar el componente: '.$e->getMessage());
        }
    }

    /**
     * Get layouts/components by type (API endpoint).
     */
    public function getByType(string $type): JsonResponse
    {
        try {
            if (! in_array($type, ['partial', 'layout', 'component'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid type specified',
                ], 400);
            }

            $components = MailrelayLayout::where('type', $type)
                ->where('status', 'active')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'type', 'description']);

            return response()->json([
                'success' => true,
                'components' => $components,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Set a layout as the default.
     */
    public function setDefault(int $id): JsonResponse
    {
        Gate::authorize('mailrelay.settings.components.edit');

        try {
            $component = MailrelayLayout::findOrFail($id);

            if ($component->type !== 'layout') {
                return response()->json([
                    'success' => false,
                    'error' => 'Solo los layouts pueden ser establecidos como predeterminados',
                ], 400);
            }

            $component->setAsDefault();

            return response()->json([
                'success' => true,
                'message' => 'Layout establecido como predeterminado',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
