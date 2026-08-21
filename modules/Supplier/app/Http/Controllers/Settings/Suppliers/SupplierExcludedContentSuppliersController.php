<?php

namespace Modules\Supplier\Http\Controllers\Settings\Suppliers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Modules\Supplier\Models\Supplier\Supplier;
use Modules\Supplier\Models\Sync\ExcludedContentSupplier;

class SupplierExcludedContentSuppliersController extends Controller
{
    public function index(): View
    {
        $excluded = ExcludedContentSupplier::with('supplier')->get()
            ->sortBy(fn ($row) => $row->supplier?->label ?? '')
            ->values();

        $suppliers = Supplier::orderBy('label')->get(['id', 'label', 'code']);

        return view('supplier::settings.views.content.excluded-suppliers', [
            'excluded' => $excluded,
            'suppliers' => $suppliers,
            'routes' => [
                'store' => route('settings.suppliers.content.excluded-suppliers.store'),
                'storeByCode' => route('settings.suppliers.content.excluded-suppliers.store-by-code'),
                'import' => route('settings.suppliers.content.excluded-suppliers.import'),
                'destroy' => route('settings.suppliers.content.excluded-suppliers.destroy', ':id'),
                'update' => route('settings.suppliers.content.excluded-suppliers.update', ':id'),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'supplier_id' => 'required|integer|exists:suppliers,id|unique:supplier_excluded_content_suppliers,supplier_id',
            'reason' => 'nullable|string|max:255',
        ], [
            'supplier_id.unique' => 'Este proveedor ya está en la lista de exclusión.',
        ]);

        $excluded = ExcludedContentSupplier::create($validated)->load('supplier');

        return response()->json([
            'success' => true,
            'message' => "{$excluded->supplier->label} añadido a la lista de exclusión.",
            'excluded' => $excluded,
        ]);
    }

    /**
     * Añade uno o varios proveedores por código/ID ERP escritos a mano (sin archivo),
     * para el caso en que el proveedor todavía no aparece en el selector porque no
     * se ha sincronizado localmente. Reutiliza el mismo matching que import().
     */
    public function storeByCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'values' => 'required|string',
            'reason' => 'nullable|string|max:255',
        ]);

        $lines = collect(preg_split('/[\r\n,;]+/', $validated['values']))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->unique()
            ->values();

        if ($lines->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Escribe al menos un código o ID.'], 422);
        }

        $result = $this->matchAndExclude($lines, $validated['reason'] ?? null);

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'excluded' => $result['excluded'],
            'not_found' => $result['not_found'],
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $excluded = ExcludedContentSupplier::findOrFail($id);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        $excluded->update($validated);

        return response()->json(['success' => true, 'message' => 'Actualizado.']);
    }

    public function destroy(int $id): JsonResponse
    {
        $excluded = ExcludedContentSupplier::with('supplier')->findOrFail($id);
        $label = $excluded->supplier?->label ?? "proveedor #{$excluded->supplier_id}";
        $excluded->delete();

        return response()->json([
            'success' => true,
            'message' => "{$label} eliminado de la exclusión.",
        ]);
    }

    /**
     * Import a batch of suppliers to exclude from a plain-text/CSV file — one
     * supplier code or ERP ID per line. Matches against the local `suppliers`
     * catalog (already synced from ERP), so unmatched lines are reported back
     * instead of silently failing.
     */
    public function import(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:txt,csv|max:1024',
            'reason' => 'nullable|string|max:255',
        ]);

        $lines = collect(preg_split('/[\r\n,;]+/', file_get_contents($validated['file']->getRealPath())))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->unique()
            ->values();

        if ($lines->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'El archivo no contiene ningún valor.'], 422);
        }

        $result = $this->matchAndExclude($lines, $validated['reason'] ?? null);

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'matched' => $result['matched'],
            'skipped' => $result['skipped'],
            'not_found' => $result['not_found'],
        ]);
    }

    /**
     * Matchea una lista de códigos/IDs ERP contra el catálogo local de proveedores
     * y crea la exclusión para cada uno que no estuviera ya excluido. Compartido por
     * import() (archivo) y storeByCode() (texto libre en el modal).
     *
     * @param  Collection<int, string>  $lines
     * @return array{matched: int, skipped: int, not_found: array, excluded: array, message: string}
     */
    private function matchAndExclude($lines, ?string $reason): array
    {
        $matched = 0;
        $skipped = 0;
        $notFound = [];
        $excluded = [];

        foreach ($lines as $line) {
            $supplier = Supplier::where('code', $line)->orWhere('erp_id', $line)->first();

            if (! $supplier) {
                $notFound[] = $line;

                continue;
            }

            $exists = ExcludedContentSupplier::where('supplier_id', $supplier->id)->exists();
            if ($exists) {
                $skipped++;

                continue;
            }

            $excluded[] = ExcludedContentSupplier::create(['supplier_id' => $supplier->id, 'reason' => $reason])
                ->load('supplier');
            $matched++;
        }

        $msg = "{$matched} proveedor(es) añadidos.";
        if ($skipped) {
            $msg .= " {$skipped} ya estaban excluidos.";
        }
        if (! empty($notFound)) {
            $msg .= ' '.count($notFound)." valor(es) sin coincidencia: {$this->joinSample($notFound)}.";
        }

        return [
            'matched' => $matched,
            'skipped' => $skipped,
            'not_found' => $notFound,
            'excluded' => $excluded,
            'message' => $msg,
        ];
    }

    private function joinSample(array $values, int $limit = 5): string
    {
        $sample = array_slice($values, 0, $limit);
        $suffix = count($values) > $limit ? '…' : '';

        return implode(', ', $sample).$suffix;
    }
}
