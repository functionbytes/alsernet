<?php

namespace Modules\Document\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Document\Entities\DocumentType;
use Modules\Document\Entities\DocumentValidationCondition;

/**
 * DocumentValidationConditionController
 *
 * Manages centralized validation conditions for document workflows.
 * Administrators can define custom conditions (like is_weapon, is_dni_only)
 * and map them to specific sale_types from document_product_blockades.
 */
class DocumentValidationConditionController extends Controller
{
    /**
     * Display list of validation conditions
     */
    public function index(Request $request)
    {
        $query = DocumentValidationCondition::query();

        // Search filter
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('key', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->has('status') && $request->input('status') !== '') {
            $query->where('is_active', (bool) $request->input('status'));
        }

        $conditions = $query->ordered()->paginate(20)->withQueryString();

        return view('documents::settings.conditions.index', [
            'conditions' => $conditions,
        ]);
    }

    /**
     * Show form to create new validation condition
     */
    public function create()
    {
        $validSaleTypes = DocumentType::getValidBlockadeTypes();
        $availableTypes = DocumentValidationCondition::AVAILABLE_TYPES;

        return view('documents::settings.conditions.create', [
            'validSaleTypes' => $validSaleTypes,
            'availableTypes' => $availableTypes,
        ]);
    }

    /**
     * Store new validation condition
     */
    public function store(Request $request)
    {
        $rules = [
            'key' => 'required|string|unique:document_validation_conditions,key|max:50|regex:/^[a-z0-9_-]+$/',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'condition_type' => 'required|string|in:sale_type_match,model_field,custom_expression',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ];

        // Add conditional validation based on condition_type
        if ($request->input('condition_type') === 'sale_type_match') {
            $rules['sale_types'] = 'required|array|min:1';
            $rules['sale_types.*'] = 'required|string|max:50';
        } elseif ($request->input('condition_type') === 'model_field') {
            $rules['model_field'] = 'required|string|max:100';
            $rules['expected_value'] = 'required|array|min:1';
            $rules['expected_value.*'] = 'required';
        } elseif ($request->input('condition_type') === 'custom_expression') {
            $rules['validation_expression'] = 'required|string';
        }

        $validated = $request->validate($rules);

        try {
            DB::beginTransaction();

            $data = [
                'key' => $validated['key'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'condition_type' => $validated['condition_type'],
                'is_active' => $validated['is_active'] ?? true,
                'sort_order' => $validated['sort_order'] ?? 0,
            ];

            // Add type-specific data
            if ($validated['condition_type'] === 'sale_type_match') {
                $data['sale_types'] = $validated['sale_types'] ?? [];
            } elseif ($validated['condition_type'] === 'model_field') {
                $data['model_field'] = $validated['model_field'];
                $data['expected_value'] = $validated['expected_value'];
            } elseif ($validated['condition_type'] === 'custom_expression') {
                $data['validation_expression'] = $validated['validation_expression'];
            }

            DocumentValidationCondition::create($data);

            DB::commit();

            return redirect()
                ->route('settings.documents.conditions.index')
                ->with('success', "Condición de validación '{$validated['name']}' creada exitosamente.");
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Error creating validation condition', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al crear la condición de validación: '.$e->getMessage());
        }
    }

    /**
     * Show form to edit validation condition
     */
    public function edit(string $condition)
    {
        // Support both key and uid
        $validationCondition = DocumentValidationCondition::where('key', $condition)
            ->orWhere('uid', $condition)
            ->firstOrFail();

        $validSaleTypes = DocumentType::getValidBlockadeTypes();
        $availableTypes = DocumentValidationCondition::AVAILABLE_TYPES;

        return view('documents::settings.conditions.edit', [
            'condition' => $validationCondition,
            'validSaleTypes' => $validSaleTypes,
            'availableTypes' => $availableTypes,
        ]);
    }

    /**
     * Update validation condition
     */
    public function update(Request $request, string $condition)
    {
        // Support both key and uid
        $validationCondition = DocumentValidationCondition::where('key', $condition)
            ->orWhere('uid', $condition)
            ->firstOrFail();

        $rules = [
            'key' => 'required|string|max:50|regex:/^[a-z0-9_-]+$/|unique:document_validation_conditions,key,'.$validationCondition->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'condition_type' => 'required|string|in:sale_type_match,model_field,custom_expression',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ];

        // Add conditional validation based on condition_type
        if ($request->input('condition_type') === 'sale_type_match') {
            $rules['sale_types'] = 'required|array|min:1';
            $rules['sale_types.*'] = 'required|string|max:50';
        } elseif ($request->input('condition_type') === 'model_field') {
            $rules['model_field'] = 'required|string|max:100';
            $rules['expected_value'] = 'required|array|min:1';
            $rules['expected_value.*'] = 'required';
        } elseif ($request->input('condition_type') === 'custom_expression') {
            $rules['validation_expression'] = 'required|string';
        }

        $validated = $request->validate($rules);

        try {
            DB::beginTransaction();

            $data = [
                'key' => $validated['key'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'condition_type' => $validated['condition_type'],
                'is_active' => $validated['is_active'] ?? true,
                'sort_order' => $validated['sort_order'] ?? 0,
            ];

            // Clear type-specific fields that don't belong to current type
            $data['sale_types'] = null;
            $data['model_field'] = null;
            $data['expected_value'] = null;
            $data['validation_expression'] = null;

            // Add type-specific data
            if ($validated['condition_type'] === 'sale_type_match') {
                $data['sale_types'] = $validated['sale_types'] ?? [];
            } elseif ($validated['condition_type'] === 'model_field') {
                $data['model_field'] = $validated['model_field'];
                $data['expected_value'] = $validated['expected_value'];
            } elseif ($validated['condition_type'] === 'custom_expression') {
                $data['validation_expression'] = $validated['validation_expression'];
            }

            $validationCondition->update($data);

            DB::commit();

            return redirect()
                ->route('settings.documents.conditions.index')
                ->with('success', 'Condición de validación actualizada exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Error updating validation condition', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al actualizar la condición de validación: '.$e->getMessage());
        }
    }

    /**
     * Delete validation condition
     */
    public function destroy(string $condition)
    {
        // Support both key and uid
        $validationCondition = DocumentValidationCondition::where('key', $condition)
            ->orWhere('uid', $condition)
            ->firstOrFail();

        try {
            DB::beginTransaction();

            $validationCondition->delete();

            DB::commit();

            return redirect()
                ->route('settings.documents.conditions.index')
                ->with('success', 'Condición de validación eliminada exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Error deleting validation condition', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Error al eliminar la condición de validación: '.$e->getMessage());
        }
    }

    /**
     * Toggle active status
     */
    public function toggleActive(string $condition)
    {
        $validationCondition = DocumentValidationCondition::where('key', $condition)
            ->orWhere('uid', $condition)
            ->firstOrFail();

        try {
            $validationCondition->update(['is_active' => ! $validationCondition->is_active]);

            return redirect()
                ->back()
                ->with('success', 'Estado actualizado exitosamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al actualizar el estado: '.$e->getMessage());
        }
    }
}
