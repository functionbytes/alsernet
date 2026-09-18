<?php

namespace Modules\Supplier\Http\Controllers\Settings\Suppliers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Supplier\Http\Requests\Product\BulkActionProductRequest;
use Modules\Supplier\Http\Requests\Product\BulkGenerateContentRequest;
use Modules\Supplier\Http\Requests\Product\UpdateProductRequest;
use Modules\Supplier\Jobs\GenerateBulkProductContentJob;
use Modules\Supplier\Models\Ai\AiContent;
use Modules\Supplier\Models\Category\Category;
use Modules\Supplier\Models\Characteristic\ModelCharacteristic;
use Modules\Supplier\Models\Characteristic\VariantCharacteristic;
use Modules\Supplier\Models\Product\Product;
use Modules\Supplier\Models\Supplier\Supplier;

class SupplierProductsController extends Controller
{
    public function index(Request $request): View
    {
        $pageTitle = 'Productos de proveedores';
        $breadcrumb = 'Configuración / Proveedores / Productos';

        $query = Product::query()
            ->with(['supplier', 'category'])
            ->withCount('attributes')
            ->withExists([
                'approvedContent as has_approved_content',
            ]);

        if ($supplierId = $request->get('supplier_id')) {
            $query->where('supplier_id', $supplierId);
        }

        if ($categoryId = $request->get('category_id')) {
            $query->where('category_id', $categoryId);
        }

        if ($request->filled('available')) {
            $query->where('available', $request->get('available') === '1');
        }

        if ($search = $request->get('search')) {
            $query->search($search);
        }

        // Hard cap the page size so a crafted ?per_page=9999 cannot pull the
        // whole catalogue into memory.
        $perPage = min(max($request->integer('per_page', 50), 1), 200);

        $items = $query->orderBy('name')->paginate($perPage)->withQueryString();

        $stats = Cache::remember('suppliers.products.stats', 60, fn () => [
            'total' => Product::count(),
            'active' => Product::where('available', true)->count(),
            'web' => Product::where('web_published', true)->count(),
        ]);

        $suppliers = Supplier::orderBy('label')->get(['id', 'label']);
        $categories = Category::orderBy('name')->get(['id', 'name']);

        return view('supplier::settings.views.products.index', compact(
            'items', 'stats', 'suppliers', 'categories',
            'pageTitle', 'breadcrumb'
        ));
    }

    public function show(int $id): View
    {
        $product = Product::with(['supplier', 'category', 'attributes' => fn ($q) => $q->orderBy('name')])
            ->withExists(['approvedContent as has_approved_content'])
            ->findOrFail($id);

        $latestContent = AiContent::where('supplier_product_id', $id)
            ->with(['contentStatus', 'prompt'])
            ->latest('id')
            ->first();

        $canValidate = $latestContent && in_array($latestContent->status, ['pending_validation', 'in_review', 'needs_revision']);
        $canReject = $canValidate;
        $canPublish = $latestContent && $latestContent->status === 'validated';

        $pageTitle = 'Detalle de producto';
        $breadcrumb = 'Configuración / Proveedores / Productos / Detalle';

        return view('supplier::settings.views.products.show', compact(
            'product', 'pageTitle', 'breadcrumb',
            'latestContent', 'canValidate', 'canReject', 'canPublish'
        ));
    }

    /**
     * Características ERP (modelo + variante) de un producto, en solo lectura.
     *
     * Mismo shape de datos que characteristicsPanel() de SupplierContentController,
     * pero parametrizado por product_id directamente (no requiere que exista un
     * AiContent asociado) y sin el catálogo de características disponibles, ya
     * que esta vista no permite añadir/editar/eliminar — solo consultar.
     */
    public function characteristics(int $id): JsonResponse
    {
        $product = Product::with('attributes')->findOrFail($id);
        $attributeIds = $product->attributes->pluck('id');

        return response()->json([
            'success' => true,
            'model_assignments' => ModelCharacteristic::where('product_id', $id)
                ->with('characteristic:id,nombre')
                ->get(),
            'variant_assignments' => VariantCharacteristic::where(function ($q) use ($attributeIds, $id) {
                $q->whereIn('product_attribute_id', $attributeIds)
                    ->orWhere(function ($q2) use ($id) {
                        $q2->whereNull('product_attribute_id')->where('product_id', $id);
                    });
            })
                ->with(['characteristic:id,nombre', 'value:id,nombre'])
                ->get(),
        ]);
    }

    public function edit(int $id): View
    {
        $product = Product::findOrFail($id);
        $pageTitle = 'Editar producto';
        $breadcrumb = 'Configuración / Proveedores / Productos / Editar';

        return view('supplier::settings.views.products.edit', compact('product', 'pageTitle', 'breadcrumb'));
    }

    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $validated = $request->validated();

        $product->update($validated);

        Cache::forget('suppliers.products.stats');

        return response()->json([
            'message' => 'Producto actualizado correctamente.',
            'product' => $product,
        ]);
    }

    public function bulkAction(BulkActionProductRequest $request): JsonResponse
    {
        $action = $request->validated('action');
        $ids = $request->validated('ids');
        $count = 0;

        // Iterate per row so the SupplierProductObserver fires its events and
        // pushes changes through the ERP sync pipeline. Mass updates would
        // bypass the observer entirely and leave ERP out of sync.
        $products = Product::whereIn('id', $ids)->get();

        if ($action === 'delete') {
            DB::transaction(function () use ($products, &$count) {
                foreach ($products as $product) {
                    $product->delete();
                    $count++;
                }
            });
        } else {
            $field = match ($action) {
                'enable', 'disable' => 'available',
                'web_on', 'web_off' => 'web_published',
            };
            $value = match ($action) {
                'enable', 'web_on' => true,
                'disable', 'web_off' => false,
            };

            foreach ($products as $product) {
                $product->{$field} = $value;
                if ($product->save()) {
                    $count++;
                }
            }
        }

        Cache::forget('suppliers.products.stats');

        $labels = [
            'delete' => 'eliminado(s)',
            'enable' => 'activado(s)',
            'disable' => 'desactivado(s)',
            'web_on' => 'publicado(s) en web',
            'web_off' => 'despublicado(s) de web',
        ];

        return response()->json([
            'message' => "{$count} producto(s) {$labels[$action]}.",
            'count' => $count,
        ]);
    }

    public function bulkGenerateContent(BulkGenerateContentRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $batchId = (string) Str::ulid();
        $userId = auth()->id();

        foreach ($validated['ids'] as $productId) {
            GenerateBulkProductContentJob::dispatch(
                productId: $productId,
                promptUid: $validated['prompt_uid'],
                model: $validated['model'],
                webSearch: (bool) ($validated['web_search'] ?? false),
                userId: $userId,
                batchId: $batchId,
            );
        }

        return response()->json([
            'success' => true,
            'batch_id' => $batchId,
            'queued' => count($validated['ids']),
            'message' => count($validated['ids']).' productos encolados para generación. Revisa los resultados en Contenido generado.',
        ]);
    }
}
