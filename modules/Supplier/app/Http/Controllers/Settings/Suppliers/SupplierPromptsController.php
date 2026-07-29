<?php

namespace Modules\Supplier\Http\Controllers\Settings\Suppliers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Supplier\Http\Requests\Prompt\BulkActionPromptRequest;
use Modules\Supplier\Http\Requests\Prompt\StorePromptRequest;
use Modules\Supplier\Http\Requests\Prompt\UpdatePromptRequest;
use Modules\Supplier\Models\Category\Category;
use Modules\Supplier\Models\Category\Sport;
use Modules\Supplier\Models\Category\Subfamily;
use Modules\Supplier\Models\Product\Product;
use Modules\Supplier\Models\Ai\AiContent;
use Modules\Supplier\Models\Prompt\Prompt;
use Modules\Supplier\Models\Prompt\PromptVersion;
use Modules\Supplier\Models\Supplier\Supplier;
use Modules\Supplier\Services\ContentGenerationService;
use Modules\Supplier\Services\ProductChatService;
use Modules\Supplier\Traits\HasFlashMessages;

class SupplierPromptsController extends Controller
{
    use HasFlashMessages;

    public function __construct(protected ContentGenerationService $contentService) {}

    /**
     * Display AI prompts list
     */
    public function index(Request $request): View
    {
        $pageTitle = 'Gestión de Prompts de IA';
        $breadcrumb = 'Configuración / Proveedores / Prompts';

        $query = Prompt::query()->with(['supplier']);

        if ($search = $request->input('search')) {
            $query->where('label', 'like', "%{$search}%");
        }

        if (in_array($request->input('status'), ['0', '1'], true)) {
            $query->where('is_active', $request->input('status') === '1');
        }

        if ($scope = $request->input('scope')) {
            $query->where('scope', $scope);
        }

        $filterSupplier = null;
        $filterCategory = null;

        if ($supplierId = $request->input('supplier_id')) {
            $query->where('supplier_id', $supplierId);
            $filterSupplier = Supplier::find($supplierId);
        }

        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
            $filterCategory = Category::find($categoryId);
        }

        $perPage = (int) $request->input('per_page', 15);
        $perPage = in_array($perPage, [10, 20, 50, 100, 200]) ? $perPage : 15;

        $prompts = $query->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        // Stats per prompt: one query grouped by prompt_id
        $promptIds = $prompts->pluck('id')->all();

        $statsRaw = AiContent::selectRaw('
                prompt_id,
                COUNT(*) as total_generated,
                SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as total_validated
            ', [AiContent::STATUS_VALIDATED, AiContent::STATUS_PUBLISHED])
            ->whereIn('prompt_id', $promptIds)
            ->groupBy('prompt_id')
            ->get()
            ->keyBy('prompt_id');

        $promptStats = [];
        foreach ($promptIds as $id) {
            $row = $statsRaw[$id] ?? null;
            $generated = $row ? (int) $row->total_generated : 0;
            $validated = $row ? (int) $row->total_validated : 0;
            $rate = $generated > 0 ? round($validated / $generated * 100) : null;
            $promptStats[$id] = [
                'total_generated' => $generated,
                'total_validated' => $validated,
                'approval_rate'   => $rate,
            ];
        }

        return view('supplier::settings.views.prompts.index', compact(
            'pageTitle', 'breadcrumb', 'prompts', 'filterSupplier', 'filterCategory', 'promptStats'
        ));
    }

    /**
     * Show create prompt form
     */
    public function create(Request $request): View
    {
        $pageTitle = 'Crear Prompt de IA';
        $breadcrumb = 'Configuración / Proveedores / Prompts / Crear';

        $suppliers     = Supplier::orderBy('label')->get();
        $sports        = Sport::active()->orderBy('name')->get();
        $erpcategorias = Category::whereNotNull('erp_categoria_id')
                            ->select('erp_categoria_id', 'erp_categoria_name', 'sport_id')
                            ->groupBy('erp_categoria_id', 'erp_categoria_name', 'sport_id')
                            ->orderBy('erp_categoria_name')
                            ->get();
        $categories    = Category::active()->orderBy('name')->get();
        $subfamilies   = Subfamily::active()->orderBy('name')->get();

        $categoryId = $request->input('category_id');
        $supplierId = $request->input('supplier_id');

        $defaultScope = $request->input('scope');
        if (! $defaultScope) {
            if ($categoryId && $supplierId) {
                $defaultScope = 'supplier_category';
            } elseif ($categoryId) {
                $defaultScope = 'category';
            } elseif ($supplierId) {
                $defaultScope = 'supplier';
            } else {
                $defaultScope = 'global';
            }
        }

        $defaults = [
            'scope' => $defaultScope,
            'supplier_id' => $supplierId,
            'category_id' => $categoryId,
        ];

        $templates = Prompt::templates()
            ->orderBy('template_category')
            ->orderBy('label')
            ->get(['uid', 'label', 'template_category', 'content_type', 'prompt_template', 'output_language', 'tone', 'priority', 'seo_focus']);

        return view('supplier::settings.views.prompts.create', compact('pageTitle', 'breadcrumb', 'suppliers', 'sports', 'erpcategorias', 'categories', 'subfamilies', 'defaults', 'templates'));
    }

    /**
     * Store new prompt
     */
    public function checkSubfamilyConflicts(Request $request): JsonResponse
    {
        $subfamilyIds = array_filter(array_map('intval', (array) $request->input('subfamily_ids', [])));
        $excludeUid   = $request->input('exclude_uid'); // UID del prompt actual (edit)

        if (empty($subfamilyIds)) {
            return response()->json(['conflicts' => []]);
        }

        $query = Prompt::query()->where(function ($q) use ($subfamilyIds) {
            // Subfamilia en campo singular
            $q->whereIn('subfamily_id', $subfamilyIds)
              // O en el array JSON
              ->orWhere(function ($q2) use ($subfamilyIds) {
                  foreach ($subfamilyIds as $id) {
                      $q2->orWhereJsonContains('subfamily_ids', $id)
                         ->orWhereJsonContains('subfamily_ids', (string) $id);
                  }
              });
        });

        if ($excludeUid) {
            $query->where('uid', '!=', $excludeUid);
        }

        $conflicting = $query->with('subfamily')->get(['id', 'uid', 'label', 'subfamily_id', 'subfamily_ids']);

        $conflicts = [];
        foreach ($conflicting as $prompt) {
            $assignedIds = array_filter(array_unique(array_merge(
                $prompt->subfamily_ids ?? [],
                $prompt->subfamily_id ? [$prompt->subfamily_id] : [],
            )));

            $conflictedIds = array_values(array_intersect(array_map('intval', $assignedIds), $subfamilyIds));

            if (empty($conflictedIds)) {
                continue;
            }

            $subfamilyNames = Subfamily::whereIn('id', $conflictedIds)->pluck('name', 'id');

            foreach ($conflictedIds as $sfId) {
                $conflicts[] = [
                    'subfamily_id'   => $sfId,
                    'subfamily_name' => $subfamilyNames[$sfId] ?? "Subfamilia #{$sfId}",
                    'prompt_uid'     => $prompt->uid,
                    'prompt_label'   => $prompt->label,
                ];
            }
        }

        return response()->json(['conflicts' => $conflicts]);
    }

    public function store(StorePromptRequest $request)
    {
        $validated = $request->validated();
        $subfamilyIds = $validated['subfamily_ids'] ?? null;
        $subfamilyMode = $request->input('subfamily_mode', 'single');

        if ($subfamilyIds && ! $request->boolean('force_reassign')) {
            $conflicts = $this->findSubfamilyConflicts(array_map('intval', $subfamilyIds));
            if (! empty($conflicts)) {
                $names = implode(', ', array_column($conflicts, 'subfamily_name'));
                return back()->withErrors(['subfamily_ids' => "Las subfamilias ya están asignadas a otro prompt: {$names}"])->withInput();
            }
        }

        if ($request->boolean('force_reassign') && $subfamilyIds) {
            $this->releaseSubfamiliesFromOtherPrompts($subfamilyIds);
        }

        $subfamilyIds = $subfamilyIds ? array_map('intval', $subfamilyIds) : null;

        Prompt::create([
            'supplier_id'    => $validated['supplier_id'] ?? null,
            'category_id'    => $validated['category_id'] ?? null,
            'subfamily_id'   => $subfamilyIds ? $subfamilyIds[0] : null,
            'subfamily_ids'  => $subfamilyIds ?: null,
            'subfamily_mode' => $subfamilyMode,
            'label' => $validated['label'],
            'scope' => $validated['scope'],
            'content_type' => $validated['content_type'],
            'prompt_template' => $validated['prompt_template'],
            'output_language' => $validated['output_language'],
            'tone' => $validated['tone'],
            'priority' => $validated['priority'] ?? 0,
            'seo_focus' => $validated['seo_focus'],
            'enable_web_search' => $validated['enable_web_search'],
                'ai_model'          => $validated['ai_model'] ?? null,
            'is_active' => $validated['is_active'],
            'version' => 1,
        ]);

        return $this->flashSuccess('Prompt creado exitosamente', 'settings.suppliers.prompts.index');
    }

    /**
     * Show prompt details
     */
    public function show(string $uid): JsonResponse
    {
        try {
            $prompt = Prompt::where('uid', $uid)
                ->with(['supplier'])
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $prompt,
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting prompt details: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los detalles del prompt',
            ], 500);
        }
    }

    /**
     * Show edit prompt form
     */
    public function edit(string $uid): View
    {
        $prompt = Prompt::where('uid', $uid)->with(['supplier', 'subfamily'])->firstOrFail();
        $pageTitle = "Editar Prompt: {$prompt->label}";
        $breadcrumb = 'Configuración / Proveedores / Prompts / Editar';

        $suppliers     = Supplier::orderBy('label')->get();
        $sports        = Sport::active()->orderBy('name')->get();
        $erpcategorias = Category::whereNotNull('erp_categoria_id')
                            ->select('erp_categoria_id', 'erp_categoria_name', 'sport_id')
                            ->groupBy('erp_categoria_id', 'erp_categoria_name', 'sport_id')
                            ->orderBy('erp_categoria_name')
                            ->get();
        $categories    = Category::active()->orderBy('name')->get();
        $subfamilies   = Subfamily::active()->orderBy('name')->get();

        $templates = Prompt::templates()
            ->orderBy('template_category')
            ->orderBy('label')
            ->get(['uid', 'label', 'template_category', 'content_type', 'prompt_template', 'output_language', 'tone', 'priority', 'seo_focus']);

        $versions = $prompt->versions()->with('savedBy:id,firstname,lastname')->get();

        return view('supplier::settings.views.prompts.edit', compact('prompt', 'pageTitle', 'breadcrumb', 'suppliers', 'sports', 'erpcategorias', 'categories', 'subfamilies', 'templates', 'versions'));
    }

    /**
     * Update prompt
     */
    public function update(UpdatePromptRequest $request, string $uid)
    {
        $prompt = Prompt::where('uid', $uid)->firstOrFail();

        $validated = $request->validated();
        $incrementVersion = $prompt->prompt_template !== $validated['prompt_template'];

        if ($incrementVersion) {
            PromptVersion::create([
                'prompt_id'        => $prompt->id,
                'version'          => $prompt->version,
                'label'            => $prompt->label,
                'scope'            => $prompt->scope,
                'content_type'     => $prompt->content_type,
                'output_language'  => $prompt->output_language,
                'tone'             => $prompt->tone,
                'priority'         => $prompt->priority,
                'seo_focus'        => $prompt->seo_focus,
                'enable_web_search'=> $prompt->enable_web_search,
                'prompt_template'  => $prompt->prompt_template,
                'saved_by'         => auth()->id(),
            ]);
        }

        $subfamilyIds = $validated['subfamily_ids'] ?? null;
        $subfamilyMode = $request->input('subfamily_mode', 'single');

        if ($subfamilyIds && ! $request->boolean('force_reassign')) {
            $conflicts = $this->findSubfamilyConflicts(array_map('intval', $subfamilyIds), $prompt->id);
            if (! empty($conflicts)) {
                $names = implode(', ', array_column($conflicts, 'subfamily_name'));
                return back()->withErrors(['subfamily_ids' => "Las subfamilias ya están asignadas a otro prompt: {$names}"])->withInput();
            }
        }

        if ($request->boolean('force_reassign') && $subfamilyIds) {
            $this->releaseSubfamiliesFromOtherPrompts($subfamilyIds, $prompt->id);
        }

        $subfamilyIds = $subfamilyIds ? array_map('intval', $subfamilyIds) : null;

        $prompt->update([
            'supplier_id'    => $validated['supplier_id'] ?? null,
            'category_id'    => $validated['category_id'] ?? null,
            'subfamily_id'   => $subfamilyIds ? $subfamilyIds[0] : null,
            'subfamily_ids'  => $subfamilyIds ?: null,
            'subfamily_mode' => $subfamilyMode,
            'label' => $validated['label'],
            'scope' => $validated['scope'],
            'content_type' => $validated['content_type'],
            'prompt_template' => $validated['prompt_template'],
            'output_language' => $validated['output_language'],
            'tone' => $validated['tone'],
            'priority' => $validated['priority'] ?? 0,
            'seo_focus' => $validated['seo_focus'],
            'enable_web_search' => $validated['enable_web_search'],
                'ai_model'          => $validated['ai_model'] ?? null,
            'is_active' => $validated['is_active'],
            'version' => $incrementVersion ? $prompt->version + 1 : $prompt->version,
        ]);

        return $this->flashSuccess('Prompt actualizado exitosamente', 'settings.suppliers.prompts.index');
    }

    /**
     * Return a specific version snapshot as JSON
     */
    public function showVersion(string $uid, int $versionId): JsonResponse
    {
        $prompt = Prompt::where('uid', $uid)->firstOrFail();
        $version = PromptVersion::where('prompt_id', $prompt->id)->findOrFail($versionId);

        return response()->json([
            'success' => true,
            'version' => [
                'id'               => $version->id,
                'version'          => $version->version,
                'label'            => $version->label,
                'prompt_template'  => $version->prompt_template,
                'scope'            => $version->scope,
                'content_type'     => $version->content_type,
                'output_language'  => $version->output_language,
                'tone'             => $version->tone,
                'seo_focus'        => $version->seo_focus,
                'enable_web_search'=> $version->enable_web_search,
                'saved_by'         => $version->savedBy?->full_name,
                'created_at'       => $version->created_at?->format('d/m/Y H:i'),
            ],
        ]);
    }

    /**
     * Toggle prompt active status
     */
    public function toggle(string $uid)
    {
        $prompt = Prompt::where('uid', $uid)->firstOrFail();
        $prompt->is_active = ! $prompt->is_active;
        $prompt->save();

        return $this->flashSuccess($prompt->is_active
            ? 'Prompt activado exitosamente'
            : 'Prompt desactivado exitosamente');
    }

    /**
     * Delete prompt
     */
    public function destroy(string $uid)
    {
        $prompt = Prompt::where('uid', $uid)->firstOrFail();
        $prompt->delete();

        return $this->flashSuccess('Prompt eliminado exitosamente');
    }

    /**
     * Preview prompt with sample data
     */
    public function preview(Request $request, string $uid): JsonResponse
    {
        try {
            $prompt = Prompt::where('uid', $uid)->firstOrFail();
            $sampleData = $request->input('sample_data', []);

            $preview = $this->contentService->previewPrompt($prompt, $sampleData);

            return response()->json([
                'success' => true,
                'preview' => $preview,
            ]);

        } catch (\Exception $e) {
            Log::error('Error previewing prompt: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al previsualizar el prompt',
            ], 500);
        }
    }

    /**
     * Duplicate prompt
     */
    public function duplicate(string $uid)
    {
        $prompt = Prompt::where('uid', $uid)->firstOrFail();

        $newPrompt = $prompt->replicate();
        $newPrompt->uid = (string) Str::ulid();
        $newPrompt->label = $prompt->label.' (Copia)';
        $newPrompt->version = 1;
        $newPrompt->is_active = false;
        $newPrompt->save();

        return $this->flashSuccess('Prompt duplicado exitosamente');
    }

    /**
     * Get prompt performance metrics
     */
    public function getMetrics(string $uid): JsonResponse
    {
        try {
            $prompt = Prompt::where('uid', $uid)->firstOrFail();

            $metrics = [
                'total_uses' => $prompt->aiContents()->count(),
                'approved_count' => $prompt->aiContents()->where('status', 'approved')->count(),
                'rejected_count' => $prompt->aiContents()->where('status', 'rejected')->count(),
                'average_tokens' => $prompt->aiContents()->avg('tokens_used') ?? 0,
                'total_cost' => $prompt->aiCosts()->sum('total_cost') ?? 0,
                'average_quality_score' => $prompt->aiContents()->avg('quality_score') ?? 0,
            ];

            return response()->json([
                'success' => true,
                'metrics' => $metrics,
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting prompt metrics: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las métricas del prompt',
            ], 500);
        }
    }

    /**
     * Toggle favorite status for the authenticated user
     */
    /**
     * Search products for Select2 AJAX in prompt builder
     */
    public function searchProducts(Request $request): JsonResponse
    {
        $search = trim((string) $request->get('q', ''));
        $supplierId = $request->get('supplier_id');
        $categoryId = $request->get('category_id');

        $query = Product::query()
            ->select('id', 'name', 'code', 'supplier_id', 'category_id')
            ->with(['supplier:id,label', 'category:id,name']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }
        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $products = $query->orderBy('name')->limit(30)->get();

        return response()->json([
            'results' => $products->map(fn (Product $p) => [
                'id' => $p->id,
                'text' => $p->code.' · '.$p->name,
                'supplier' => $p->supplier?->label,
                'category' => $p->category?->name,
            ])->all(),
        ]);
    }

    /**
     * Return template variables + attributes for a sample product
     */
    public function sampleData(int $productId, ProductChatService $chatService): JsonResponse
    {
        $product = Product::with(['supplier', 'category', 'attributes', 'translations'])
            ->findOrFail($productId);

        $variables = $chatService->buildProductVariables($product);

        $attributes = $product->attributes
            ->map(fn ($a) => [
                'id' => $a->id,
                'code' => $a->code,
                'code_secundary' => $a->code_secundary,
                'reference' => $a->reference,
                'ean13' => $a->ean13,
                'upc' => $a->upc,
                'name' => $a->name,
                'available' => (bool) $a->available,
            ])->all();

        return response()->json([
            'success' => true,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'code' => $product->code,
                'supplier' => $product->supplier?->label,
                'category' => $product->category?->name,
            ],
            'variables' => $variables,
            'attributes' => $attributes,
            'attributes_count' => count($attributes),
        ]);
    }

    public function toggleFavorite(string $uid): JsonResponse
    {
        $prompt = Prompt::where('uid', $uid)->firstOrFail();
        $user = auth()->user();

        $isFavorite = $prompt->favoritedBy()->where('user_id', $user->id)->exists();

        if ($isFavorite) {
            $prompt->favoritedBy()->detach($user->id);
        } else {
            $prompt->favoritedBy()->attach($user->id);
        }

        return response()->json([
            'success' => true,
            'is_favorite' => ! $isFavorite,
            'message' => ! $isFavorite ? 'Añadido a favoritos' : 'Eliminado de favoritos',
        ]);
    }

    public function bulkAction(BulkActionPromptRequest $request): JsonResponse
    {
        $action = $request->validated('action');
        $uids = $request->validated('uids');
        $count = 0;

        match ($action) {
            'delete' => $count = Prompt::whereIn('uid', $uids)->delete(),
            'enable' => $count = Prompt::whereIn('uid', $uids)->update(['is_active' => true]),
            'disable' => $count = Prompt::whereIn('uid', $uids)->update(['is_active' => false]),
        };

        $labels = [
            'delete' => 'eliminado(s)',
            'enable' => 'activado(s)',
            'disable' => 'desactivado(s)',
        ];

        return response()->json([
            'message' => "{$count} prompt(s) {$labels[$action]}.",
            'count' => $count,
        ]);
    }

    private function findSubfamilyConflicts(array $ids, ?int $excludePromptId = null): array
    {
        $query = Prompt::where(function ($q) use ($ids) {
            $q->whereIn('subfamily_id', $ids)
              ->orWhere(function ($q2) use ($ids) {
                  foreach ($ids as $id) {
                      $q2->orWhereJsonContains('subfamily_ids', $id)
                         ->orWhereJsonContains('subfamily_ids', (string) $id);
                  }
              });
        });
        if ($excludePromptId) {
            $query->where('id', '!=', $excludePromptId);
        }
        $conflicts = [];
        foreach ($query->get() as $prompt) {
            $assigned = array_unique(array_merge($prompt->subfamily_ids ?? [], $prompt->subfamily_id ? [$prompt->subfamily_id] : []));
            foreach (array_intersect(array_map('intval', $assigned), $ids) as $sfId) {
                $conflicts[] = [
                    'subfamily_id'   => $sfId,
                    'subfamily_name' => Subfamily::find($sfId)?->name ?? "#{$sfId}",
                    'prompt_label'   => $prompt->label,
                ];
            }
        }
        return $conflicts;
    }

    private function releaseSubfamiliesFromOtherPrompts(array $subfamilyIds, ?int $excludePromptId = null): void
    {
        $ids = array_map('intval', $subfamilyIds);

        $query = Prompt::where(function ($q) use ($ids) {
            $q->whereIn('subfamily_id', $ids)
              ->orWhere(function ($q2) use ($ids) {
                  foreach ($ids as $id) {
                      $q2->orWhereJsonContains('subfamily_ids', $id)
                         ->orWhereJsonContains('subfamily_ids', (string) $id);
                  }
              });
        });

        if ($excludePromptId) {
            $query->where('id', '!=', $excludePromptId);
        }

        foreach ($query->get() as $prompt) {
            $current = array_map('intval', array_filter((array) ($prompt->subfamily_ids ?? [])));
            $updated = array_values(array_filter($current, fn ($id) => ! in_array($id, $ids)));

            $prompt->update([
                'subfamily_id'  => count($updated) ? $updated[0] : null,
                'subfamily_ids' => count($updated) ? $updated : null,
            ]);
        }
    }
}
