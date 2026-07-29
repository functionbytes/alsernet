<?php

namespace Modules\Supplier\Http\Controllers\Settings\Suppliers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Supplier\Http\Requests\Supplier\BulkActionSupplierRequest;
use Modules\Supplier\Http\Requests\Supplier\StoreSupplierRequest;
use Modules\Supplier\Http\Requests\Supplier\UpdateSupplierRequest;
use Modules\Supplier\Models\Prompt\Prompt;
use Modules\Supplier\Models\Ai\AiContent;
use Modules\Supplier\Models\Source\Source;
use Modules\Supplier\Models\Supplier\Supplier;
use Modules\Supplier\Services\SourceConfigurationService;

class SuppliersController extends Controller
{
    public function __construct(
        private readonly SourceConfigurationService $sourceConfigService
    ) {}

    /**
     * Display suppliers list with pagination
     */
    public function index(Request $request): View
    {
        $pageTitle = 'Gestión de Proveedores';
        $breadcrumb = 'Configuración / Proveedores';

        $searchKey = $request->get('search');
        $available = $request->get('available');

        $query = Supplier::query();

        if ($searchKey) {
            $query->where(function ($q) use ($searchKey) {
                $q->where('label', 'like', "%{$searchKey}%")
                    ->orWhere('code', 'like', "%{$searchKey}%")
                    ->orWhere('email', 'like', "%{$searchKey}%");
            });
        }

        if ($available !== null && $available !== '') {
            $query->where('available', $available);
        }

        $perPage = (int) $request->input('per_page', 15);
        $perPage = in_array($perPage, [10, 20, 50, 100, 200]) ? $perPage : 15;

        $suppliers = $query->orderByDesc('priority')
            ->orderBy('label')
            ->paginate($perPage)
            ->withQueryString();

        $stats = Cache::remember('suppliers.list.stats', 60, function () {
            $aggregated = Supplier::query()->selectRaw(
                'COUNT(*) as total, '
                .'SUM(CASE WHEN available = 1 THEN 1 ELSE 0 END) as active, '
                .'SUM(CASE WHEN available = 0 THEN 1 ELSE 0 END) as inactive, '
                .'SUM(CASE WHEN last_sync_at IS NOT NULL THEN 1 ELSE 0 END) as synced'
            )->first();

            return [
                'total' => (int) $aggregated->total,
                'active' => (int) $aggregated->active,
                'inactive' => (int) $aggregated->inactive,
                'synced' => (int) $aggregated->synced,
            ];
        });

        $limboCounts = AiContent::where('status', 'pending_generation')->count();

        return view('supplier::settings.views.suppliers.suppliers.index', compact('suppliers', 'searchKey', 'available', 'pageTitle', 'breadcrumb', 'stats', 'limboCounts'));
    }

    /**
     * Show create form
     */
    public function create(): View
    {
        $pageTitle = 'Crear Proveedor';
        $breadcrumb = 'Configuración / Proveedores / Crear';

        return view('supplier::settings.views.suppliers.suppliers.create', compact('pageTitle', 'breadcrumb'));
    }

    /**
     * Store new supplier
     */
    public function store(StoreSupplierRequest $request): JsonResponse
    {
        try {
            $supplier = Supplier::create($request->validated());

            Cache::forget('suppliers.list.stats');

            return response()->json([
                'success' => true,
                'message' => 'Proveedor creado exitosamente',
                'supplier' => $supplier,
            ]);

        } catch (\Exception $e) {
            Log::error('Error creating supplier: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al crear el proveedor',
            ], 500);
        }
    }

    /**
     * Show edit form
     */
    public function edit(string $uid): View
    {
        $supplier = Supplier::byUid($uid)->firstOrFail();
        $pageTitle = 'Editar Proveedor';
        $breadcrumb = 'Configuración / Proveedores / Editar';

        return view('supplier::settings.views.suppliers.suppliers.edit', compact('supplier', 'pageTitle', 'breadcrumb'));
    }

    /**
     * Update supplier
     */
    public function update(UpdateSupplierRequest $request, string $uid): JsonResponse
    {
        try {
            $supplier = Supplier::byUid($uid)->firstOrFail();

            $data = $request->validated();

            // Cast erp_id to int if present, fallback to existing value
            if (array_key_exists('erp_id', $data)) {
                $data['erp_id'] = $data['erp_id'] !== null ? (int) $data['erp_id'] : $supplier->erp_id;
            }

            // Coerce available to boolean (FormRequest may have it as string from form)
            if (array_key_exists('available', $data)) {
                $data['available'] = (bool) $data['available'];
            }

            $supplier->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Proveedor actualizado exitosamente',
                'supplier' => $supplier->fresh(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating supplier: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el proveedor',
            ], 500);
        }
    }

    /**
     * Delete supplier
     */
    public function destroy(string $uid): JsonResponse
    {
        try {
            $supplier = Supplier::byUid($uid)->firstOrFail();
            $supplier->delete();

            Cache::forget('suppliers.list.stats');

            return response()->json(['success' => true, 'message' => 'Proveedor eliminado exitosamente']);

        } catch (\Exception $e) {
            Log::error('Error deleting supplier: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el proveedor',
            ], 500);
        }
    }

    /**
     * Toggle supplier active status
     */
    public function toggle(string $uid): JsonResponse
    {
        try {
            $supplier = Supplier::byUid($uid)->firstOrFail();
            $supplier->available = ! $supplier->available;
            $supplier->save();

            Cache::forget('suppliers.list.stats');

            return response()->json([
                'success' => true,
                'available' => $supplier->available,
                'message' => $supplier->available
                    ? 'Proveedor activado exitosamente'
                    : 'Proveedor desactivado exitosamente',
            ]);

        } catch (\Exception $e) {
            Log::error('Error toggling supplier status: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado del proveedor',
            ], 500);
        }
    }

    /**
     * Get supplier statistics
     */
    public function getStats(string $uid): JsonResponse
    {
        try {
            $supplier = Supplier::byUid($uid)->firstOrFail();

            $productStats = $supplier->products()->selectRaw(
                'COUNT(*) as total, SUM(CASE WHEN available = 1 THEN 1 ELSE 0 END) as active'
            )->first();

            $stats = [
                'total_products' => (int) $productStats->total,
                'active_products' => (int) $productStats->active,
                'total_categories' => $supplier->categories()->count(),
                'last_sync_at' => $supplier->last_sync_at?->format('d/m/Y H:i'),
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats,
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting supplier stats: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas del proveedor',
            ], 500);
        }
    }

    /**
     * Get supplier details
     */
    public function show(string $uid): JsonResponse
    {
        try {
            $supplier = Supplier::byUid($uid)->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => [
                    'uid' => $supplier->uid,
                    'erp_id' => $supplier->erp_id,
                    'code' => $supplier->code,
                    'label' => $supplier->label,
                    'description' => $supplier->description,
                    'email' => $supplier->email,
                    'priority' => $supplier->priority,
                    'available' => $supplier->available,
                    'last_sync_at' => $supplier->last_sync_at?->format('d/m/Y H:i'),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting supplier: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el proveedor',
            ], 500);
        }
    }

    /**
     * Show supplier detail page
     */
    public function detail(string $uid): View
    {
        $supplier = Supplier::withCount(['categories', 'prompts'])->byUid($uid)->firstOrFail();

        $productStats = $supplier->products()->selectRaw(
            'COUNT(*) as total, '
            .'SUM(CASE WHEN available = 1 THEN 1 ELSE 0 END) as active, '
            .'SUM(CASE WHEN web_published = 1 THEN 1 ELSE 0 END) as web_published'
        )->first();

        $stats = [
            'total_products' => (int) $productStats->total,
            'active_products' => (int) $productStats->active,
            'web_published' => (int) $productStats->web_published,
            'categories' => (int) $supplier->categories_count,
            'prompts' => (int) $supplier->prompts_count,
        ];

        return view('supplier::settings.views.suppliers.suppliers.detail', compact('supplier', 'stats'));
    }

    /**
     * Get supplier products for detail page AJAX.
     */
    public function getProductsData(Request $request, string $uid): JsonResponse
    {
        try {
            $supplier = Supplier::byUid($uid)->firstOrFail();
            $query = $supplier->products()->with('category');

            if ($search = $request->input('search.value')) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            }

            if ($request->filled('available')) {
                $query->where('available', (bool) $request->input('available'));
            }

            $total = $supplier->products()->count();
            $filtered = $query->count();

            // Clamp the DataTables page size so a crafted length cannot pull
            // the whole catalogue into memory.
            $length = min(max($request->integer('length', 25), 1), 200);

            $products = $query
                ->orderByDesc('available')
                ->orderBy('name')
                ->skip(max($request->integer('start', 0), 0))
                ->take($length)
                ->withExists(['approvedContent as has_approved_content'])
                ->get()
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'uid' => $p->uid,
                    'category_id' => $p->category_id,
                    'code' => $p->code ?? '—',
                    'name' => $p->name ?? '—',
                    'category' => $p->category?->name ?? '<span class="text-muted">—</span>',
                    'available' => $p->available
                        ? '<span class="badge bg-success-subtle text-success">Activo</span>'
                        : '<span class="badge bg-light text-dark">Inactivo</span>',
                    'web' => $p->web_published
                        ? '<span class="badge bg-info-subtle text-info">Web</span>'
                        : '<span class="text-muted small">No</span>',
                    'is_default' => $p->is_default
                        ? '<i class="fas fa-star text-warning" title="Por defecto"></i>'
                        : '',
                    'has_approved_content' => (bool) $p->has_approved_content,
                ]);

            return response()->json([
                'draw' => $request->input('draw'),
                'recordsTotal' => $total,
                'recordsFiltered' => $filtered,
                'data' => $products,
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting supplier products: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Error al obtener productos'], 500);
        }
    }

    /**
     * Get supplier categories for detail page AJAX.
     */
    public function getCategoriesData(string $uid): JsonResponse
    {
        try {
            $supplier = Supplier::byUid($uid)->firstOrFail();

            // Pre-aggregate prompt counts per category in one query to avoid N+1.
            $promptCounts = $supplier->prompts()
                ->selectRaw('category_id, COUNT(*) as count')
                ->groupBy('category_id')
                ->pluck('count', 'category_id');

            $categories = $supplier->categories()
                ->with('sport')
                ->orderBy('name')
                ->get()
                ->map(fn ($c) => [
                    'category_id' => $c->id,
                    'name' => $c->name,
                    'sport' => $c->sport?->name ?? '<span class="text-muted">—</span>',
                    'is_primary' => $c->pivot->is_primary
                        ? '<i class="fas fa-check-circle text-success" title="Principal"></i>'
                        : '',
                    'is_active' => $c->pivot->is_active
                        ? '<span class="badge bg-success-subtle text-success">Activa</span>'
                        : '<span class="badge bg-light text-dark">Inactiva</span>',
                    'priority' => $c->pivot->priority,
                    'prompts_count' => $promptCounts->get($c->id, 0),
                ]);

            return response()->json(['data' => $categories]);

        } catch (\Exception $e) {
            Log::error('Error getting supplier categories: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Error al obtener categorías'], 500);
        }
    }

    /**
     * Get supplier prompts for detail page
     */
    public function getPromptsData(string $uid): JsonResponse
    {
        try {
            $supplier = Supplier::byUid($uid)->firstOrFail();

            $scopeLabels = [
                'global' => ['label' => 'Global', 'color' => 'primary'],
                'supplier' => ['label' => 'Proveedor', 'color' => 'info'],
                'category' => ['label' => 'Categoría', 'color' => 'warning'],
                'supplier_category' => ['label' => 'Prov+Cat', 'color' => 'success'],
                'source' => ['label' => 'Fuente', 'color' => 'secondary'],
            ];

            $prompts = $supplier->prompts()
                ->orderByDesc('is_active')
                ->orderBy('priority')
                ->orderBy('label')
                ->get()
                ->map(function (Prompt $p) use ($scopeLabels) {
                    $scope = $scopeLabels[$p->scope] ?? ['label' => ucfirst($p->scope), 'color' => 'light'];

                    return [
                        'uid' => $p->uid,
                        'label' => $p->label,
                        'scope' => '<span class="badge bg-'.$scope['color'].'-subtle text-'.$scope['color'].'">'.$scope['label'].'</span>',
                        'tone' => ucfirst($p->tone ?? '—'),
                        'priority' => $p->priority,
                        'seo_focus' => $p->seo_focus,
                        'is_active' => $p->is_active,
                        'is_default' => $p->is_default,
                    ];
                });

            return response()->json(['data' => $prompts]);

        } catch (\Exception $e) {
            Log::error('Error getting supplier prompts: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Error al obtener prompts'], 500);
        }
    }

    public function bulkAction(BulkActionSupplierRequest $request): JsonResponse
    {
        $action = $request->validated('action');
        $uids = $request->validated('uids');
        $count = 0;

        match ($action) {
            'delete' => $count = Supplier::whereIn('uid', $uids)->delete(),
            'enable' => $count = Supplier::whereIn('uid', $uids)->update(['available' => true]),
            'disable' => $count = Supplier::whereIn('uid', $uids)->update(['available' => false]),
        };

        Cache::forget('suppliers.list.stats');

        $labels = [
            'delete' => 'eliminado(s)',
            'enable' => 'activado(s)',
            'disable' => 'desactivado(s)',
        ];

        return response()->json([
            'message' => "{$count} proveedor(es) {$labels[$action]}.",
            'count' => $count,
        ]);
    }

    /**
     * Test the connection of every active source belonging to active suppliers
     * and return a per-source summary.
     */
    public function testAll(): JsonResponse
    {
        try {
            $sources = Source::query()
                ->active()
                ->whereHas('supplier', fn ($q) => $q->where('available', true))
                ->with('supplier:id,uid,label')
                ->get();

            $results = $sources->map(function (Source $source): array {
                $result = $this->sourceConfigService->testConnection($source);

                return [
                    'supplier' => $source->supplier?->label,
                    'supplier_uid' => $source->supplier?->uid,
                    'source_uid' => $source->uid,
                    'source_label' => $source->label,
                    'source_type' => $source->source_type,
                    'success' => $result->success,
                    'message' => $result->message,
                    'response_time' => $result->responseTime,
                ];
            });

            $passed = $results->where('success', true)->count();
            $failed = $results->count() - $passed;

            return response()->json([
                'success' => true,
                'message' => "Pruebas completadas: {$passed} correctas, {$failed} con errores.",
                'tested' => $results->count(),
                'passed' => $passed,
                'failed' => $failed,
                'results' => $results,
            ]);

        } catch (\Exception $e) {
            Log::error('Error testing all suppliers: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al probar los proveedores',
            ], 500);
        }
    }
}
