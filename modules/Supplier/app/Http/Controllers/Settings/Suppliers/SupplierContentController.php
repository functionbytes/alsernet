<?php

namespace Modules\Supplier\Http\Controllers\Settings\Suppliers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Core\Models\Setting;
use Modules\Supplier\Http\Requests\Content\ActionContentRequest;
use Modules\Supplier\Http\Requests\Content\BulkActionContentRequest;
use Modules\Supplier\Http\Requests\Content\BulkApproveContentRequest;
use Modules\Supplier\Http\Requests\Content\EditContentRequest;
use Modules\Supplier\Http\Requests\Content\RejectContentRequest;
use Modules\Supplier\Http\Requests\Content\TranslateContentRequest;
use Modules\Supplier\Http\Resources\AiContentResource;
use Modules\Supplier\Jobs\RegenerateAiContentJob;
use Modules\Supplier\Jobs\SyncCharacteristicsToErpJob;
use Modules\Supplier\Jobs\TranslateAiContentJob;
use Modules\Supplier\Models\Ai\AiContent;
use Modules\Supplier\Models\Ai\AiContentStatus;
use Modules\Supplier\Models\Category\Category;
use Modules\Supplier\Models\Category\Sport;
use Modules\Supplier\Models\Category\Subfamily;
use Modules\Supplier\Models\Characteristic\ErpCharacteristic;
use Modules\Supplier\Models\Characteristic\ErpCharacteristicValue;
use Modules\Supplier\Models\Characteristic\ModelCharacteristic;
use Modules\Supplier\Models\Characteristic\VariantCharacteristic;
use Modules\Supplier\Models\Product\Product;
use Modules\Supplier\Models\Product\ProductAttribute;
use Modules\Supplier\Models\Prompt\Prompt;
use Modules\Supplier\Models\Supplier\Supplier;
use Modules\Supplier\Presenters\AiContentPresenter;
use Modules\Supplier\Services\ContentGenerationService;
use Modules\Supplier\Services\Filters\ContentFilterService;
use Modules\Supplier\Services\Integrations\ErpModelSyncService;
use Modules\Supplier\Services\PromptSelectionService;

class SupplierContentController extends Controller
{
    public function __construct(
        protected ContentGenerationService $contentService,
        protected AiContentPresenter $presenter,
        protected ContentFilterService $filters,
        protected PromptSelectionService $promptSelectionService,
        protected ErpModelSyncService $erpModelSyncService,
    ) {}

    /**
     * Display pending content review list
     */
    public function index(Request $request): View
    {
        $pageTitle = 'Revisión de Contenido de IA';
        $breadcrumb = 'Configuración / Proveedores / Contenido';

        $tab = $request->input('tab', 'available');
        $userId = (int) auth()->id();

        $query = AiContent::query()->with(['supplier', 'prompt', 'supplierProduct.category.sport', 'supplierProduct.subfamily', 'contentStatus']);

        if ($tab === 'mine') {
            $query->where('assigned_to', $userId);
        } else {
            $query->whereNull('assigned_to');
        }

        // "Sin contenido" (on_hold) queda fuera del listado general salvo que
        // se esté filtrando explícitamente por ese estado — se seleccionan y
        // se "mandan para allá" desde Aplicar acción, y ahí quedan aparte.
        $query->excludeOnHoldByDefault($request->filled('status'));

        $this->filters->apply($query, $request);

        $perPage = (int) $request->input('per_page', 10);
        $perPage = in_array($perPage, [10, 20, 50, 100, 200]) ? $perPage : 10;

        $contents = $query->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        $myCount = AiContent::where('assigned_to', $userId)->count();

        $suppliers = Supplier::orderBy('label')->get(['id', 'label']);
        $sports = Sport::active()->orderBy('name')->get(['id', 'name']);
        $erpcategorias = Category::whereNotNull('erp_categoria_id')
            ->select('erp_categoria_id', 'erp_categoria_name', 'sport_id')
            ->groupBy('erp_categoria_id', 'erp_categoria_name', 'sport_id')
            ->orderBy('erp_categoria_name')
            ->get();
        $categories = Category::orderBy('name')->get(['id', 'name', 'erp_categoria_id', 'erp_categoria_name', 'sport_id']);
        $subfamilies = Subfamily::active()->orderBy('name')->get(['id', 'name', 'category_id']);
        $prompts = Prompt::orderBy('label')->get(['id', 'label']);

        $contentStatuses = AiContentStatus::ordered()->get()->keyBy('key');

        $stats = $this->statusCounts();
        $limboCounts = AiContent::where('status', 'pending_generation')->count();
        $canAssignOthers = auth()->user()?->can('suppliers.content.assign-others') ?? false;
        $productsWithoutContent = Product::doesntHave('aiContent')->count();

        return view('supplier::settings.views.content.index', compact(
            'pageTitle', 'breadcrumb', 'contents', 'suppliers', 'sports', 'erpcategorias', 'categories', 'subfamilies', 'prompts', 'stats', 'limboCounts', 'contentStatuses', 'tab', 'myCount', 'canAssignOthers', 'productsWithoutContent'
        ));
    }

    /**
     * Show content detail view
     */
    public function show(string $uid): View
    {
        $content = AiContent::where('uid', $uid)
            ->with([
                'supplier',
                'prompt',
                'validator',
                'validation',
                'cost',
                'supplierProduct.category.sport',
                'supplierProduct.subfamily',
                'supplierProduct.supplier',
                'supplierProduct.attributes',
            ])
            ->firstOrFail();

        $pageTitle = "Revisión de Contenido: {$content->generated_name}";
        $breadcrumb = 'Configuración / Proveedores / Contenido / Detalle';

        $erpModeloUrl = Setting::get('supplier.erp_modelo_url', 'http://interges:8080/api-gestion/modelo/');
        $erpIdModelo = $content->supplierProduct?->erp_id
            ?? ($content->source_attributes['id'] ?? null)
            ?? $content->erp_reference;

        return view('supplier::settings.views.content.show', compact('content', 'pageTitle', 'breadcrumb', 'erpModeloUrl', 'erpIdModelo'));
    }

    /**
     * Quick preview data for inline modal (JSON).
     */
    public function preview(string $uid): JsonResponse
    {
        $content = AiContent::where('uid', $uid)
            ->with(['supplier', 'prompt', 'supplierProduct'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'uid' => $content->uid,
            'title' => $content->generated_name ?? 'Sin título',
            'status' => $content->status,
            'status_label' => $content->contentStatus?->label ?? $content->status,
            'status_class' => $content->contentStatus?->badge_class ?? 'bg-secondary',
            'product' => $content->supplierProduct?->name ?? $content->generated_name ?? 'N/A',
            'product_code' => $content->erp_reference,
            'supplier' => $content->supplier?->label ?? 'N/A',
            'prompt' => $content->prompt?->label ?? null,
            'generated_name' => $content->generated_name,
            'short_description' => $content->short_description,
            'long_description' => $content->long_description,
            'seo_title' => $content->seo_title,
            'seo_description' => $content->seo_description,
            'created_at' => $content->created_at?->format('d/m/Y H:i'),
            'updated_at' => $content->updated_at?->format('d/m/Y H:i'),
            'detail_url' => route('settings.suppliers.content.show', $content->uid),
            'chat_url' => $content->supplier_product_id && in_array($content->status, [AiContent::STATUS_PENDING_VALIDATION, AiContent::STATUS_PENDING_GENERATION])
                ? route('settings.suppliers.products.chat', $content->supplier_product_id)
                : null,
        ]);
    }

    /**
     * Approve content
     */
    public function approve(Request $request, string $uid): JsonResponse
    {
        $content = AiContent::where('uid', $uid)->firstOrFail();

        try {
            $this->contentService->approveContent($content, (int) auth()->id());
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            Log::error('Error approving content: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Error al aprobar el contenido'], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Contenido aprobado correctamente',
            'content' => new AiContentResource($content->refresh()),
        ]);
    }

    /**
     * Reject content with reason
     */
    public function reject(RejectContentRequest $request, string $uid): JsonResponse
    {
        $content = AiContent::where('uid', $uid)->firstOrFail();

        try {
            $this->contentService->rejectContent($content, (int) auth()->id(), $request->validated('reason'));
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            Log::error('Error rejecting content: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Error al rechazar el contenido'], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Contenido rechazado correctamente',
            'content' => new AiContentResource($content->refresh()),
        ]);
    }

    /**
     * Regenerate content with AI
     */
    public function regenerate(Request $request, string $uid): JsonResponse
    {
        $content = AiContent::where('uid', $uid)->firstOrFail();

        try {
            $updated = $this->contentService->regenerateContent($content, $this->resolvePrompt($request->input('prompt_id')));
        } catch (\Throwable $e) {
            Log::error('Error regenerating content: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Contenido regenerado correctamente',
            'content' => new AiContentResource($updated),
        ]);
    }

    /**
     * Publish content to PrestaShop
     */
    public function publish(Request $request, string $uid): JsonResponse
    {
        try {
            $content = AiContent::where('uid', $uid)->firstOrFail();

            if ($content->status !== AiContent::STATUS_VALIDATED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se puede publicar contenido aprobado',
                ], 400);
            }

            // No hay mecanismo de publicación de producto hacia PrestaShop hoy:
            // el bridge (alsernetbridge) solo expone lectura de catálogo
            // (product.get/search/categories), sin endpoints de escritura, y el
            // antiguo SupplierSyncService (sync directo a la BD de PrestaShop)
            // se eliminó por estar roto — importaba un namespace Entities\* que
            // ya no existe en el módulo Supplier. Publicar productos requeriría
            // construir endpoints de escritura nuevos en el bridge.
            return response()->json([
                'success' => false,
                'message' => 'La publicación automática a PrestaShop no está disponible todavía.',
            ], 503);

        } catch (\Throwable $e) {
            Log::error('Error publishing content: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al publicar el contenido',
            ], 500);
        }
    }

    /**
     * Bulk approve content
     */
    public function bulkApprove(BulkApproveContentRequest $request): JsonResponse
    {
        try {
            $uids = $request->validated('content_uids');
            $results = $this->approveMany($uids);

            $successCount = collect($results)->where('success', true)->count();

            return response()->json([
                'success' => true,
                'message' => "Se aprobaron {$successCount} de ".count($results).' contenidos',
                'results' => $results,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error bulk approving content: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar el contenido en lote',
            ], 500);
        }
    }

    /**
     * Get content validation results
     */
    public function getValidation(string $uid): JsonResponse
    {
        try {
            $content = AiContent::where('uid', $uid)
                ->with('validation')
                ->firstOrFail();

            $validation = $content->validation;

            return response()->json([
                'success' => true,
                'validation' => [
                    'is_valid' => $validation->is_valid ?? false,
                    'quality_score' => $validation->quality_score ?? 0,
                    'readability_score' => $validation->readability_score ?? 0,
                    'seo_score' => $validation->seo_score ?? 0,
                    'issues' => $validation->issues ?? [],
                    'warnings' => $validation->warnings ?? [],
                    'suggestions' => $validation->suggestions ?? [],
                ],
            ]);

        } catch (\Throwable $e) {
            Log::error('Error getting validation: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la validación del contenido',
            ], 500);
        }
    }

    /**
     * Get content cost information
     */
    public function getCost(string $uid): JsonResponse
    {
        try {
            $content = AiContent::where('uid', $uid)
                ->with('cost')
                ->firstOrFail();

            $cost = $content->cost;

            return response()->json([
                'success' => true,
                'cost' => [
                    'input_tokens' => $cost->input_tokens ?? 0,
                    'output_tokens' => $cost->output_tokens ?? 0,
                    'total_tokens' => $cost->total_tokens ?? 0,
                    'cost' => $cost->total_cost ?? 0,
                    'model' => $cost->model ?? 'N/A',
                ],
            ]);

        } catch (\Throwable $e) {
            Log::error('Error getting cost: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el costo del contenido',
            ], 500);
        }
    }

    /**
     * Get stats for content review dashboard
     */
    public function getStats(): JsonResponse
    {
        try {
            $counts = $this->statusCounts();

            return response()->json([
                'success' => true,
                'data' => [
                    'pending' => $counts['pending'],
                    'approved' => $counts['approved'],
                    'rejected' => $counts['rejected'],
                    'total' => $counts['total'],
                    'avg_quality' => 0,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Error getting stats: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las estadísticas',
            ], 500);
        }
    }

    /**
     * Regenerate a given quantity from the provided list of UIDs.
     * Dispatches one job per item so the HTTP response is immediate.
     */
    public function regenerateByQuantity(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => ['required', 'string'],
            'quantity' => ['required', 'integer', 'min:1', 'max:500'],
        ]);

        $uids = array_slice($request->input('ids'), 0, (int) $request->input('quantity'));
        $userId = (int) auth()->id();

        foreach ($uids as $uid) {
            RegenerateAiContentJob::dispatch($uid, $userId);
        }

        return response()->json([
            'success' => true,
            'message' => 'Se encoló la regeneración de '.count($uids).' contenido(s)',
            'queued' => count($uids),
        ]);
    }

    /**
     * Assign a batch of content to a user (self or another if permitted)
     */
    public function bulkAssign(BulkActionContentRequest $request): JsonResponse
    {
        try {
            $uids = $request->validated('ids');
            $now = now();

            $targetUserId = (int) auth()->id();
            $targetName = 'ti';

            $requestedUserId = $request->input('user_id');
            if ($requestedUserId && auth()->user()->can('suppliers.content.assign-others')) {
                $targetUser = User::findOrFail((int) $requestedUserId);
                if (! $targetUser->can('suppliers.view.content')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'El usuario seleccionado no tiene acceso al módulo de contenido',
                    ], 403);
                }
                $targetUserId = $targetUser->id;
                $targetName = trim("{$targetUser->firstname} {$targetUser->lastname}");
            }

            $updated = AiContent::whereIn('uid', $uids)
                ->whereNull('assigned_to')
                ->update(['assigned_to' => $targetUserId, 'assigned_at' => $now]);

            $msg = $requestedUserId && $targetName !== 'ti'
                ? "Se asignaron {$updated} contenido(s) a {$targetName}"
                : "Se te asignaron {$updated} contenido(s) correctamente";

            return response()->json([
                'success' => true,
                'message' => $msg,
                'assigned' => $updated,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error in bulk assign: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al asignar el contenido',
            ], 500);
        }
    }

    /**
     * JSON-only list of users that can be assigned content (used by bulk modal in index).
     */
    public function getAssignableUsers(): JsonResponse
    {
        $users = User::query()
            ->where(function ($q) {
                $q->whereHas('permissions', fn ($p) => $p->where('name', 'suppliers.view.content'))
                    ->orWhereHas('roles', function ($r) {
                        $r->whereHas('permissions', fn ($p) => $p->where('name', 'suppliers.view.content'))
                            ->orWhere('name', 'super-admin');
                    });
            })
            ->orderBy('firstname')
            ->get(['id', 'firstname', 'lastname', 'email'])
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => trim("{$u->firstname} {$u->lastname}"),
                'email' => $u->email,
            ]);

        return response()->json($users);
    }

    /**
     * Reviewer management page — stats per user.
     */
    public function assignable(): View
    {
        $users = User::query()
            ->where(function ($q) {
                $q->whereHas('permissions', fn ($p) => $p->where('name', 'suppliers.view.content'))
                    ->orWhereHas('roles', function ($r) {
                        $r->whereHas('permissions', fn ($p) => $p->where('name', 'suppliers.view.content'))
                            ->orWhere('name', 'super-admin');
                    });
            })
            ->orderBy('firstname')
            ->get(['id', 'firstname', 'lastname', 'email']);

        $userIds = $users->pluck('id');

        $assignedCounts = AiContent::whereIn('assigned_to', $userIds)
            ->groupBy('assigned_to')
            ->selectRaw(
                'assigned_to,
                 count(*) as total,
                 sum(status = ?) as pending,
                 sum(status = ?) as approved,
                 sum(status = ?) as rejected,
                 sum(status = ?) as published',
                [
                    AiContent::STATUS_PENDING_VALIDATION,
                    AiContent::STATUS_VALIDATED,
                    AiContent::STATUS_REJECTED,
                    AiContent::STATUS_PUBLISHED,
                ]
            )
            ->get()
            ->keyBy('assigned_to');

        $users = $users->map(function ($u) use ($assignedCounts) {
            $counts = $assignedCounts->get($u->id);
            $u->assigned_count = $counts ? (int) $counts->total : 0;
            $u->pending_count = $counts ? (int) $counts->pending : 0;
            $u->approved_count = $counts ? (int) $counts->approved : 0;
            $u->rejected_count = $counts ? (int) $counts->rejected : 0;
            $u->published_count = $counts ? (int) $counts->published : 0;
            $u->full_name = trim("{$u->firstname} {$u->lastname}");

            return $u;
        });

        $unassignedCount = AiContent::whereNull('assigned_to')
            ->where('status', AiContent::STATUS_PENDING_VALIDATION)
            ->count();
        $totalContent = AiContent::count();

        $pageTitle = 'Revisores de contenido';
        $breadcrumb = 'Configuración / Proveedores / Contenido / Revisores';

        return view('supplier::settings.views.content.assignable', compact(
            'pageTitle', 'breadcrumb', 'users', 'unassignedCount', 'totalContent'
        ));
    }

    /**
     * Unassigned content list — select and assign to reviewers.
     */
    public function unassigned(Request $request): View
    {
        $contentQuery = AiContent::query()
            ->with(['supplier', 'supplierProduct.category'])
            ->whereNull('assigned_to')
            ->where('status', AiContent::STATUS_PENDING_VALIDATION);

        if ($search = $request->input('search')) {
            $contentQuery->where(function ($q) use ($search) {
                $q->where('generated_name', 'like', "%{$search}%")
                    ->orWhere('erp_reference', 'like', "%{$search}%");
            });
        }

        if ($supplierId = $request->input('supplier_id')) {
            $contentQuery->where('supplier_id', $supplierId);
        }

        $perPage = in_array((int) $request->input('per_page', 20), [10, 20, 50, 100])
            ? (int) $request->input('per_page', 20)
            : 20;

        $contents = $contentQuery->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();

        $unassignedCount = AiContent::whereNull('assigned_to')
            ->where('status', AiContent::STATUS_PENDING_VALIDATION)
            ->count();

        $suppliers = Supplier::orderBy('label')->get(['id', 'label']);

        // Users for the assign modal dropdown
        $users = User::query()
            ->where(function ($q) {
                $q->whereHas('permissions', fn ($p) => $p->where('name', 'suppliers.view.content'))
                    ->orWhereHas('roles', function ($r) {
                        $r->whereHas('permissions', fn ($p) => $p->where('name', 'suppliers.view.content'))
                            ->orWhere('name', 'super-admin');
                    });
            })
            ->orderBy('firstname')
            ->get(['id', 'firstname', 'lastname', 'email'])
            ->map(function ($u) {
                $u->full_name = trim("{$u->firstname} {$u->lastname}");

                return $u;
            });

        $pageTitle = 'Contenido sin asignar';
        $breadcrumb = 'Configuración / Proveedores / Contenido / Sin asignar';

        return view('supplier::settings.views.content.unassigned', compact(
            'pageTitle', 'breadcrumb', 'contents', 'unassignedCount', 'suppliers', 'users'
        ));
    }

    /**
     * Assign content to a specific user.
     * Accepts specific UIDs (ids[]) or auto-picks by quantity.
     */
    public function assignBatch(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'ids' => ['nullable', 'array', 'min:1', 'max:500'],
            'ids.*' => ['string'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        if (empty($request->input('ids')) && empty($request->input('quantity'))) {
            return response()->json([
                'success' => false,
                'message' => 'Debes indicar los contenidos (ids) o una cantidad (quantity)',
            ], 422);
        }

        $targetUser = User::findOrFail((int) $request->input('user_id'));

        if (! $targetUser->can('suppliers.view.content')) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario seleccionado no tiene acceso al módulo de contenido',
            ], 403);
        }

        if ($ids = $request->input('ids')) {
            $uids = collect($ids);
        } else {
            $uids = AiContent::whereNull('assigned_to')
                ->where('status', AiContent::STATUS_PENDING_VALIDATION)
                ->orderBy('created_at', 'desc')
                ->limit((int) $request->input('quantity'))
                ->pluck('uid');
        }

        if ($uids->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No hay contenido disponible sin asignar',
            ], 422);
        }

        $assigned = AiContent::whereIn('uid', $uids)
            ->whereNull('assigned_to')
            ->update(['assigned_to' => $targetUser->id, 'assigned_at' => now()]);

        $name = trim("{$targetUser->firstname} {$targetUser->lastname}");

        return response()->json([
            'success' => true,
            'message' => "Se asignaron {$assigned} contenido(s) a {$name}",
            'assigned' => $assigned,
        ]);
    }

    /**
     * Remove assignment from a single content item
     */
    public function unassign(string $uid): JsonResponse
    {
        try {
            $content = AiContent::where('uid', $uid)
                ->where('assigned_to', auth()->id())
                ->firstOrFail();

            $content->update(['assigned_to' => null, 'assigned_at' => null]);

            return response()->json([
                'success' => true,
                'message' => 'Asignación eliminada correctamente',
            ]);
        } catch (\Throwable $e) {
            Log::error('Error unassigning content: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la asignación',
            ], 500);
        }
    }

    /**
     * Polling endpoint: devuelve el estado actual de una lista de UIDs.
     * Usado por el frontend para saber cuándo terminaron los jobs de regeneración.
     */
    public function bulkStatus(Request $request): JsonResponse
    {
        $uids = $request->input('ids', []);
        if (empty($uids)) {
            return response()->json(['items' => []]);
        }

        $items = AiContent::whereIn('uid', $uids)
            ->with('contentStatus')
            ->get(['uid', 'status', 'prompt_id', 'error_message'])
            ->map(fn ($c) => [
                'uid' => $c->uid,
                'status' => $c->status,
                'status_label' => $c->contentStatus?->label ?? $c->status,
                'badge_class' => $c->contentStatus?->badge_class ?? 'bg-secondary',
                'done' => ! in_array($c->status, ['generating', 'pending_generation']),
                'success' => $c->status === 'pending_validation',
                'error' => str_starts_with($c->status, 'error_'),
                'error_msg' => $c->error_message ?: match ($c->status) {
                    'error_generation_failed' => 'Error al generar con la IA',
                    'error_insufficient_info' => 'Información insuficiente para generar',
                    'error_source_unavailable' => 'Fuente de datos no disponible',
                    default => null,
                },
            ]);

        $allDone = $items->every(fn ($i) => $i['done']);

        return response()->json([
            'items' => $items,
            'all_done' => $allDone,
        ]);
    }

    /**
     * Bulk action handler (approve, reject, regenerate, assign, unassign)
     */
    public function bulkAction(BulkActionContentRequest $request): JsonResponse
    {
        try {
            $action = $request->validated('action');
            $uids = $request->validated('ids');

            if ($action === 'assign') {
                return $this->bulkAssign($request);
            }

            if ($action === 'unassign') {
                $userId = (int) auth()->id();
                AiContent::whereIn('uid', $uids)->where('assigned_to', $userId)
                    ->update(['assigned_to' => null, 'assigned_at' => null]);

                return response()->json([
                    'success' => true,
                    'message' => 'Asignaciones eliminadas correctamente',
                ]);
            }

            $publicar = (int) $request->input('publicar', 0);

            $results = match ($action) {
                'approve' => $this->approveMany($uids, $publicar),
                'reject' => $this->rejectMany($uids, 'Bulk rejection'),
                'regenerate' => $this->regenerateMany($uids),
                'publish' => $this->publishMany($uids),
                'hold' => $this->holdMany($uids, $request->input('notes')),
                'restore' => $this->restoreMany($uids),
            };

            $successCount = collect($results)->where('success', true)->count();

            return response()->json([
                'success' => true,
                'message' => "Se procesaron {$successCount} de ".count($uids).' contenidos',
                'results' => $results,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error in bulk action: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al ejecutar la acción masiva',
            ], 500);
        }
    }

    /**
     * Edit content inline
     */
    public function edit(EditContentRequest $request, string $uid): JsonResponse
    {
        try {
            $content = AiContent::where('uid', $uid)->firstOrFail();
            $field = $request->validated('field');
            $value = $request->validated('value');

            $editableFields = ['generated_name', 'short_description', 'long_description', 'seo_title', 'seo_description', 'seo_keywords', 'notes'];
            if (in_array($field, $editableFields)) {
                $content->update([$field => $value]);

                return response()->json([
                    'success' => true,
                    'message' => 'Contenido actualizado correctamente',
                ]);
            }

            if ($field === 'brand') {
                $attrs = $content->source_attributes ?? [];
                $attrs['brand'] = $value;
                $content->update(['source_attributes' => $attrs]);

                return response()->json([
                    'success' => true,
                    'message' => 'Marca actualizada correctamente',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Campo no permitido para edición',
            ], 400);
        } catch (\Throwable $e) {
            Log::error('Error editing content: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al editar el contenido',
            ], 500);
        }
    }

    /**
     * Filter content by status
     */
    public function filterByStatus(Request $request, string $status): JsonResponse
    {
        try {
            $validStatuses = ['pending', 'approved', 'rejected', 'published'];

            if (! in_array($status, $validStatuses)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Estado inválido',
                ], 400);
            }

            $perPage = min(max($request->integer('per_page', 25), 1), 100);

            $contents = AiContent::byStatus($this->statusFromAlias($status))
                ->with(['supplier', 'prompt', 'validation', 'contentStatus'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return AiContentResource::collection($contents)
                ->additional(['success' => true])
                ->response();
        } catch (\Throwable $e) {
            Log::error('Error filtering content: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al filtrar el contenido',
            ], 500);
        }
    }

    /**
     * Translate content to one or more languages using DeepL
     */
    public function translate(TranslateContentRequest $request, string $uid): JsonResponse
    {
        try {
            $content = AiContent::where('uid', $uid)->firstOrFail();
            $languages = $request->validated('languages');

            foreach ($languages as $lang) {
                TranslateAiContentJob::dispatch($content->uid, $lang);
            }

            return response()->json([
                'success' => true,
                'message' => 'Traducción encolada para: '.implode(', ', $languages),
                'queued' => $languages,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error enqueueing translation: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al encolar la traducción',
            ], 500);
        }
    }

    /**
     * Panel data: catalog of ERP characteristics plus the assignments already
     * made at model and variant level for this content's product.
     */
    public function characteristicsPanel(string $uid): JsonResponse
    {
        $content = AiContent::where('uid', $uid)
            ->with('supplierProduct.attributes')
            ->firstOrFail();

        $attributeIds = $content->supplierProduct?->attributes->pluck('id') ?? collect();
        $productId = $content->supplierProduct?->id;

        return response()->json([
            'success' => true,
            // Solo características con al menos un valor activo en el catálogo
            // — una sin valores deja el select de "Valor" siempre vacío, un
            // callejón sin salida para quien la elige.
            'catalog' => ErpCharacteristic::active()
                ->whereHas('values', fn ($q) => $q->where('estado', true))
                ->orderBy('nombre')
                ->get(['id', 'nombre']),
            'model_assignments' => ModelCharacteristic::where('product_id', $productId)
                ->with('characteristic:id,nombre')
                ->get(),
            // Incluye tanto las asignaciones por variante (product_attribute_id)
            // como las del producto sin variantes tratado como su propio
            // artículo (product_attribute_id null, product_id = este producto).
            'variant_assignments' => VariantCharacteristic::where(function ($q) use ($attributeIds, $productId) {
                $q->whereIn('product_attribute_id', $attributeIds)
                    ->orWhere(function ($q2) use ($productId) {
                        $q2->whereNull('product_attribute_id')->where('product_id', $productId);
                    });
            })
                ->with(['characteristic:id,nombre', 'value:id,nombre'])
                ->get(),
            'attributes' => $content->supplierProduct?->attributes ?? [],
        ]);
    }

    /**
     * Valores del catálogo local para una característica (fuente remota del
     * select2 de valor por variante). Soporta `search` para el buscador del
     * select2 y pagina de a 50 (características con miles de valores, p.ej.
     * Color con ~3.900, no entraban en una sola página fija de 50 — el resto
     * quedaba inalcanzable sin escribir en el buscador. Select2 pide páginas
     * siguientes con `page`; devolvemos `pagination.more` para que sepa si
     * hay que seguir ofreciendo scroll infinito).
     */
    public function characteristicValues(Request $request, int $characteristicId): JsonResponse
    {
        $perPage = 50;
        $page = max(1, $request->integer('page', 1));
        $search = trim($request->string('search')->toString());

        $query = ErpCharacteristicValue::where('characteristic_id', $characteristicId)
            ->active()
            ->when($search !== '', function ($q) use ($search) {
                // Con miles de valores por coincidencia parcial (ej. "Verde" matchea
                // "Amarillo-Verde", "Verde Agua", "Azul-Verde"...), orden alfabético
                // puro mezcla todo — "Verde" exacto termina página adentro. Se prioriza
                // coincidencia exacta, luego "empieza con", y recién ahí el resto.
                $q->where('nombre', 'like', '%'.$search.'%')
                    ->orderByRaw(
                        'CASE WHEN nombre = ? THEN 0 WHEN nombre LIKE ? THEN 1 ELSE 2 END',
                        [$search, $search.'%']
                    );
            })
            ->orderBy('nombre');

        // Pide una fila de más para saber si hay siguiente página sin un COUNT(*) aparte.
        $rows = $query->skip(($page - 1) * $perPage)->take($perPage + 1)
            ->get(['id', 'erp_id', 'nombre', 'estado', 'last_sync_at']);

        $hasMore = $rows->count() > $perPage;

        return response()->json([
            'success' => true,
            'values' => $hasMore ? $rows->slice(0, $perPage)->values() : $rows,
            'pagination' => ['more' => $hasMore],
        ]);
    }

    /**
     * Assign one or more catalog characteristics to the model (product) level.
     */
    public function saveModelCharacteristics(Request $request, string $uid): JsonResponse
    {
        $content = AiContent::where('uid', $uid)->with('supplierProduct')->firstOrFail();

        if (! $content->supplierProduct) {
            return response()->json([
                'success' => false,
                'message' => 'Este contenido no tiene un producto ERP vinculado',
            ], 422);
        }

        $validated = $request->validate([
            'characteristic_ids' => ['required', 'array', 'min:1'],
            'characteristic_ids.*' => ['integer', 'exists:supplier_erp_characteristics,id'],
        ]);

        // Mismo idmodelo que usa syncToErp()/SyncContentToErpJob para este contenido.
        $erpModelId = $content->supplierProduct->erp_id
            ?? ($content->source_attributes['id'] ?? null)
            ?? $content->erp_reference;

        if (! $erpModelId) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo determinar el ID de modelo ERP de este contenido',
            ], 422);
        }

        foreach ($validated['characteristic_ids'] as $characteristicId) {
            ModelCharacteristic::firstOrCreate(
                [
                    'product_id' => $content->supplierProduct->id,
                    'characteristic_id' => $characteristicId,
                ],
                [
                    'erp_model_id' => $erpModelId,
                    'sync_status' => 'pending',
                    'created_by' => auth()->id(),
                ]
            );
        }

        return response()->json(['success' => true]);
    }

    /**
     * Assign a characteristic value to a specific variant (product attribute),
     * o al producto directamente cuando no tiene variantes (se trata como su
     * propio artículo: erp_article_id = erp_id del producto). Re-queues for
     * sync (pending) when the value changes on an existing assignment.
     */
    public function saveVariantCharacteristic(Request $request, string $uid): JsonResponse
    {
        $content = AiContent::where('uid', $uid)->with('supplierProduct')->firstOrFail();

        if (! $content->supplierProduct) {
            return response()->json([
                'success' => false,
                'message' => 'Este contenido no tiene un producto ERP vinculado',
            ], 422);
        }

        $validated = $request->validate([
            'product_attribute_id' => ['nullable', 'integer', 'exists:supplier_product_attributes,id'],
            'characteristic_id' => ['required', 'integer', 'exists:supplier_erp_characteristics,id'],
            'value_id' => ['required', 'integer', 'exists:supplier_erp_characteristic_values,id'],
        ]);

        // Mismo idmodelo que usa syncToErp()/SyncContentToErpJob para este contenido.
        $erpModelId = $content->supplierProduct->erp_id
            ?? ($content->source_attributes['id'] ?? null)
            ?? $content->erp_reference;

        if ($validated['product_attribute_id'] ?? null) {
            $attribute = ProductAttribute::findOrFail($validated['product_attribute_id']);

            VariantCharacteristic::updateOrCreate(
                [
                    'product_attribute_id' => $validated['product_attribute_id'],
                    'characteristic_id' => $validated['characteristic_id'],
                ],
                [
                    'product_id' => null,
                    'erp_article_id' => $attribute->erp_id,
                    'erp_model_id' => $erpModelId,
                    'value_id' => $validated['value_id'],
                    'sync_status' => 'pending',
                    'created_by' => auth()->id(),
                ]
            );
        } else {
            // Sin variantes: el propio producto hace de "artículo" en ERP.
            VariantCharacteristic::updateOrCreate(
                [
                    'product_attribute_id' => null,
                    'product_id' => $content->supplierProduct->id,
                    'characteristic_id' => $validated['characteristic_id'],
                ],
                [
                    'erp_article_id' => $erpModelId,
                    'erp_model_id' => $erpModelId,
                    'value_id' => $validated['value_id'],
                    'sync_status' => 'pending',
                    'created_by' => auth()->id(),
                ]
            );
        }

        return response()->json(['success' => true]);
    }

    /**
     * Quita una característica de modelo todavía no enviada al ERP.
     */
    public function destroyModelCharacteristic(string $uid, int $modelCharacteristicId): JsonResponse
    {
        $content = AiContent::where('uid', $uid)->with('supplierProduct')->firstOrFail();

        $item = ModelCharacteristic::where('id', $modelCharacteristicId)
            ->where('product_id', $content->supplierProduct?->id)
            ->firstOrFail();

        if ($item->sync_status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede quitar: ya se envió al ERP',
            ], 422);
        }

        $item->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Quita un valor de variante todavía no enviado al ERP.
     */
    public function destroyVariantCharacteristic(string $uid, int $variantCharacteristicId): JsonResponse
    {
        $content = AiContent::where('uid', $uid)->with('supplierProduct.attributes')->firstOrFail();

        $attributeIds = $content->supplierProduct?->attributes->pluck('id') ?? collect();
        $productId = $content->supplierProduct?->id;

        $item = VariantCharacteristic::where('id', $variantCharacteristicId)
            ->where(function ($q) use ($attributeIds, $productId) {
                $q->whereIn('product_attribute_id', $attributeIds)
                    ->orWhere(function ($q2) use ($productId) {
                        $q2->whereNull('product_attribute_id')->where('product_id', $productId);
                    });
            })
            ->firstOrFail();

        if ($item->sync_status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede quitar: ya se envió al ERP',
            ], 422);
        }

        $item->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Envío manual y explícito de las características pendientes al ERP.
     * Nunca se dispara solo: guardar una característica siempre la deja en
     * "pending" — hace falta este botón (o aprobar el contenido) para
     * enviarla de verdad.
     */
    public function syncCharacteristicsNow(string $uid): JsonResponse
    {
        $content = AiContent::where('uid', $uid)->firstOrFail();

        SyncCharacteristicsToErpJob::dispatch($content->uid);

        return response()->json([
            'success' => true,
            'message' => 'Envío al ERP iniciado en segundo plano',
        ]);
    }

    /**
     * Perform action on single content (approve, reject, regenerate, etc.)
     */
    public function action(ActionContentRequest $request, string $uid): JsonResponse
    {
        $content = AiContent::where('uid', $uid)->firstOrFail();
        $action = $request->validated('action');

        // "Regenerar volviendo a coger los datos de Gestión": antes de regenerar,
        // refresca el snapshot ERP (source_attributes) del que se leen las variables
        // del prompt. Si falla, se aborta sin tocar el contenido actual.
        if ($action === 'regenerate' && $request->boolean('full_update')) {
            $refresh = $this->refreshSourceAttributesFromErp($content);
            if (! $refresh['success']) {
                return response()->json(['success' => false, 'message' => $refresh['message']], 400);
            }
            $content->refresh();
        }

        if ($action === 'publish_erp') {
            if (! in_array($content->status, [AiContent::STATUS_VALIDATED, AiContent::STATUS_PUBLISHED_HIDDEN])) {
                return response()->json(['success' => false, 'message' => 'Solo se puede publicar contenido aprobado'], 400);
            }

            $publicar = (int) ($request->input('publicar', 1));
            $nombreOverride = $request->filled('nombre') ? trim($request->input('nombre')) : null;
            $marcaOverride = $request->has('marca') ? trim($request->input('marca') ?? '') : null;
            $erpResult = $this->syncToErp($content, $publicar, $nombreOverride, $marcaOverride);

            if ($publicar === 1) {
                $content->publish();
            } else {
                $content->publishHidden();
            }

            return response()->json([
                'success' => $erpResult['success'],
                'message' => $erpResult['success'] ? 'Publicado y sincronizado con ERP' : ($erpResult['message'] ?? 'Error al sincronizar con ERP'),
                'content' => new AiContentResource($content->refresh()),
                'erp_result' => $erpResult,
            ]);
        }

        try {
            match ($action) {
                // Si el modal envía "publicar", el ERP se sincroniza más abajo con syncToErp()
                // usando ese valor → evitar el job redundante (publicar=0) que lo sobrescribiría.
                'approve' => $this->contentService->approveContent($content, (int) auth()->id(), syncToErp: ! $request->has('publicar')),
                'reject' => $this->contentService->rejectContent(
                    $content,
                    (int) auth()->id(),
                    $request->input('reason') ?: 'Manual rejection'
                ),
                'regenerate' => $content = $this->contentService->regenerateContent(
                    $content,
                    $this->resolvePrompt($request->input('prompt_id')),
                    (int) auth()->id()
                ),
            };
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            Log::error('Error performing action: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Error al ejecutar la acción'], 500);
        }

        $erpResult = null;

        if ($action === 'approve' && $request->has('publicar')) {
            $publicar = (int) $request->input('publicar', 0);
            $nombreOverride = $request->filled('nombre') ? trim($request->input('nombre')) : null;
            $marcaOverride = $request->has('marca') ? trim($request->input('marca') ?? '') : null;
            $erpResult = $this->syncToErp($content, $publicar, $nombreOverride, $marcaOverride);

            if ($publicar === 1) {
                $content->publish();
            } else {
                $content->publishHidden();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Acción completada',
            'content' => new AiContentResource($content->refresh()),
            'erp_result' => $erpResult,
        ]);
    }

    /**
     * Llama al endpoint ERP para sincronizar descripción y estado de publicación.
     *
     * @return array{success: bool, status?: int, message?: string}
     */
    private function syncToErp(AiContent $content, int $publicar, ?string $nombreOverride = null, ?string $marcaOverride = null): array
    {
        $erpUrl = Setting::get('supplier.erp_modelo_url', 'http://interges:8080/api-gestion/modelo/');
        $erpId = $content->supplierProduct?->erp_id
            ?? ($content->source_attributes['id'] ?? null)
            ?? $content->erp_reference;

        if (! $erpId || ! $erpUrl) {
            return ['success' => false, 'message' => 'ERP URL o ID de modelo no configurado'];
        }

        $nombre = $nombreOverride ?? ($content->supplierProduct?->name ?? $content->generated_name ?? '');

        // null = no override (usar fallback del producto)
        // ''   = enviado vacío desde el modal → omitir del payload ERP
        if ($marcaOverride === null) {
            $marca = $content->supplierProduct?->metadata['erp_marca']
                ?? $content->source_attributes['marca']
                ?? $content->source_attributes['idmarca']
                ?? $content->source_attributes['brand']
                ?? null;

            // Fallback: productos ya sincronizados no tienen la marca en source_attributes
            // (el filter no la devolvía). Consultarla directamente del modelo en Oracle.
            if ($marca === null && $erpId) {
                try {
                    $marca = DB::connection('oracle')
                        ->table('modelo')
                        ->where('idmodelo', $erpId)
                        ->value('idmarca');
                } catch (\Throwable $e) {
                    Log::warning('No se pudo obtener idmarca desde Oracle', ['erp_id' => $erpId, 'error' => $e->getMessage()]);
                }
            }
        } else {
            $marca = $marcaOverride !== '' ? $marcaOverride : null;
        }

        $payload = [
            'idmodelo' => (string) $erpId,
            'nombre' => $nombre,
            'descripcion' => $content->long_description ?? '',
            'publicar' => $publicar,
        ];
        if ($marca !== null) {
            $payload['marca'] = (string) $marca;
        }

        try {
            // El servidor de escritura del ERP usa name-based virtual hosting: sin forzar
            // el header Host exacto (sin puerto), Apache enruta a un vhost por defecto que
            // responde 200/404 falsos según la ruta en vez del vhost real de api-gestion.
            $response = Http::timeout(10)
                ->withHeaders(['Host' => parse_url($erpUrl, PHP_URL_HOST)])
                ->asForm()
                ->post($erpUrl, $payload);

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'message' => $response->successful() ? null : 'ERP respondió con error '.$response->status(),
            ];
        } catch (\Throwable $e) {
            Log::error('ERP sync failed from action', [
                'content_uid' => $content->uid,
                'erp_id' => $erpId,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Vuelve a traer del ERP (Gestión) los datos del modelo vinculado a este contenido
     * y refresca tanto el producto/artículos locales como el snapshot `source_attributes`
     * del propio contenido — de ahí lee `regenerateContent()` las variables del prompt.
     * Usado por la opción "actualización completa" del botón Regenerar.
     *
     * @return array{success: bool, message?: string}
     */
    private function refreshSourceAttributesFromErp(AiContent $content): array
    {
        $erpId = $content->supplierProduct?->erp_id
            ?? ($content->source_attributes['id'] ?? null)
            ?? $content->erp_reference;

        if (! $erpId) {
            return ['success' => false, 'message' => 'No se pudo determinar el ID de modelo ERP de este contenido'];
        }

        $stats = $this->erpModelSyncService->syncAllModelsFromFilter(
            force: true,
            skipAi: true,
            erpModelId: (int) $erpId,
        );

        if (! $stats['success']) {
            return [
                'success' => false,
                'message' => 'Error al traer los datos desde el ERP: '.(implode('; ', $stats['errors']) ?: 'error desconocido'),
            ];
        }

        return ['success' => true];
    }

    /**
     * Approve a batch of content records identified by UID.
     *
     * @param  array<int, string>  $uids
     * @return array<int, array{uid: string, success: bool, message?: string}>
     */
    private function approveMany(array $uids, int $publicar = 0): array
    {
        $contents = AiContent::whereIn('uid', $uids)->get()->keyBy('uid');
        $results = [];

        foreach ($uids as $uid) {
            $content = $contents->get($uid);

            if (! $content) {
                $results[] = ['uid' => $uid, 'success' => false, 'message' => 'No encontrado'];

                continue;
            }

            try {
                // syncToErp se llama explícitamente abajo con $publicar → no disparar el job (publicar=0).
                $this->contentService->approveContent($content, (int) auth()->id(), syncToErp: false);

                $erpResult = $this->syncToErp($content->refresh(), $publicar);

                if ($erpResult['success']) {
                    $publicar === 1 ? $content->publish() : $content->publishHidden();
                }

                $results[] = ['uid' => $uid, 'success' => true, 'erp' => $erpResult];
            } catch (\Throwable $e) {
                $results[] = ['uid' => $uid, 'success' => false, 'message' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * @param  array<int, string>  $uids
     * @return array<int, array{uid: string, success: bool, message?: string}>
     */
    private function publishMany(array $uids): array
    {
        $contents = AiContent::whereIn('uid', $uids)->get()->keyBy('uid');
        $results = [];

        foreach ($uids as $uid) {
            $content = $contents->get($uid);

            if (! $content) {
                $results[] = ['uid' => $uid, 'success' => false, 'message' => 'No encontrado'];

                continue;
            }

            if ($content->status !== AiContent::STATUS_VALIDATED) {
                $results[] = ['uid' => $uid, 'success' => false, 'message' => 'No está en estado validado'];

                continue;
            }

            try {
                $content->publish();

                $erpResult = $this->syncToErp($content->refresh(), 1);

                $results[] = ['uid' => $uid, 'success' => true, 'erp' => $erpResult];
            } catch (\Throwable $e) {
                $results[] = ['uid' => $uid, 'success' => false, 'message' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Reject a batch of content records identified by UID.
     *
     * @param  array<int, string>  $uids
     * @return array<int, array{uid: string, success: bool, message?: string}>
     */
    private function rejectMany(array $uids, string $reason): array
    {
        $contents = AiContent::whereIn('uid', $uids)->get()->keyBy('uid');
        $results = [];

        foreach ($uids as $uid) {
            $content = $contents->get($uid);

            if (! $content) {
                $results[] = ['uid' => $uid, 'success' => false, 'message' => 'No encontrado'];

                continue;
            }

            try {
                $this->contentService->rejectContent($content, (int) auth()->id(), $reason);
                $results[] = ['uid' => $uid, 'success' => true];
            } catch (\Throwable $e) {
                $results[] = ['uid' => $uid, 'success' => false, 'message' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Park a batch of content records in the "Sin contenido" holding area.
     * Not allowed for content already sent to the store (published/published_hidden)
     * — that requires unpublishing from the ERP first, out of scope here.
     *
     * @param  array<int, string>  $uids
     * @return array<int, array{uid: string, success: bool, message?: string}>
     */
    private function holdMany(array $uids, ?string $notes = null): array
    {
        $contents = AiContent::whereIn('uid', $uids)->get()->keyBy('uid');
        $results = [];
        $userId = (int) auth()->id();

        foreach ($uids as $uid) {
            $content = $contents->get($uid);

            if (! $content) {
                $results[] = ['uid' => $uid, 'success' => false, 'message' => 'No encontrado'];

                continue;
            }

            if (in_array($content->status, [AiContent::STATUS_PUBLISHED, AiContent::STATUS_PUBLISHED_HIDDEN])) {
                $results[] = ['uid' => $uid, 'success' => false, 'message' => 'Ya está publicado en el ERP, no se puede marcar sin contenido'];

                continue;
            }

            try {
                $content->holdWithNote($notes, $userId);
                $results[] = ['uid' => $uid, 'success' => true];
            } catch (\Throwable $e) {
                $results[] = ['uid' => $uid, 'success' => false, 'message' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Take a batch of content records out of "Sin contenido" back to Por validar.
     *
     * @param  array<int, string>  $uids
     * @return array<int, array{uid: string, success: bool, message?: string}>
     */
    private function restoreMany(array $uids): array
    {
        $contents = AiContent::whereIn('uid', $uids)->get()->keyBy('uid');
        $results = [];
        $userId = (int) auth()->id();

        foreach ($uids as $uid) {
            $content = $contents->get($uid);

            if (! $content) {
                $results[] = ['uid' => $uid, 'success' => false, 'message' => 'No encontrado'];

                continue;
            }

            try {
                $content->restoreFromHold($userId);
                $results[] = ['uid' => $uid, 'success' => true];
            } catch (\Throwable $e) {
                $results[] = ['uid' => $uid, 'success' => false, 'message' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Dispatch async regeneration jobs for a batch of content UIDs.
     * Returns a result-per-UID array indicating whether the item was found and queued.
     *
     * @param  array<int, string>  $uids
     * @return array<int, array{uid: string, success: bool, message?: string}>
     */
    private function regenerateMany(array $uids, ?int $requestedByUserId = null): array
    {
        $userId = $requestedByUserId ?? (int) auth()->id();

        // Estados que permiten regeneración
        $regenerableStatuses = [
            AiContent::STATUS_PENDING_GENERATION,
            AiContent::STATUS_PENDING_VALIDATION,
            AiContent::STATUS_IN_REVIEW,
            AiContent::STATUS_NEEDS_REVISION,
            AiContent::STATUS_REJECTED,
            AiContent::STATUS_ERROR_INSUFFICIENT_INFO,
            AiContent::STATUS_ERROR_SOURCE_UNAVAILABLE,
            AiContent::STATUS_ERROR_GENERATION_FAILED,
        ];

        $contents = AiContent::whereIn('uid', $uids)
            ->with(['supplierProduct', 'prompt'])
            ->get()
            ->keyBy('uid');

        $results = [];

        foreach ($uids as $uid) {
            if (! $contents->has($uid)) {
                $results[] = ['uid' => $uid, 'success' => false, 'message' => 'No encontrado'];

                continue;
            }

            $content = $contents[$uid];

            // Bloquear estados no regenerables
            if (! in_array($content->status, $regenerableStatuses)) {
                $results[] = [
                    'uid' => $uid,
                    'success' => false,
                    'message' => "Estado '{$content->status}' no permite regeneración",
                ];

                continue;
            }

            // Para pending_generation sin prompt: intentar resolver por subfamilia
            if (! $content->prompt_id) {
                $subfamilyId = $content->supplierProduct?->subfamily_id;
                $prompt = $this->promptSelectionService->selectPrompt(
                    supplierId: $content->supplier_id,
                    subfamilyId: $subfamilyId,
                );

                if ($prompt) {
                    $content->update(['prompt_id' => $prompt->id]);
                } else {
                    $results[] = [
                        'uid' => $uid,
                        'success' => false,
                        'message' => 'Sin prompt asignado — configura un prompt para esta subfamilia',
                    ];

                    continue;
                }
            }

            RegenerateAiContentJob::dispatch($uid, $userId);
            $results[] = ['uid' => $uid, 'success' => true];
        }

        return $results;
    }

    /**
     * Resolve a Prompt model from a UID/ID, or null when none provided.
     */
    private function resolvePrompt(mixed $promptId): ?Prompt
    {
        if (empty($promptId)) {
            return null;
        }

        return Prompt::where('uid', $promptId)->orWhere('id', $promptId)->first();
    }

    /**
     * Export filtered contents as a streaming CSV (max 5000 rows).
     */
    public function export(Request $request): HttpResponse
    {
        $tab = $request->input('tab', 'available');
        $userId = (int) auth()->id();

        $query = AiContent::query()
            ->with(['supplier', 'prompt', 'contentStatus'])
            ->select([
                'uid',
                'erp_reference',
                'generated_name',
                'short_description',
                'seo_title',
                'seo_keywords',
                'status',
                'supplier_id',
                'created_at',
                'sources_used',
                'generation_metadata',
            ]);

        if ($tab === 'mine') {
            $query->where('assigned_to', $userId);
        } else {
            $query->whereNull('assigned_to');
        }

        $query->excludeOnHoldByDefault($request->filled('status'));

        $this->filters->apply($query, $request);

        $query->orderBy('created_at', 'desc')->limit(5000);

        $filename = 'contenido_ia_'.now()->format('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-store, no-cache',
            'Pragma' => 'no-cache',
        ];

        $csvHeaders = [
            'uid',
            'erp_reference',
            'nombre_generado',
            'descripcion_corta',
            'seo_title',
            'seo_keywords',
            'estado',
            'proveedor',
            'fecha_generacion',
            'fuentes_web',
            'web_search_query',
        ];

        $stream = fopen('php://temp', 'r+');

        // UTF-8 BOM so Excel opens it correctly
        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, $csvHeaders);

        $query->chunk(500, function ($rows) use ($stream) {
            foreach ($rows as $row) {
                $sources = $row->sources_used ?? [];
                $urls = collect($sources)->map(function ($src) {
                    if (is_string($src)) {
                        return $src;
                    }

                    return $src['url'] ?? $src['source'] ?? '';
                })->filter()->implode('|');

                $webSearchQuery = $row->generation_metadata['web_search_query']
                    ?? $row->generation_metadata['search_query']
                    ?? '';

                fputcsv($stream, [
                    $row->uid,
                    $row->erp_reference ?? '',
                    $row->generated_name ?? '',
                    $row->short_description ?? '',
                    $row->seo_title ?? '',
                    $row->seo_keywords ?? '',
                    $row->contentStatus?->label ?? $row->status,
                    $row->supplier?->label ?? '',
                    $row->created_at?->format('d/m/Y H:i') ?? '',
                    $urls,
                    $webSearchQuery,
                ]);
            }
        });

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return response($csv, 200, $headers);
    }

    /**
     * Coverage report: products without any AI content, grouped by supplier.
     */
    public function coverageReport(Request $request): JsonResponse|View
    {
        $total = Product::doesntHave('aiContent')->count();

        $bySupplier = Product::doesntHave('aiContent')
            ->select('supplier_id', DB::raw('COUNT(*) as count'))
            ->groupBy('supplier_id')
            ->with('supplier:id,label')
            ->get()
            ->map(fn ($row) => [
                'supplier_id' => $row->supplier_id,
                'supplier_label' => $row->supplier?->label ?? "Proveedor #{$row->supplier_id}",
                'count' => (int) $row->count,
            ])
            ->sortByDesc('count')
            ->values();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'total' => $total,
                'by_supplier' => $bySupplier,
            ]);
        }

        $pageTitle = 'Cobertura de contenido';
        $breadcrumb = 'Configuración / Proveedores / Contenido / Cobertura';

        return view('supplier::settings.views.content.coverage', compact(
            'pageTitle', 'breadcrumb', 'total', 'bySupplier'
        ));
    }

    /**
     * Aggregated content counts for the review dashboard, cached for 2 minutes.
     *
     * @return array{total: int, pending: int, approved: int, rejected: int}
     */
    private function statusCounts(): array
    {
        return Cache::remember(ContentGenerationService::STATS_CACHE_KEY, 120, function (): array {
            $row = AiContent::query()->selectRaw(
                'COUNT(*) as total, '
                .'SUM(status = ?) as pending, '
                .'SUM(status = ?) as validated, '
                .'SUM(status = ?) as published, '
                .'SUM(status = ?) as published_hidden, '
                .'SUM(status = ?) as rejected, '
                .'SUM(status = ?) as on_hold',
                [
                    AiContent::STATUS_PENDING_VALIDATION,
                    AiContent::STATUS_VALIDATED,
                    AiContent::STATUS_PUBLISHED,
                    AiContent::STATUS_PUBLISHED_HIDDEN,
                    AiContent::STATUS_REJECTED,
                    AiContent::STATUS_ON_HOLD,
                ]
            )->first();

            $validated = (int) $row->validated;
            $published = (int) $row->published;
            $published_hidden = (int) $row->published_hidden;

            return [
                'total' => (int) $row->total,
                'pending' => (int) $row->pending,
                'validated' => $validated,
                'validated_total' => $validated + $published + $published_hidden,
                'published' => $published,
                'published_hidden' => $published_hidden,
                'rejected' => (int) $row->rejected,
                'on_hold' => (int) $row->on_hold,
            ];
        });
    }

    /**
     * Map a public status alias to the canonical AiContent status value.
     */
    private function statusFromAlias(string $alias): string
    {
        return match ($alias) {
            'pending' => AiContent::STATUS_PENDING_VALIDATION,
            'approved' => AiContent::STATUS_VALIDATED,
            'rejected' => AiContent::STATUS_REJECTED,
            'published' => AiContent::STATUS_PUBLISHED,
            default => $alias,
        };
    }
}
