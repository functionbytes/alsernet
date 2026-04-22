<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\Helpdesk\Models\CustomAttribute;

class AttributesController extends Controller
{
    /**
     * Display attributes list.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', CustomAttribute::class);

        $query = CustomAttribute::query()->withoutGlobalScope('active');

        // Apply filters
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('key', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        if ($request->has('format') && $request->format !== 'all') {
            $query->where('format', $request->format);
        }

        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->where('active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('active', false);
            }
        }

        $attributes = $query
            ->orderBy('internal', 'desc')
            ->orderBy('name', 'asc')
            ->paginate(50)
            ->appends($request->query());

        return view('helpdesk::managers.settings.helpdesk.attributes.index', [
            'attributes' => $attributes,
        ]);
    }

    /**
     * Show create attribute form.
     */
    public function create()
    {
        $this->authorize('create', CustomAttribute::class);

        return view('helpdesk::managers.settings.helpdesk.attributes.create');
    }

    /**
     * Store new attribute.
     */
    public function store(Request $request)
    {
        $this->authorize('create', CustomAttribute::class);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:60',
                Rule::unique('helpdesk.helpdesk_attributes', 'name')->where(function ($query) {
                    return $query->where('active', true);
                }),
            ],
            'key' => [
                'required',
                'string',
                'max:60',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('helpdesk.helpdesk_attributes', 'key'),
            ],
            'format' => 'required|string|max:50',
            'required' => 'nullable|boolean',
            'permission' => 'required|in:userCanView,userCanEdit,agentCanEdit',
            'description' => 'nullable|string|max:600',
            'options' => 'nullable|array',
            'options.*' => 'nullable|string',
            'min_value' => 'nullable|numeric',
            'max_value' => 'nullable|numeric',
            'active' => 'nullable|boolean',
        ]);

        // If key is not provided, generate from name
        if (empty($validated['key'])) {
            $validated['key'] = Str::slug($validated['name'], '_');
        }

        // Set default type
        $validated['type'] = 'conversation';

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
            ->route('manager.helpdesk.settings.attributes.index')
            ->with('success', "Atributo '{$attribute->name}' creado correctamente");
    }

    /**
     * Show edit attribute form.
     */
    public function edit($id)
    {
        $attribute = CustomAttribute::withoutGlobalScope('active')->findOrFail($id);

        $this->authorize('update', $attribute);

        return view('helpdesk::managers.settings.helpdesk.attributes.edit', [
            'attribute' => $attribute,
        ]);
    }

    /**
     * Update attribute.
     */
    public function update(Request $request, $id)
    {
        $attribute = CustomAttribute::withoutGlobalScope('active')->findOrFail($id);

        $this->authorize('update', $attribute);

        $rules = [
            'name' => [
                'required',
                'string',
                'max:60',
                Rule::unique('helpdesk.helpdesk_attributes', 'name')
                    ->ignore($attribute->id)
                    ->where(function ($query) {
                        return $query->where('active', true);
                    }),
            ],
            'format' => 'required|string|max:50',
            'required' => 'nullable|boolean',
            'permission' => 'required|in:userCanView,userCanEdit,agentCanEdit',
            'description' => 'nullable|string|max:600',
            'options' => 'nullable|array',
            'options.*' => 'nullable|string',
            'min_value' => 'nullable|numeric',
            'max_value' => 'nullable|numeric',
            'active' => 'nullable|boolean',
        ];

        // Internal attributes have restrictions
        if ($attribute->internal) {
            unset($rules['required'], $rules['permission']);
        }

        $validated = $request->validate($rules);

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
            ->route('manager.helpdesk.settings.attributes.index')
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
            ->route('manager.helpdesk.settings.attributes.index')
            ->with('success', 'Atributo eliminado correctamente');
    }

    /**
     * Toggle attribute active status.
     */
    public function toggleActive($id)
    {
        $attribute = CustomAttribute::withoutGlobalScope('active')->findOrFail($id);

        $this->authorize('update', $attribute);

        $attribute->update([
            'active' => ! $attribute->active,
        ]);

        $status = $attribute->active ? 'activado' : 'desactivado';

        return back()->with('success', "Atributo {$status} correctamente");
    }
}
