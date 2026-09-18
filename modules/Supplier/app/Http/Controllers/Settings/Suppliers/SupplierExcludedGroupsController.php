<?php

namespace Modules\Supplier\Http\Controllers\Settings\Suppliers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Supplier\Models\Category\Category;
use Modules\Supplier\Models\Category\Subfamily;
use Modules\Supplier\Models\Product\Product;
use Modules\Supplier\Models\Sync\ExcludedErpGroup;

class SupplierExcludedGroupsController extends Controller
{
    public function index(): View
    {
        $groups = ExcludedErpGroup::orderBy('erp_id')->get();

        // Fix 8: resolver labels desde BD local para los que no tienen label
        $erpIds = $groups->pluck('erp_id')->all();
        $subfamilyNames = Subfamily::whereIn('erp_id', $erpIds)->pluck('name', 'erp_id');
        $categoryNames  = Category::whereIn('erp_id', $erpIds)->pluck('name', 'erp_id');

        $groups->each(function ($group) use ($subfamilyNames, $categoryNames) {
            if (! $group->label) {
                $group->resolved_label = $subfamilyNames[$group->erp_id]
                    ?? $categoryNames[$group->erp_id]
                    ?? null;
            } else {
                $group->resolved_label = $group->label;
            }
        });

        // Fix 5: contar productos ya sincronizados en grupos excluidos
        $excludedErpIds = $groups->pluck('erp_id')->all();
        $affectedProductCount = empty($excludedErpIds) ? 0 : Product::where(function ($q) use ($excludedErpIds) {
            $q->whereIn('erp_category_id', $excludedErpIds)
              ->orWhereIn('erp_subfamily_id', $excludedErpIds);
        })->count();

        return view('supplier::settings.views.sync.excluded-groups', [
            'groups'               => $groups,
            'affectedProductCount' => $affectedProductCount,
            'routes'               => [
                'store'   => route('settings.suppliers.sync.excluded-groups.store'),
                'bulk'    => route('settings.suppliers.sync.excluded-groups.bulk'),
                'destroy' => route('settings.suppliers.sync.excluded-groups.destroy', ':id'),
                'update'  => route('settings.suppliers.sync.excluded-groups.update', ':id'),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'erp_id' => 'required|integer|min:1|unique:supplier_excluded_erp_groups,erp_id',
            'label'  => 'nullable|string|max:120',
            'reason' => 'nullable|string|max:255',
        ], [
            'erp_id.unique' => 'Este ID ya está en la lista de exclusión.',
        ]);

        $group = ExcludedErpGroup::create($validated);

        return response()->json([
            'success' => true,
            'message' => "ID {$group->erp_id} añadido a la lista de exclusión.",
            'group'   => $group,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $group = ExcludedErpGroup::findOrFail($id);

        $validated = $request->validate([
            'label'  => 'nullable|string|max:120',
            'reason' => 'nullable|string|max:255',
        ]);

        $group->update($validated);

        return response()->json(['success' => true, 'message' => 'Actualizado.']);
    }

    public function destroy(int $id): JsonResponse
    {
        $group = ExcludedErpGroup::findOrFail($id);
        $erpId = $group->erp_id;
        $group->delete();

        return response()->json([
            'success' => true,
            'message' => "ID {$erpId} eliminado de la exclusión.",
        ]);
    }

    public function bulkStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids'    => 'required|string',
            'reason' => 'nullable|string|max:255',
        ]);

        $raw    = preg_split('/[\s,;]+/', trim($validated['ids']));
        $ids    = array_filter(array_map('intval', $raw), fn ($v) => $v > 0);
        $reason = $validated['reason'] ?? null;

        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No se encontraron IDs válidos.'], 422);
        }

        $existing = ExcludedErpGroup::whereIn('erp_id', $ids)->pluck('erp_id')->all();
        $toInsert = array_diff($ids, $existing);

        $created = 0;
        foreach ($toInsert as $erpId) {
            ExcludedErpGroup::create(['erp_id' => $erpId, 'reason' => $reason]);
            $created++;
        }

        $skipped = count($existing);
        $msg = "{$created} ID(s) añadidos.";
        if ($skipped) {
            $msg .= " {$skipped} ya existían y se omitieron.";
        }

        return response()->json(['success' => true, 'message' => $msg, 'created' => $created, 'skipped' => $skipped]);
    }
}
