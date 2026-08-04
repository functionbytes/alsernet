<?php

namespace Modules\Supplier\Services\Integrations;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Supplier\Models\Ai\AiContent;
use Modules\Supplier\Models\Category\Category;
use Modules\Supplier\Models\Category\Sport;
use Modules\Supplier\Models\Category\Subfamily;
use Modules\Supplier\Models\Product\Product;
use Modules\Supplier\Models\Product\ProductAttribute;
use Modules\Supplier\Models\Supplier\Supplier;
use Modules\Supplier\Models\Sync\ExcludedErpGroup;
use Modules\Supplier\Models\Sync\SyncBatch;
use Modules\Supplier\Models\Sync\SyncFailure;
use Modules\Supplier\Models\Sync\SyncLog;
use Modules\Core\Models\Setting;
use Modules\Supplier\Services\ContentGenerationService;
use Modules\Supplier\Services\PromptSelectionService;

/**
 * Sincroniza modelos ERP desde Oracle → supplier_products + supplier_product_attributes.
 *
 * Modo filter (principal):
 *   Consume /api/erp/products/filter?description_empty=1&web=2
 *   El endpoint ya devuelve product_attributes y el proveedor default → sin llamadas extra.
 *
 * Modo legacy (@deprecated):
 *   Consume /api/erp/modelos + /api/erp/modelos/detallado/{id} por separado (1 petición HTTP por modelo).
 */
class ErpModelSyncService
{
    protected string $erpBaseUrl;

    protected int $pageSize = 100;

    public function __construct(
        private readonly ErpProviderSyncService $providerSyncService,
        private readonly PromptSelectionService $promptSelectionService,
        private readonly ContentGenerationService $contentGenerationService,
    ) {
        $base = Setting::get('supplier.erp_internal_url', config('supplier.erp_internal_url', 'http://nginx'));
        $this->erpBaseUrl = rtrim($base, '/').'/api/erp';
    }

    /**
     * Sincroniza modelos desde /api/erp/products/filter?description_empty=1&web=2.
     * El endpoint devuelve product_attributes y el proveedor default incluidos → sin llamadas extra.
     *
     * @return array{success: bool, models: int, attributes: int, errors: array}
     */
    public function syncAllModelsFromFilter(
        ?SyncBatch $batch = null,
        ?int $limit = null,
        bool $force = false,
        ?string $dateFrom = null,
        bool $skipAi = false,
        bool $dryRun = false,
        ?int $erpModelId = null,
        ?int $erpProviderId = null,
        bool $descriptionEmpty = false,
        ?string $webFilter = '2',
        bool $registerOnly = false,
        string $dateField = 'creation',
    ): array {
        $stats = ['success' => false, 'models' => 0, 'attributes' => 0, 'skipped' => 0, 'errors' => []];
        $logBuffer = [];
        $consecutiveErrors = 0;
        $circuitBreakerThreshold = config('supplier.sync.circuit_breaker_threshold', 10);
        $circuitBroken = false;

        try {
            Log::info('Starting model sync from ERP filter', ['limit' => $limit, 'date_from' => $dateFrom, 'force' => $force, 'skip_ai' => $skipAi, 'dry_run' => $dryRun, 'erp_model_id' => $erpModelId]);

            // Sincronizar un único modelo por ID
            if ($erpModelId !== null) {
                $response = Http::timeout(60)->get("{$this->erpBaseUrl}/products/{$erpModelId}/detailed");

                if (! $response->successful()) {
                    $stats['errors'][] = "ERP API error: {$response->status()}";

                    return $stats;
                }

                $data = $response->json();
                $modelData = $data['data'] ?? null;

                if (! $modelData) {
                    $stats['errors'][] = 'No data returned from ERP for model '.$erpModelId;

                    return $stats;
                }

                $registeredErpIds = $force ? [] : Product::whereNotNull('erp_id')->pluck('erp_id')->flip()->all();
                $this->processSingleModel($modelData, $batch, $force, $stats, $logBuffer, $registeredErpIds, null, $consecutiveErrors, $circuitBreakerThreshold, $circuitBroken, $skipAi, $dryRun, $registerOnly);
                $this->flushLogBuffer($logBuffer);
                $stats['success'] = empty($stats['errors']);

                return $stats;
            }

            // Pre-load all registered ERP IDs in a single query → O(1) isset lookup per model.
            // Also includes ERP IDs of products that already have AI-generated content
            // (long_description or generated_name set) so they aren't re-processed even
            // if force=false. This prevents re-importing products the manager already described.
            if ($force) {
                $registeredErpIds = [];
            } else {
                // Value 0 = registered product (erp_id exists in products table)
                // Value 1 = has AI-generated description (AiContent with long_description or generated_name)
                // Cached for 5 minutes to avoid 2 expensive queries on every sync run.
                $registeredErpIds = Cache::remember('sync:registered_erp_ids', 300, function () {
                    $ids = Product::whereNotNull('erp_id')->pluck('erp_id')->flip()->map(fn () => 0)->all();

                    // También saltar modelos ERP cuyo producto ya tiene descripción IA generada
                    AiContent::whereNotNull('erp_reference')
                        ->where(fn ($q) => $q->whereNotNull('long_description')->orWhereNotNull('generated_name'))
                        ->pluck('erp_reference')
                        ->each(function ($ref) use (&$ids) {
                            if (is_numeric($ref)) {
                                $intRef = (int) $ref;
                                // Only mark as AI-skip (1) if not already a registered product (0)
                                if (! isset($ids[$intRef])) {
                                    $ids[$intRef] = 1;
                                }
                            }
                        });

                    return $ids;
                });
            }

            Log::info('Registered ERP IDs pre-loaded', [
                'count' => count($registeredErpIds),
                'force' => $force,
            ]);

            $filterParams = [
                'date_from'  => $dateFrom ?? config('supplier.erp_sync.filter_date_from'),
                'date_field' => $dateField,
            ];
            if ($descriptionEmpty) {
                $filterParams['description_empty'] = 1;
            }
            if ($webFilter) {
                $filterParams['web'] = $webFilter;
            }
            if ($erpProviderId) {
                $filterParams['idproveedor'] = $erpProviderId;
            }

            $httpErrors = $this->paginatedErpRequest(
                url: "{$this->erpBaseUrl}/products/filter",
                params: $filterParams,
                timeout: 120,
                maxItems: $limit,
                processModel: function (array $modelData) use ($batch, $force, $limit, $circuitBreakerThreshold, $skipAi, $dryRun, $registerOnly, &$stats, &$logBuffer, &$registeredErpIds, &$consecutiveErrors, &$circuitBroken): bool {
                    return $this->processSingleModel($modelData, $batch, $force, $stats, $logBuffer, $registeredErpIds, $limit, $consecutiveErrors, $circuitBreakerThreshold, $circuitBroken, $skipAi, $dryRun, $registerOnly);
                },
                onPageComplete: function () use (&$logBuffer): void {
                    $this->flushLogBuffer($logBuffer);
                },
            );

            $this->flushLogBuffer($logBuffer);
            $stats['errors'] = array_merge($stats['errors'], $httpErrors);

            if ($circuitBroken) {
                $stats['errors'][] = "Circuit breaker activado: {$consecutiveErrors} errores consecutivos";
                $batch?->update(['metadata->circuit_broken' => true]);
            }

            // Éxito si no hubo errores HTTP, o si se alcanzó el límite pedido (errores de paginación extra no cuentan)
            $reachedLimit = $limit !== null && $stats['models'] >= $limit;
            $stats['success'] = empty($httpErrors) || $reachedLimit;
            Log::info('Model sync from filter completed', $stats);

            // Invalidar cache de IDs registrados para que la próxima consulta refleje los cambios.
            Cache::forget('sync:registered_erp_ids');
        } catch (Exception $e) {
            $this->flushLogBuffer($logBuffer);
            Log::error('Model sync from filter failed', ['error' => $e->getMessage()]);
            $stats['errors'][] = $e->getMessage();
        }

        return $stats;
    }

    protected function processSingleModel(
        array $modelData,
        ?SyncBatch $batch,
        bool $force,
        array &$stats,
        array &$logBuffer,
        array &$registeredErpIds,
        ?int $limit,
        int &$consecutiveErrors = 0,
        int $circuitBreakerThreshold = 10,
        bool &$circuitBroken = false,
        bool $skipAi = false,
        bool $dryRun = false,
        bool $registerOnly = false,
    ): bool {
        $erpModelId = $modelData['id'] ?? null;

        try {
            // Fix 3: comprobar grupos excluidos aquí (antes de syncModelFromFilter) para
            // tener acceso a $logBuffer/$stats y poder loguear/auto-resolver fallos.
            if ($this->isModelExcluded($modelData)) {
                $stats['skipped']++;
                if ($batch) {
                    $logBuffer[] = $this->buildLogRow($batch, [
                        'entity_type' => 'model',
                        'erp_id'      => $erpModelId,
                        'action'      => 'skip',
                        'result'      => 'skipped',
                        'message'     => 'Grupo ERP excluido — omitido (sin crear producto ni contenido)',
                    ]);

                    // Fix 4: auto-resolver fallos pendientes del mismo erp_id
                    if ($erpModelId) {
                        SyncFailure::where('erp_id', $erpModelId)
                            ->whereIn('failure_status', ['pending', 'acknowledged'])
                            ->update([
                                'failure_status'   => 'resolved',
                                'resolution_notes' => 'Auto-resuelto: modelo pertenece a grupo ERP excluido',
                                'resolved_at'      => now(),
                            ]);
                    }
                }
                $batch?->incrementProcessedItems();

                return true;
            }

            if (! $force && $erpModelId && isset($registeredErpIds[$erpModelId])) {
                $stats['skipped']++;
                if ($batch) {
                    $skipReason = $registeredErpIds[$erpModelId] === 1
                        ? 'Descripción IA ya generada — omitido (usa Forzar para re-generar)'
                        : 'Ya registrado — omitido (usa Forzar para re-sincronizar)';
                    $logBuffer[] = $this->buildLogRow($batch, [
                        'entity_type' => 'model',
                        'erp_id' => $erpModelId,
                        'action' => 'skip',
                        'result' => 'skipped',
                        'message' => $skipReason,
                    ]);
                }
                $batch?->incrementProcessedItems();

                return true;
            }

            $existsBefore = $force && $erpModelId
                ? Product::where('erp_id', $erpModelId)->exists()
                : false;

            [$product, $attributeCount] = $this->syncModelFromFilter($modelData, $batch, $skipAi, $dryRun, $registerOnly);

            if ($product) {
                $consecutiveErrors = 0;
                $stats['models']++;
                $stats['attributes'] += $attributeCount;

                if ($erpModelId) {
                    $registeredErpIds[$erpModelId] = 0;
                }

                if ($batch) {
                    $logBuffer[] = $this->buildLogRow($batch, [
                        'entity_type' => 'model',
                        'entity_id' => $product->id,
                        'erp_id' => $erpModelId,
                        'action' => $existsBefore ? 'update' : 'create',
                        'result' => 'success',
                    ]);

                    if ($erpModelId) {
                        SyncFailure::where('erp_id', $erpModelId)
                            ->whereIn('failure_status', ['pending', 'acknowledged'])
                            ->delete();
                    }
                }
            }

            $batch?->incrementProcessedItems();

            // El límite aplica solo a modelos NUEVOS creados/actualizados, no a los skipped
            return ! ($limit !== null && $stats['models'] >= $limit);
        } catch (Exception $e) {
            $stats['errors'][] = "Model {$erpModelId}: {$e->getMessage()}";
            Log::error('Model sync error (filter)', [
                'model_id' => $erpModelId,
                'error' => $e->getMessage(),
            ]);

            // Los fallos de calidad de datos (sin proveedor/categoría/artículos en ERP)
            // son por-item y no indican que el ERP o el pipeline estén caídos: si se
            // cuentan igual que un error transitorio, un lote de items con datos
            // incompletos dispara el circuit breaker y aborta el resto del batch,
            // incluyendo items válidos que venían después (visto con H315470: quedaba
            // atrapado detrás de 10 modelos "sin_proveedor" consecutivos).
            if ($this->isTransientFailure($e->getMessage())) {
                $consecutiveErrors++;
                if ($consecutiveErrors >= $circuitBreakerThreshold) {
                    $circuitBroken = true;
                    Log::warning('Circuit breaker triggered during batch sync', [
                        'consecutive_errors' => $consecutiveErrors,
                        'threshold' => $circuitBreakerThreshold,
                        'batch_id' => $batch?->id,
                    ]);

                    // Visible en el panel (antes solo quedaba en laravel.log): que quede
                    // registrado en el propio batch por qué se cortó la sincronización.
                    if ($batch) {
                        $logBuffer[] = $this->buildLogRow($batch, [
                            'entity_type' => 'model',
                            'erp_id' => $erpModelId,
                            'action' => 'abort',
                            'result' => 'failed',
                            'message' => "Circuit breaker: {$consecutiveErrors} errores transitorios consecutivos (umbral {$circuitBreakerThreshold}) — sincronización cortada, items restantes del ERP sin evaluar",
                            'error_message' => $e->getMessage(),
                        ]);
                    }
                }
            }

            if ($batch) {
                $logBuffer[] = $this->buildLogRow($batch, [
                    'entity_type' => 'model',
                    'erp_id' => $erpModelId,
                    'action' => 'create',
                    'result' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);

                $this->recordSyncFailure($batch, $erpModelId, $e->getMessage(), $modelData);
            }

            $batch?->incrementFailedItems();

            return ! $circuitBroken;
        }
    }

    /**
     * Iterates a paginated ERP endpoint, invoking $processModel for every item.
     *
     * The processor returns false to stop early (e.g. a limit was reached).
     * $onPageComplete, when given, runs after each page so callers can flush
     * any per-page buffers.
     *
     * @param  array<string, mixed>  $params  Extra query params merged with limit/offset.
     * @param  callable(array): bool  $processModel
     * @param  callable():void|null  $onPageComplete
     * @return list<string> HTTP-level errors encountered while paginating.
     */
    private function paginatedErpRequest(
        string $url,
        array $params,
        int $timeout,
        callable $processModel,
        ?callable $onPageComplete = null,
        int $startOffset = 0,
        ?int $maxItems = null,
    ): array {
        $errors = [];
        $offset = $startOffset;
        $hasMore = true;
        $totalScanned = 0;
        // Cuando hay un límite explícito, reducir el pageSize para no traer muchos más
        // items de los necesarios en la primera petición.
        $pageSize = $maxItems !== null ? min($maxItems, $this->pageSize) : $this->pageSize;

        while ($hasMore) {
            $response = Http::timeout($timeout)->get($url, array_merge($params, [
                'limit' => $pageSize,
                'offset' => $offset,
            ]));

            if (! $response->successful()) {
                $errors[] = "ERP API error: {$response->status()}";
                break;
            }

            $data = $response->json();
            $models = $data['data'] ?? [];
            $hasMore = $data['pagination']['hasMore'] ?? false;

            if (empty($models)) {
                break;
            }

            foreach ($models as $modelData) {
                // Límite de total de items escaneados del ERP: evita escanear toda la BD
                // cuando hay muchos productos ya registrados y el límite no se alcanza por nuevos.
                if ($maxItems !== null && $totalScanned >= $maxItems) {
                    $hasMore = false;
                    break;
                }
                $totalScanned++;

                if ($processModel($modelData) === false) {
                    $hasMore = false;
                    break;
                }
            }

            if ($onPageComplete !== null) {
                $onPageComplete();
            }

            $offset += $pageSize;
        }

        return $errors;
    }

    /**
     * Build a SyncLog insert row with the columns SyncLog::insert() needs
     * (uid + timestamps) plus sensible defaults for the unset columns.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function buildLogRow(SyncBatch $batch, array $attributes): array
    {
        $now = now();

        return array_merge([
            'uid' => (string) Str::ulid(),
            'batch_id' => $batch->id,
            'entity_id' => null,
            'erp_id' => null,
            'message' => null,
            'error_message' => null,
            'retry_count' => 0,
            'triggered_by' => 'sync_job',
            'synced_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ], $attributes);
    }

    /**
     * Persist any buffered SyncLog rows in a single insert and reset the buffer.
     *
     * @param  list<array<string, mixed>>  $buffer
     */
    private function flushLogBuffer(array &$buffer): void
    {
        if ($buffer === []) {
            return;
        }

        SyncLog::insert($buffer);
        $buffer = [];
    }

    /**
     * Check whether a product with the given ERP model ID is already registered.
     * Any product with that erp_id is considered registered and will be skipped
     * to avoid re-processing. Use force=true to override.
     *
     * NOTE: syncAllModelsFromFilter() uses a pre-loaded Set instead of calling this
     * method per model to avoid N+1 queries. This method is kept for ad-hoc callers.
     */
    protected function wasAlreadySynced(int $erpModelId, bool $force = false): bool
    {
        if ($force) {
            return false;
        }

        return Product::where('erp_id', $erpModelId)->exists();
    }

    /**
     * Comprueba si algún artículo del modelo pertenece a un grupo ERP excluido.
     * Contrasta idgrupo_cl, idsubfamilia_cl e idfamilia_cl contra la lista de exclusión.
     */
    protected function isModelExcluded(array $data): bool
    {
        $excluded = ExcludedErpGroup::getExcludedSet();
        if (empty($excluded)) {
            return false;
        }

        // Nivel familia (idfamilia_cl)
        $familyId = $data['categorie']['id'] ?? null;
        if ($familyId && isset($excluded[$familyId])) {
            return true;
        }

        // Nivel grupo/subfamilia desde cada artículo
        foreach ($data['product_attributes'] ?? [] as $attr) {
            $grupoId    = $attr['grupo']       ?? null;
            $subfamId   = $attr['subfamily_id'] ?? null;

            if ($grupoId  && isset($excluded[$grupoId]))  return true;
            if ($subfamId && isset($excluded[$subfamId])) return true;
        }

        return false;
    }

    /**
     * Sincroniza un modelo usando datos ya completos del filter endpoint.
     * No hace llamadas HTTP adicionales: product_attributes y supplier ya vienen en $data.
     *
     * Si falta categoría, proveedor o artículos se lanza una excepción: el producto NO se crea
     * y el bloque catch del llamador registra un SyncFailure para reintentar desde la UI.
     *
     * @return array{0: Product, 1: int}
     */
    protected function syncModelFromFilter(array $data, ?SyncBatch $batch = null, bool $skipAi = false, bool $dryRun = false, bool $registerOnly = false): array
    {
        $erpModelId = $data['id'] ?? null;

        if (! $erpModelId) {
            return [null, 0];
        }

        // Comprueba si el modelo pertenece a un grupo/subfamilia/familia excluido
        if ($this->isModelExcluded($data)) {
            Log::info('ERP model skipped — excluded group', [
                'erp_model_id' => $erpModelId,
                'categorie_id' => $data['categorie']['id'] ?? null,
            ]);
            return [null, 0];
        }

        $supplier  = $this->resolveSupplierFromFilter($data);
        $category  = $this->resolveCategoryFromFilter($data);
        $subfamily = $this->resolveSubfamilyFromFilter($data);

        // Resolver sport directamente del dato ERP (más fiable que via $category->sport_id,
        // porque la categoría puede existir en local sin sport_id si fue importada antes)
        $erpSportId = $data['categorie']['sport']['id'] ?? null;
        $sport = $erpSportId ? Sport::where('erp_id', $erpSportId)->first() : null;
        $attributes = $data['product_attributes'] ?? [];

        if (! $category) {
            throw new Exception('Modelo sin categoría relacionada en ERP');
        }

        if (! $supplier) {
            throw new Exception('Modelo sin proveedor asignado en ERP');
        }

        if (empty($attributes)) {
            throw new Exception('Modelo sin artículos relacionados en ERP');
        }

        // Dry-run: validar datos ERP pero sin escribir en BD
        if ($dryRun) {
            $existing = Product::where('erp_id', $erpModelId)->first();
            $fakeProduct = $existing ?? new Product(['id' => 0, 'erp_id' => $erpModelId, 'name' => $data['description'] ?? "Modelo {$erpModelId}"]);

            return [$fakeProduct, count($attributes)];
        }

        $product = $this->upsertProduct($erpModelId, $supplier, $category, $sport, $data, $subfamily);

        $attributeCount = $this->syncAttributesInline($product, $attributes);

        // skip_ai=true → registra AiContent como pending_generation sin llamar a la IA
        $this->handleAiContent($product, $supplier, $category, $data, $registerOnly || $skipAi);

        return [$product, $attributeCount];
    }

    /**
     * Resuelve el proveedor a partir de supplier del filter endpoint (ya filtrado por default).
     * Si no existe en local lo crea con los datos disponibles.
     */
    protected function resolveSupplierFromFilter(array $data): ?Supplier
    {
        // El endpoint ya devuelve el proveedor resuelto (default primero, luego cualquiera)
        $supplierData = $data['supplier'] ?? null;
        $erpProviderId = $supplierData['id'] ?? null;

        if (! $erpProviderId) {
            return null;
        }

        $supplier = Supplier::where('erp_id', $erpProviderId)->first();

        if ($supplier) {
            return $supplier;
        }

        $result = $this->providerSyncService->syncProvider([
            'id' => $erpProviderId,
            'label' => $supplierData['name'] ?? "Proveedor {$erpProviderId}",
            'cif' => $supplierData['cif'] ?? null,
            'email' => $supplierData['email'] ?? null,
            'available' => $supplierData['available'] ?? true,
        ]);

        return $result['supplier'];
    }

    /**
     * Resuelve la subfamilia a partir del primer product_attribute del modelo.
     * Si ya existe en la BD local (sincronizada desde la jerarquía de categorías) la reutiliza.
     * Si no existe la crea con nombre provisional que se corregirá en la próxima sync de categorías.
     */
    protected function resolveSubfamilyFromFilter(array $data): ?Subfamily
    {
        $firstAttr   = ($data['product_attributes'] ?? [])[0] ?? [];
        $erpSubfamId = $firstAttr['subfamily_id'] ?? null;

        if (! $erpSubfamId) {
            return null;
        }

        $existing = Subfamily::where('erp_id', $erpSubfamId)->first();
        if ($existing) {
            return $existing;
        }

        return Subfamily::create([
            'erp_id'    => $erpSubfamId,
            'name'      => "Subfamilia {$erpSubfamId}",
            'available' => true,
        ]);
    }

    /**
     * Resuelve la categoría a partir de categorie del filter endpoint.
     * Si no existe crea también el sport si viene en los datos.
     */
    protected function resolveCategoryFromFilter(array $data): ?Category
    {
        $categorieData = $data['categorie'] ?? null;
        $erpCategoryId = $categorieData['id'] ?? null;

        if (! $erpCategoryId) {
            return null;
        }

        // Resolver sport siempre, independientemente de si la categoría ya existe
        $sportData = $categorieData['sport'] ?? null;
        $sport = null;

        if ($sportData && isset($sportData['id'])) {
            $sport = Sport::firstOrCreate(
                ['erp_id' => $sportData['id']],
                [
                    'name' => $this->stripPrefix($sportData['description'] ?? null),
                    'short_name' => $this->stripPrefix($sportData['description_short'] ?? null),
                    'available' => $sportData['available'] ?? true,
                    'last_sync_at' => now(),
                ]
            );
        }

        $category = Category::where('erp_id', $erpCategoryId)->first();

        if ($category) {
            // Actualizar sport_id si no estaba asignado
            if ($sport && ! $category->sport_id) {
                $category->update([
                    'sport_id'     => $sport->id,
                    'erp_sport_id' => $sport->erp_id,
                ]);
            }

            return $category;
        }

        return Category::create([
            'erp_id'       => $erpCategoryId,
            'name'         => $this->stripPrefix($categorieData['description'] ?? null),
            'short_name'   => $this->stripPrefix($categorieData['description_short'] ?? null),
            'available'    => $categorieData['available'] ?? true,
            'sport_id'     => $sport?->id,
            'erp_sport_id' => $sportData['id'] ?? null,
            'last_sync_at' => now(),
        ]);
    }

    /**
     * Upserta atributos directamente desde el array product_attributes ya cargado.
     * Evita la llamada extra a /api/erp/modelos/detallado/{id}.
     */
    protected function syncAttributesInline(Product $product, array $productAttributes): int
    {
        $count = 0;
        $incomingErpIds = [];

        foreach ($productAttributes as $articuloData) {
            $this->upsertAttribute($product, $articuloData);
            $count++;
            if (isset($articuloData['id'])) {
                $incomingErpIds[] = (int) $articuloData['id'];
            }
        }

        // Soft-delete attributes that no longer exist in Oracle for this model
        if ($incomingErpIds) {
            ProductAttribute::where('product_id', $product->id)
                ->whereNotIn('erp_id', $incomingErpIds)
                ->whereNull('deleted_at')
                ->update(['deleted_at' => now()]);
        }

        return $count;
    }

    /**
     * Upsert supplier_products usando erp_id = idmodelo.
     */
    protected function upsertProduct(int $erpModelId, ?Supplier $supplier, ?Category $category, ?Sport $sport, array $data, ?Subfamily $subfamily = null): Product
    {
        $product = Product::withTrashed()->where('erp_id', $erpModelId)->first()
            ?? new Product;

        if ($product->trashed()) {
            $product->restore();
        }

        $existingMeta = $product->exists ? ($product->metadata ?? []) : [];
        $rawDescription = $data['description'] ?? $data['descripcion'] ?? null;
        // 'web_status' es el valor numérico crudo de MODELO.ESTADO_PUBLICADO_WEB
        // (0 = no publicar, 1 = publicar, 2 = pendiente de revisión). 'web' es un
        // booleano derivado en el endpoint ERP que colapsa 1 y 2 en `true` — usarlo
        // aquí perdía la distinción y marcaba items "pendientes de revisión" como
        // publicados. Fallback a 'web' solo si el endpoint todavía no manda el raw.
        $rawWebStatus = $data['web_status'] ?? $data['estado_publicado_web'] ?? $data['web'] ?? null;

        $metaUpdates = [];
        if (is_string($rawDescription) && trim($rawDescription) !== '') {
            $metaUpdates['erp_description'] = trim($rawDescription);
        }
        if ($rawWebStatus !== null) {
            $metaUpdates['web_status'] = (int) $rawWebStatus;
        }
        $rawMarca = $data['marca'] ?? $data['idmarca'] ?? null;
        if ($rawMarca !== null) {
            $metaUpdates['erp_marca'] = (string) $rawMarca;
        }

        // Obtener erp_subfamily_id y erp_sport_id del primer atributo
        $firstAttr      = ($data['product_attributes'] ?? [])[0] ?? [];
        $erpSubfamilyId = $firstAttr['subfamily_id'] ?? null;
        $erpSportId     = $firstAttr['sport_id'] ?? ($data['categorie']['sport']['id'] ?? null);

        $product->fill([
            'erp_id'          => $erpModelId,
            'supplier_id'     => $supplier?->id,
            'category_id'     => $category?->id,
            'erp_category_id' => $data['idgrupo_cl'] ?? $data['grupo'] ?? null,
            'subfamily_id'    => $subfamily?->id,
            'erp_subfamily_id'=> $erpSubfamilyId,
            'sport_id'        => $sport?->id,
            'erp_sport_id'    => $erpSportId,
            'erp_model_id'    => $erpModelId,
            'code'            => $data['codigo'] ?? $data['code'] ?? null,
            'name'            => ($data['nombre'] ?? null) ?: ($data['name'] ?? null) ?: ($data['description'] ?? null),
            'available'       => (bool) ($data['estado'] ?? $data['available'] ?? true),
            'is_default'      => (bool) ($data['default'] ?? false),
            // Solo web_status=1 ("publicar") cuenta como publicado; 2 ("pendiente de
            // revisión") no debe marcarse como publicado aunque sea "truthy".
            'web_published'   => (int) ($rawWebStatus ?? 0) === 1,
            'last_sync_at'    => now(),
            'metadata'        => $metaUpdates ? array_merge($existingMeta, $metaUpdates) : ($existingMeta ?: null),
        ]);

        $product->save();

        return $product;
    }

    /**
     * Upsert supplier_product_attributes para un artículo.
     */
    protected function upsertAttribute(Product $product, array $data): void
    {
        $erpArticuloId = $data['id'] ?? null;

        if (! $erpArticuloId) {
            return;
        }

        $attribute = ProductAttribute::withTrashed()->where('erp_id', $erpArticuloId)->first()
            ?? new ProductAttribute;

        if ($attribute->trashed()) {
            $attribute->restore();
        }

        $erpCategoryId  = $data['categorie'] ?? $data['idfamilia_cl'] ?? null;
        $erpSubfamilyId = $data['subfamily_id'] ?? null;
        $erpSportId     = $data['sport_id'] ?? null;

        $category  = $erpCategoryId  ? Category::where('erp_id', $erpCategoryId)->first()  : null;
        $subfamily = $erpSubfamilyId ? Subfamily::where('erp_id', $erpSubfamilyId)->first() : null;
        $sport     = $erpSportId     ? Sport::where('erp_id', $erpSportId)->first()         : null;

        $attribute->fill([
            'erp_id'          => $erpArticuloId,
            'product_id'      => $product->id,
            'category_id'     => $category?->id,
            'erp_category_id' => $erpCategoryId,
            'erp_group_id'    => $data['grupo'] ?? $data['idgrupo_cl'] ?? null,
            'subfamily_id'    => $subfamily?->id,
            'erp_subfamily_id'=> $erpSubfamilyId,
            'sport_id'        => $sport?->id,
            'erp_sport_id'    => $erpSportId,
            'code'            => $data['code'] ?? $data['codigo'] ?? null,
            'code_secundary'  => $data['code_secundary'] ?? $data['codigo_secundario'] ?? null,
            'reference'       => $data['reference'] ?? $data['referencia'] ?? null,
            'ean13'           => $data['ean13'] ?? null,
            'upc'             => $data['upc'] ?? null,
            'name'            => $data['description'] ?? $data['nombre'] ?? $data['name'] ?? null,
            'available'       => (bool) ($data['available'] ?? $data['estado'] ?? true),
            // Ver nota en upsertProduct(): solo web_status=1 es "publicado".
            'web_published'   => (int) ($data['web_status'] ?? $data['estado_publicado_web'] ?? $data['web'] ?? 0) === 1,
            'erp_created_at'  => $data['created'] ?? null,
            'erp_updated_at'  => $data['updated'] ?? null,
            'last_sync_at'    => now(),
        ]);

        $attribute->save();
    }

    /**
     * Selecciona prompt y genera contenido AI a partir de los datos ERP del modelo.
     *
     * Con prompt → llama a la API AI con el contenido del modelo y atributos → pending_validation.
     * Sin prompt → crea AiContent en pending_generation (esperando configuración de prompt).
     */
    protected function handleAiContent(Product $product, ?Supplier $supplier, ?Category $category, array $erpData = [], bool $registerOnly = false): void
    {
        if (! $supplier) {
            return;
        }

        if (AiContent::where('supplier_product_id', $product->id)->exists()) {
            return;
        }

        // Vincular registros huérfanos (sin supplier_product_id) cuyo source_attributes.id
        // coincida con el erp_id del producto recién sincronizado.
        $orphan = AiContent::whereNull('supplier_product_id')
            ->whereJsonContains('source_attributes->id', $product->erp_id)
            ->first();

        if ($orphan) {
            $orphan->update(['supplier_product_id' => $product->id]);

            return;
        }

        $prompt = $this->promptSelectionService->selectPrompt(
            supplierId: $supplier->id,
            categoryId: $category?->id,
            subfamilyId: $product->subfamily_id,
        );

        $hasValidPrompt = $prompt && in_array($prompt->scope, ['supplier_category', 'category', 'global']);

        // registerOnly: registrar AiContent como pending_generation sin disparar generación,
        // independientemente de si hay prompt configurado para la subfamilia
        if ($registerOnly) {
            AiContent::create([
                'supplier_id' => $supplier->id,
                'supplier_product_id' => $product->id,
                'erp_reference' => $product->code,
                'status' => AiContent::STATUS_PENDING_GENERATION,
                'prompt_id' => $hasValidPrompt ? $prompt->id : null,
                'source_attributes' => $erpData ?: null,
            ]);

            Log::info('AI content created as pending_generation (register_only mode)', [
                'product_id' => $product->id,
                'supplier_id' => $supplier->id,
                'category_id' => $category?->id,
                'prompt_found' => $hasValidPrompt ? "yes (scope: {$prompt->scope})" : 'no',
            ]);

            return;
        }

        if (! $hasValidPrompt) {
            // Sin prompt aplicable → crear como pending_generation para que aparezca
            // en la lista de contenido y pueda ser generado manualmente más tarde
            AiContent::create([
                'supplier_id' => $supplier->id,
                'supplier_product_id' => $product->id,
                'erp_reference' => $product->code,
                'status' => AiContent::STATUS_PENDING_GENERATION,
                'prompt_id' => null,
                'source_attributes' => $erpData ?: null,
            ]);

            Log::info('AI content created as pending_generation during ERP sync (no applicable prompt)', [
                'product_id' => $product->id,
                'supplier_id' => $supplier->id,
                'category_id' => $category?->id,
                'prompt_found' => $prompt ? "yes (scope: {$prompt->scope})" : 'no',
            ]);

            return;
        }

        // Crear y generar automáticamente - SOLO si llegamos aquí con prompt válido
        $content = AiContent::create([
            'supplier_id' => $supplier->id,
            'supplier_product_id' => $product->id,
            'erp_reference' => $product->code,
            'status' => AiContent::STATUS_GENERATING,
            'prompt_id' => $prompt->id,
            'source_attributes' => $erpData ?: null,
        ]);

        try {
            $this->contentGenerationService->generateFromErpModel($content, $prompt, $erpData);

            Log::info('AI content generated automatically from ERP sync', [
                'product_id' => $product->id,
                'content_id' => $content->id,
                'supplier_id' => $supplier->id,
                'category_id' => $category?->id,
                'prompt_id' => $prompt->id,
                'prompt_scope' => $prompt->scope,
            ]);
        } catch (Exception $e) {
            $content->markAsFailed($e->getMessage());

            Log::error('AI generation failed during ERP sync', [
                'product_id' => $product->id,
                'content_id' => $content->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Reintenta sincronizar un modelo específico desde el ERP.
     * Usado para reintentar fallos individuales.
     *
     * @return array{success: bool, product: Product|null, error: string|null}
     */
    public function retryModelFromErp(int $erpModelId): array
    {
        try {
            Cache::forget("product:detailed:{$erpModelId}");

            $response = Http::timeout(60)->get("{$this->erpBaseUrl}/products/{$erpModelId}/detailed");

            if (! $response->successful()) {
                return ['success' => false, 'product' => null, 'error' => "ERP API error: {$response->status()}"];
            }

            $data = $response->json();

            if (! ($data['success'] ?? false)) {
                return ['success' => false, 'product' => null, 'error' => 'ERP returned error'];
            }

            $modelData = $data['data'] ?? null;

            if (! $modelData) {
                return ['success' => false, 'product' => null, 'error' => 'No data from ERP'];
            }

            [$product, $attributeCount] = $this->syncModelFromFilter($modelData);

            if (! $product) {
                return ['success' => false, 'product' => null, 'error' => 'Could not sync model'];
            }

            return ['success' => true, 'product' => $product, 'error' => null];
        } catch (Exception $e) {
            return ['success' => false, 'product' => null, 'error' => $e->getMessage()];
        }
    }

    /** Quita el prefijo "T." que el ERP antepone a todos los nombres de categorías. */
    private function stripPrefix(?string $name): ?string
    {
        return $name ? preg_replace('/^T\./i', '', $name) : $name;
    }

    /**
     * Distingue errores transitorios (API/BD caídos, indican que el pipeline
     * está roto) de fallos de calidad de datos (item puntual sin proveedor/
     * categoría/artículos en ERP). Solo los primeros deben alimentar el
     * circuit breaker del batch — ver processSingleModel().
     */
    private function isTransientFailure(string $errorMessage): bool
    {
        return $this->classifyFailureType($errorMessage) === 'error_api'
            || $this->classifyFailureType($errorMessage) === 'error_db';
    }

    private function classifyFailureType(string $errorMessage): string
    {
        return match (true) {
            str_contains($errorMessage, 'sin proveedor')  => 'sin_proveedor',
            str_contains($errorMessage, 'sin categoría')  => 'sin_categoria',
            str_contains($errorMessage, 'sin artículos')  => 'sin_articulos',
            str_contains($errorMessage, 'ERP API error')  => 'error_api',
            str_contains($errorMessage, 'SQLSTATE')       => 'error_db',
            default                                       => 'datos_invalidos',
        };
    }

    /**
     * Registra un fallo de sincronización en supplier_sync_failures.
     */
    protected function recordSyncFailure(
        SyncBatch $batch,
        ?int $erpModelId,
        string $errorMessage,
        array $modelData = [],
    ): void {
        $failureType = $this->classifyFailureType($errorMessage);

        $existing = SyncFailure::where('erp_id', $erpModelId)
            ->whereIn('failure_status', ['pending', 'acknowledged'])
            ->first();

        if ($existing) {
            $existing->update([
                'batch_id' => $batch->id,
                'error_message' => $errorMessage,
                'failure_type' => $failureType,
                'changed_data' => $modelData,
                'retry_count' => $existing->retry_count + 1,
                'last_retry_at' => now(),
            ]);

            return;
        }

        SyncFailure::create([
            'batch_id' => $batch->id,
            'sync_type' => 'product',
            'erp_id' => $erpModelId,
            'error_message' => $errorMessage,
            'error_code' => $failureType,
            'failure_type' => $failureType,
            'changed_data' => $modelData,
            'failure_status' => 'pending',
            'max_retries' => 3,
            'retry_count' => 0,
            'last_retry_at' => null,
        ]);
    }
}
