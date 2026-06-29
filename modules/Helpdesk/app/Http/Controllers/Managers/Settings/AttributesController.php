<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Helpdesk\Http\Requests\StoreCustomAttributeRequest;
use Modules\Helpdesk\Http\Requests\UpdateCustomAttributeRequest;
use Modules\Helpdesk\Models\CustomAttribute;

class AttributesController extends Controller
{
    /**
     * Display attributes list grouped by model_type.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', CustomAttribute::class);

        $query = CustomAttribute::query()->withoutGlobalScope('active');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('key', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('format')) {
            $query->where('format', $request->format);
        }

        if ($request->filled('permission')) {
            $query->where('permission', $request->permission);
        }

        if ($request->filled('status')) {
            $query->where('active', $request->status === 'active');
        }

        $grouped = $query
            ->orderBy('internal', 'desc')
            ->orderBy('name', 'asc')
            ->get()
            ->groupBy('model_type');

        return view('helpdesk::settings.attributes.index', [
            'contactAttributes' => $grouped['contact'] ?? collect(),
            'conversationAttributes' => $grouped['conversation'] ?? collect(),
            'contactCount' => ($grouped['contact'] ?? collect())->count(),
            'conversationCount' => ($grouped['conversation'] ?? collect())->count(),
        ]);
    }

    /**
     * Show create attribute form.
     */
    public function create()
    {
        $this->authorize('create', CustomAttribute::class);

        return view('helpdesk::settings.attributes.create');
    }

    /**
     * Store new attribute.
     */
    public function store(StoreCustomAttributeRequest $request)
    {
        $this->authorize('create', CustomAttribute::class);

        $validated = $request->validated();

        // If key is not provided, generate from name
        if (empty($validated['key'])) {
            $validated['key'] = Str::slug($validated['name'], '_');
        }

        // Set default type (legacy field, kept for backwards compat)
        $validated['type'] = $validated['model_type'] ?? 'contact';

        // Build config array based on format
        $config = [];
        if (in_array($validated['format'], ['select', 'checkboxGroup']) && ! empty($validated['options'])) {
            $config['options'] = array_filter($validated['options']);
        }

        if ($validated['format'] === 'number') {
            if (isset($validated['min_value'])) {
                $config['min'] = $validated['min_value'];
            }
            if (isset($validated['max_value'])) {
                $config['max'] = $validated['max_value'];
            }
        }

        $validated['config'] = ! empty($config) ? $config : null;

        // Set defaults
        $validated['required'] = $request->has('required') ? true : false;
        $validated['active'] = $request->has('active') ? true : false;

        // Remove fields that aren't in the model
        unset($validated['options'], $validated['min_value'], $validated['max_value']);

        $attribute = CustomAttribute::create($validated);

        return redirect()
            ->route('settings.helpdesk.attributes.index')
            ->with('success', "Atributo '{$attribute->name}' creado correctamente");
    }

    /**
     * Show edit attribute form.
     */
    public function edit($id)
    {
        $attribute = CustomAttribute::withoutGlobalScope('active')->findOrFail($id);

        $this->authorize('update', $attribute);

        return view('helpdesk::settings.attributes.edit', [
            'attribute' => $attribute,
        ]);
    }

    /**
     * Update attribute.
     */
    public function update(UpdateCustomAttributeRequest $request, $id)
    {
        $attribute = CustomAttribute::withoutGlobalScope('active')->findOrFail($id);

        $this->authorize('update', $attribute);

        $validated = $request->validated();

        // Build config array based on format
        $config = [];
        if (in_array($validated['format'], ['select', 'checkboxGroup']) && ! empty($validated['options'])) {
            $config['options'] = array_filter($validated['options']);
        }

        if ($validated['format'] === 'number') {
            if (isset($validated['min_value'])) {
                $config['min'] = $validated['min_value'];
            }
            if (isset($validated['max_value'])) {
                $config['max'] = $validated['max_value'];
            }
        }

        $validated['config'] = ! empty($config) ? $config : null;

        // Set defaults
        $validated['required'] = $request->has('required') ? true : false;
        $validated['active'] = $request->has('active') ? true : false;

        // Remove fields that aren't in the model
        unset($validated['options'], $validated['min_value'], $validated['max_value']);

        $attribute->update($validated);

        return redirect()
            ->route('settings.helpdesk.attributes.index')
            ->with('success', "Atributo '{$attribute->name}' actualizado correctamente");
    }

    /**
     * Delete attribute.
     */
    public function destroy($id)
    {
        $attribute = CustomAttribute::withoutGlobalScope('active')->findOrFail($id);

        $this->authorize('delete', $attribute);

        if ($attribute->internal) {
            return back()->with('error', 'No se pueden eliminar atributos internos del sistema');
        }

        $attribute->delete();

        return redirect()
            ->route('settings.helpdesk.attributes.index')
            ->with('success', 'Atributo eliminado correctamente');
    }

    /**
     * Toggle attribute active status (AJAX PATCH).
     */
    public function toggleActive($id): JsonResponse
    {
        $attribute = CustomAttribute::withoutGlobalScope('active')->findOrFail($id);

        $this->authorize('update', $attribute);

        $attribute->update(['active' => ! $attribute->active]);

        $status = $attribute->active ? 'activado' : 'desactivado';

        return response()->json([
            'success' => true,
            'active' => $attribute->active,
            'message' => "Atributo {$status} correctamente",
        ]);
    }
}
