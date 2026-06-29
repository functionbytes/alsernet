<?php

namespace Modules\Mailrelay\Http\Controllers\Settings;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Mailrelay\Entities\MailrelayVariable;
use Modules\Mailrelay\Http\Controllers\Controller;
use Modules\Mailrelay\Http\Requests\Settings\StoreVariableRequest;
use Modules\Mailrelay\Http\Requests\Settings\UpdateVariableRequest;

class VariableController extends Controller
{
    /**
     * Display a listing of variables.
     */
    public function index(Request $request): View
    {
        Gate::authorize('mailrelay.settings.variables.view');

        $query = MailrelayVariable::query();

        // Filter by search
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('variable_key', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        // Filter by module
        if ($request->filled('module')) {
            $query->where('module', $request->input('module'));
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter system/custom
        if ($request->filled('is_system')) {
            $query->where('is_system', $request->boolean('is_system'));
        }

        $variables = $query->orderBy('category')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('mailrelay::settings.variables.index', [
            'variables' => $variables,
        ]);
    }

    /**
     * Show the form for creating a new variable.
     */
    public function create(): View
    {
        Gate::authorize('mailrelay.settings.variables.create');

        return view('mailrelay::settings.variables.create');
    }

    /**
     * Store a newly created variable.
     */
    public function store(StoreVariableRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();

            MailrelayVariable::create($validated);

            return redirect()
                ->route('managers.settings.mailrelay.variables.index')
                ->with('success', 'Variable creada correctamente.');
        } catch (\Exception $e) {
            Log::error('Mailrelay variable create failed', ['error' => $e->getMessage()]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al crear la variable. Por favor, inténtalo de nuevo.');
        }
    }

    /**
     * Show the form for editing the specified variable.
     */
    public function edit(int $id): View
    {
        Gate::authorize('mailrelay.settings.variables.edit');

        $variable = MailrelayVariable::findOrFail($id);

        return view('mailrelay::settings.variables.edit', [
            'variable' => $variable,
        ]);
    }

    /**
     * Update the specified variable.
     */
    public function update(UpdateVariableRequest $request, int $id): RedirectResponse
    {
        try {
            $variable = MailrelayVariable::findOrFail($id);

            $validated = $request->validated();

            $variable->update($validated);

            return redirect()
                ->route('managers.settings.mailrelay.variables.index')
                ->with('success', 'Variable actualizada correctamente.');
        } catch (\Exception $e) {
            Log::error('Mailrelay variable update failed', ['error' => $e->getMessage()]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al actualizar la variable. Por favor, inténtalo de nuevo.');
        }
    }

    /**
     * Remove the specified variable.
     */
    public function destroy(int $id): RedirectResponse
    {
        Gate::authorize('mailrelay.settings.variables.delete');

        try {
            $variable = MailrelayVariable::findOrFail($id);

            // Prevent deletion of system variables
            if ($variable->is_system) {
                return redirect()
                    ->back()
                    ->with('error', 'No se pueden eliminar variables del sistema.');
            }

            $variable->delete();

            return redirect()
                ->route('managers.settings.mailrelay.variables.index')
                ->with('success', 'Variable eliminada correctamente.');
        } catch (\Exception $e) {
            Log::error('Mailrelay variable delete failed', ['error' => $e->getMessage()]);

            return redirect()
                ->back()
                ->with('error', 'Error al eliminar la variable. Por favor, inténtalo de nuevo.');
        }
    }

    /**
     * Toggle variable status (active/inactive).
     */
    public function toggleStatus(int $id): JsonResponse
    {
        Gate::authorize('mailrelay.settings.variables.edit');

        try {
            $variable = MailrelayVariable::findOrFail($id);

            $newStatus = $variable->status === 'active' ? 'inactive' : 'active';
            $variable->status = $newStatus;
            $variable->save();

            return response()->json([
                'success' => true,
                'status' => $newStatus,
                'message' => $newStatus === 'active'
                    ? 'Variable activada correctamente'
                    : 'Variable desactivada correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Ha ocurrido un error. Por favor, inténtalo de nuevo.',
            ], 500);
        }
    }

    /**
     * Get variables by category (API endpoint).
     */
    public function getByCategory(string $category): JsonResponse
    {
        try {
            $allowedCategories = ['system', 'customer', 'order', 'document', 'custom'];

            if (! in_array($category, $allowedCategories)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid category specified',
                ], 400);
            }

            $variables = MailrelayVariable::where('category', $category)
                ->where('status', 'active')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(function ($variable) {
                    return [
                        'id' => $variable->id,
                        'name' => $variable->name,
                        'variable_key' => $variable->variable_key,
                        'placeholder' => $variable->getPlaceholder(),
                        'description' => $variable->description,
                        'example_value' => $variable->example_value,
                        'category' => $variable->category,
                        'module' => $variable->module,
                    ];
                });

            return response()->json([
                'success' => true,
                'variables' => $variables,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Ha ocurrido un error. Por favor, inténtalo de nuevo.',
            ], 500);
        }
    }

    /**
     * Get variables by module (API endpoint).
     */
    public function getByModule(string $module): JsonResponse
    {
        try {
            $variables = MailrelayVariable::where('module', $module)
                ->where('status', 'active')
                ->orderBy('category')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(function ($variable) {
                    return [
                        'id' => $variable->id,
                        'name' => $variable->name,
                        'variable_key' => $variable->variable_key,
                        'placeholder' => $variable->getPlaceholder(),
                        'description' => $variable->description,
                        'example_value' => $variable->example_value,
                        'category' => $variable->category,
                        'module' => $variable->module,
                    ];
                });

            return response()->json([
                'success' => true,
                'variables' => $variables,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Ha ocurrido un error. Por favor, inténtalo de nuevo.',
            ], 500);
        }
    }

    /**
     * Get all active variables (API endpoint).
     */
    public function getAll(): JsonResponse
    {
        try {
            $variables = MailrelayVariable::where('status', 'active')
                ->orderBy('category')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(function ($variable) {
                    return [
                        'id' => $variable->id,
                        'name' => $variable->name,
                        'variable_key' => $variable->variable_key,
                        'placeholder' => $variable->getPlaceholder(),
                        'description' => $variable->description,
                        'example_value' => $variable->example_value,
                        'category' => $variable->category,
                        'module' => $variable->module,
                    ];
                });

            return response()->json([
                'success' => true,
                'variables' => $variables,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Ha ocurrido un error. Por favor, inténtalo de nuevo.',
            ], 500);
        }
    }

    /**
     * Get variables grouped by category (API endpoint).
     */
    public function getGrouped(): JsonResponse
    {
        try {
            $variables = MailrelayVariable::where('status', 'active')
                ->orderBy('category')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->groupBy('category')
                ->map(function ($group, $category) {
                    return [
                        'category' => $category,
                        'variables' => $group->map(function ($variable) {
                            return [
                                'id' => $variable->id,
                                'name' => $variable->name,
                                'variable_key' => $variable->variable_key,
                                'placeholder' => $variable->getPlaceholder(),
                                'description' => $variable->description,
                                'example_value' => $variable->example_value,
                                'module' => $variable->module,
                            ];
                        }),
                    ];
                });

            return response()->json([
                'success' => true,
                'variables' => array_values($variables->toArray()),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Ha ocurrido un error. Por favor, inténtalo de nuevo.',
            ], 500);
        }
    }
}
