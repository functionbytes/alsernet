<?php

namespace Modules\Erp\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Erp\Models\Oracle\Articulo\Articulo;
use Modules\Erp\Models\Oracle\Configuracion\FamiliaCl;
use Modules\Erp\Models\Oracle\Configuracion\SubfamiliaCl;
use Modules\Erp\Models\Oracle\Otros\CategoriaCl;
use Modules\Erp\Models\Oracle\Otros\DeporteCl;
use Modules\Erp\Models\Oracle\Otros\GrupoCl;
use Modules\Erp\Models\Oracle\Proveedor\Artiprov;
use Modules\Erp\Models\Oracle\Proveedor\ArtiprovTarifapro;
use Modules\Erp\Models\Oracle\Proveedor\Proveedor;
use Modules\Erp\Services\CircuitBreaker;
use Modules\Supplier\Entities\SupplierErpCategory;
use Modules\Supplier\Entities\SupplierErpProvider;
use Modules\Supplier\Entities\SupplierFamily;
use Modules\Supplier\Entities\SupplierGroup;
use Modules\Supplier\Entities\SupplierProduct;
use Modules\Supplier\Entities\SupplierProductPrice;
use Modules\Supplier\Entities\SupplierProviderProduct;
use Modules\Supplier\Entities\SupplierSport;
use Modules\Supplier\Entities\SupplierSubfamily;
use Modules\Supplier\Entities\SupplierSyncAudit;

/**
 * Job que monitorea cambios en Oracle de forma CONTINUA en tiempo real
 *
 * Características:
 * - Se ejecuta de forma infinita/continua (loop)
 * - Detecta cambios en Oracle comparando fmodificacion > last_synced_at
 * - Sincroniza automáticamente a Supplier
 * - Procesa: Categorías, Proveedores, Productos, Precios
 * - Usa transaction por batch para integridad
 * - Health checks para evitar que el worker se cuelgue
 * - Logging detallado de cambios detectados
 *
 * Uso:
 * ```bash
 * # Iniciar el monitor en un worker dedicado
 * php artisan erp:monitor-oracle
 *
 * # El job se ejecutará continuamente sin pausas (solo retrasos configurables)
 * ```
 *
 * Configuración recomendada (Horizon/Supervisord):
 * - Worker dedicado solo para este job
 * - Timeout: nunca (o muy alto: 3600+)
 * - numprocs: 1 (solo un proceso)
 * - queue: oracle-monitor
 */
class MonitorOracleChanges implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * El job NUNCA debería expirar (se ejecuta indefinidamente)
     * Set a very high timeout
     */
    public $timeout = 3600; // 1 hora timeout máximo, pero se redespacha antes

    /**
     * Número máximo de reintentos (rara vez necesario, el job se redispacha a sí mismo)
     */
    public $tries = 2;

    /**
     * Queue dedicado — note we can't declare `public $queue = '...'` here because
     * the Queueable trait already declares `public $queue` and PHP 8.4 treats
     * differing default values as incompatible composition. We set it via
     * onQueue() in the constructor instead.
     */
    private const QUEUE_NAME = 'oracle-monitor';

    /**
     * Circuit Breaker instance for resilience
     */
    protected CircuitBreaker $circuitBreaker;

    /**
     * Concurrency lock instance
     */
    protected ?Lock $lock = null;

    /**
     * Flag to signal graceful shutdown
     */
    protected bool $shouldStop = false;

    /**
     * Maximum cycles before restarting job (memory management)
     */
    protected int $maxCycles = 1000;

    /**
     * Current cycle number for audit trail
     */
    protected int $currentCycleNumber = 0;

    /**
     * Crear instancia del job
     */
    public function __construct()
    {
        $this->circuitBreaker = app(CircuitBreaker::class);
        $this->onQueue(self::QUEUE_NAME);
    }

    /**
     * Ejecutar el monitoreo de cambios en Oracle
     *
     * Algoritmo (MEJORADO):
     * 1. Acquire concurrency lock (prevent double processing)
     * 2. Register signal handlers for graceful shutdown (SIGTERM, SIGINT)
     * 3. Loop controlado (max cycles para restart automático)
     * 4. Por cada tipo de entidad (categorías, proveedores, productos, precios):
     *    - Check circuit breaker (skip if Oracle is down)
     *    - Buscar registros donde fmodificacion > last_synced_at
     *    - Sincronizar en chunks con transacciones
     *    - Actualizar last_synced_at
     * 5. Memory management cada 50 ciclos
     * 6. Al terminar un ciclo, esperar 30-60 segundos antes de revisar otra vez
     * 7. Health check: registrar estado cada ciclo
     * 8. Si hay error, loguear pero continuar
     * 9. Graceful shutdown: release lock y log estado final
     */
    public function handle(): void
    {
        // =====================================================================
        // MEJORA #3: CONCURRENCY LOCK - Prevent double processing
        // =====================================================================
        $lockKey = 'oracle_monitor_running';
        $this->lock = Cache::lock($lockKey, 3600); // 1 hour lock

        if (! $this->lock->get()) {
            Log::warning('⚠️ MonitorOracleChanges already running - exiting to prevent double processing', [
                'lock_key' => $lockKey,
            ]);

            return;
        }

        Log::info('🔵 MonitorOracleChanges started - monitoring ERP for real-time changes', [
            'lock_acquired' => true,
            'max_cycles' => $this->maxCycles,
        ]);

        try {
            // =====================================================================
            // MEJORA #1: GRACEFUL SHUTDOWN - Register signal handlers
            // =====================================================================
            $this->registerSignalHandlers();

            // Ciclo controlado de monitoreo
            $cycleNumber = 0;
            $healthCheckInterval = 10; // Log health check cada 10 ciclos

            while (! $this->shouldStop && $cycleNumber < $this->maxCycles) {
                $cycleNumber++;
                $this->currentCycleNumber = $cycleNumber; // For audit trail

                try {
                    $startTime = microtime(true);

                    // =====================================================================
                    // MEJORA #2: CIRCUIT BREAKER - Check if Oracle is available
                    // =====================================================================
                    if ($this->circuitBreaker->isOpen('oracle')) {
                        Log::warning('🔴 Skipping sync cycle - Oracle circuit breaker is OPEN', [
                            'cycle' => $cycleNumber,
                            'circuit_state' => $this->circuitBreaker->getState('oracle'),
                        ]);

                        // Wait longer when circuit is open
                        sleep(60);

                        continue;
                    }

                    // =====================================================================
                    // MEJORA #6: SELECTIVE ENTITY MONITORING
                    // =====================================================================
                    // Monitorear cambios en cada tipo de entidad (si está habilitado en config)
                    $monitoredEntities = config('erp.supplier_sync.monitored_entities', []);

                    $changesCounts = [];

                    if ($monitoredEntities['sports'] ?? true) {
                        $changesCounts['sports'] = $this->monitorSports();
                    }

                    if ($monitoredEntities['categories'] ?? true) {
                        $changesCounts['categories'] = $this->monitorCategories();
                    }

                    if ($monitoredEntities['families'] ?? true) {
                        $changesCounts['families'] = $this->monitorFamilies();
                    }

                    if ($monitoredEntities['subfamilies'] ?? true) {
                        $changesCounts['subfamilies'] = $this->monitorSubfamilies();
                    }

                    if ($monitoredEntities['groups'] ?? true) {
                        $changesCounts['groups'] = $this->monitorGroups();
                    }

                    if ($monitoredEntities['providers'] ?? true) {
                        $changesCounts['providers'] = $this->monitorProviders();
                    }

                    if ($monitoredEntities['products'] ?? true) {
                        $changesCounts['products'] = $this->monitorProducts();
                    }

                    if ($monitoredEntities['provider_products'] ?? true) {
                        $changesCounts['provider_products'] = $this->monitorProviderProducts();
                    }

                    if ($monitoredEntities['prices'] ?? true) {
                        $changesCounts['prices'] = $this->monitorPrices();
                    }

                    // Record success in circuit breaker
                    $this->circuitBreaker->recordSuccess('oracle');

                    $elapsedTime = microtime(true) - $startTime;
                    $totalChanges = array_sum($changesCounts);

                    // Health check: loguear estado periódicamente
                    if ($cycleNumber % $healthCheckInterval === 0) {
                        Log::info('📊 Monitor health check', [
                            'cycle' => $cycleNumber,
                            'elapsed_seconds' => round($elapsedTime, 2),
                            'changes_detected' => $totalChanges,
                            'breakdown' => $changesCounts,
                            'memory_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                            'peak_memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                            'circuit_breaker' => $this->circuitBreaker->getState('oracle'),
                        ]);
                    }

                    // Si hay cambios, loguearlos brevemente
                    if ($totalChanges > 0) {
                        Log::info('✅ Changes synchronized from ERP → Supplier', [
                            'cycle' => $cycleNumber,
                            'total_changes' => $totalChanges,
                            'breakdown' => $changesCounts,
                        ]);
                    }

                    // =====================================================================
                    // MEJORA #1: MEMORY MANAGEMENT - Cleanup every 50 cycles
                    // =====================================================================
                    if ($cycleNumber % 50 === 0) {
                        $this->clearMemory();
                        Log::debug('🧹 Memory cleanup performed', [
                            'cycle' => $cycleNumber,
                            'memory_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                        ]);
                    }

                    // Esperar antes del siguiente ciclo (30-60 segundos configurable)
                    // Esto da tiempo a Oracle de cambiar más registros y reduce carga
                    $waitSeconds = config('erp.supplier_sync.oracle_monitor_interval', 30);
                    sleep($waitSeconds);

                } catch (\Exception $e) {
                    // Record failure in circuit breaker
                    $this->circuitBreaker->recordFailure('oracle');

                    // Si hay error, loguear pero continuar (no detener el monitor)
                    Log::error('❌ Error in MonitorOracleChanges cycle', [
                        'cycle' => $cycleNumber,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'circuit_breaker' => $this->circuitBreaker->getState('oracle'),
                    ]);

                    // Esperar más antes de reintentar (para no saturar logs)
                    sleep(5);
                }
            }

            // =====================================================================
            // Auto-redispatch if stopped by max cycles (not by signal)
            // =====================================================================
            if (! $this->shouldStop) {
                Log::info('🔄 MonitorOracleChanges reached max cycles - restarting job', [
                    'completed_cycles' => $cycleNumber,
                    'max_cycles' => $this->maxCycles,
                ]);

                // Redispatch itself to continue monitoring
                self::dispatch();
            } else {
                Log::info('🛑 MonitorOracleChanges stopped gracefully by signal', [
                    'completed_cycles' => $cycleNumber,
                    'reason' => 'shutdown_signal',
                ]);
            }

        } finally {
            // =====================================================================
            // MEJORA #3: Release concurrency lock
            // =====================================================================
            if ($this->lock) {
                $this->lock->release();
                Log::debug('🔓 Concurrency lock released', [
                    'lock_key' => $lockKey,
                ]);
            }
        }
    }

    /**
     * Register signal handlers for graceful shutdown (MEJORA #1)
     *
     * Handles SIGTERM (systemd stop) and SIGINT (Ctrl+C) to gracefully
     * finish current cycle and release resources before exiting.
     */
    protected function registerSignalHandlers(): void
    {
        if (! extension_loaded('pcntl')) {
            Log::warning('⚠️ PCNTL extension not loaded - graceful shutdown disabled');

            return;
        }

        pcntl_async_signals(true);

        $shutdownHandler = function (int $sig) {
            $name = match ($sig) {
                SIGTERM => 'SIGTERM',
                SIGINT  => 'SIGINT',
                SIGHUP  => 'SIGHUP',
                default => "signal {$sig}",
            };
            Log::info("Signal {$name} received - initiating graceful shutdown");
            $this->shouldStop = true;
        };

        pcntl_signal(SIGTERM, $shutdownHandler);
        pcntl_signal(SIGINT, $shutdownHandler);
        // SIGHUP arrives when systemd / Horizon reloads config or the controlling
        // terminal closes — treat it as a soft stop so the worker rotates cleanly.
        pcntl_signal(SIGHUP, $shutdownHandler);

        Log::debug('Signal handlers registered for graceful shutdown (TERM/INT/HUP)');
    }

    /**
     * Clear memory and reconnect to databases (MEJORA #1)
     *
     * Performs garbage collection and reconnects to both Oracle and PostgreSQL
     * to prevent connection stale issues and memory leaks.
     */
    protected function clearMemory(): void
    {
        // Force garbage collection
        gc_collect_cycles();

        // Reconnect to all known connections to prevent stale handles. We refresh
        // the default connection plus 'oracle' so this is portable across mysql /
        // pgsql deployments. Previous code hard-coded 'pgsql' which always failed.
        $connections = array_unique(array_filter([
            \DB::getDefaultConnection(),
            'oracle',
        ]));

        foreach ($connections as $name) {
            try {
                \DB::reconnect($name);
            } catch (\Throwable $e) {
                Log::warning('Failed to reconnect database during memory cleanup', [
                    'connection' => $name,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Monitorear cambios en SPORTS (DeporteCl ↔ Sport)
     *
     * @return int Número de registros sincronizados
     */
    protected function monitorSports(): int
    {
        return $this->monitorEntity(
            DeporteCl::class,
            SupplierSport::class,
            'erp_sport_id',
            'iddeporte_cl',
            [
                'descripcion' => 'name',
                'desc_corta' => 'short_name',
                'estado' => 'is_active',
            ]
        );
    }

    /**
     * Monitorear cambios en CATEGORÍAS
     */
    protected function monitorCategories(): int
    {
        return $this->monitorEntity(
            CategoriaCl::class,
            SupplierErpCategory::class,
            'erp_category_id',
            'idcategoria_cl',
            [
                'descripcion' => 'name',
                'desc_corta' => 'short_name',
                'estado' => 'is_active',
                'apareceinfstock' => 'show_in_stock_report',
            ]
        );
    }

    /**
     * Monitorear cambios en FAMILIAS
     */
    protected function monitorFamilies(): int
    {
        return $this->monitorEntity(
            FamiliaCl::class,
            SupplierFamily::class,
            'erp_family_id',
            'idfamilia_cl',
            [
                'descripcion' => 'name',
                'desc_corta' => 'short_name',
                'estado' => 'is_active',
                'sonarmas' => 'is_weapons',
                'sonarmasfogueo' => 'is_blank_weapons',
                'soncartuchos' => 'is_cartridges',
            ]
        );
    }

    /**
     * Monitorear cambios en SUBFAMILIAS
     */
    protected function monitorSubfamilies(): int
    {
        return $this->monitorEntity(
            SubfamiliaCl::class,
            SupplierSubfamily::class,
            'erp_subfamily_id',
            'idsubfamilia_cl',
            [
                'descripcion' => 'name',
                'desc_corta' => 'short_name',
                'estado' => 'is_active',
                'escartucheria' => 'is_ammunition',
                'esmunicionmetalica' => 'is_metal_ammunition',
                'mostrarlotes' => 'show_lots',
            ]
        );
    }

    /**
     * Monitorear cambios en GRUPOS
     */
    protected function monitorGroups(): int
    {
        return $this->monitorEntity(
            GrupoCl::class,
            SupplierGroup::class,
            'erp_group_id',
            'idgrupo_cl',
            [
                'descripcion' => 'name',
                'desc_corta' => 'short_name',
                'estado' => 'is_active',
                'prefijo' => 'prefix',
                'prox_num' => 'next_number',
                'excluir_pedir_a_tienda' => 'exclude_store_request',
                'pedir_numero_serie' => 'require_serial_number',
                'intrastat' => 'intrastat',
            ]
        );
    }

    /**
     * Monitorear cambios en PROVEEDORES
     */
    protected function monitorProviders(): int
    {
        return $this->monitorEntity(
            Proveedor::class,
            SupplierErpProvider::class,
            'erp_provider_id',
            'idproveedor',
            [
                'nombre' => 'name',
                'email' => 'email',
                'telefono1' => 'phone',
                'web' => 'website',
                'cif' => 'tax_id',
                'portes' => 'shipping_cost',
                'dto' => 'discount_percent',
                'dias_servir_parcial' => 'partial_delivery_days',
            ]
        );
    }

    /**
     * Monitorear cambios en PRODUCTOS
     */
    protected function monitorProducts(): int
    {
        return $this->monitorEntity(
            Articulo::class,
            SupplierProduct::class,
            'erp_product_id',
            'idarticulo',
            [
                'descripcion' => 'name',
                'codigo' => 'code',
                'codbar' => 'barcode',
                'estado' => 'is_active',
                'preciorecomendadoprov' => 'recommended_price',
                'preciomedio' => 'average_cost',
                'precioultcompra' => 'last_purchase_cost',
            ]
        );
    }

    /**
     * Monitorear cambios en PRODUCTO-PROVEEDOR (Artiprov)
     */
    protected function monitorProviderProducts(): int
    {
        return $this->monitorEntity(
            Artiprov::class,
            SupplierProviderProduct::class,
            'erp_artiprov_id',
            'idartiprov',
            [
                'codigo' => 'provider_code',
                'codigo2' => 'provider_code_alt',
                'descripcion' => 'provider_name',
                'estado' => 'is_active',
                'pcosto' => 'current_cost',
                'dto1' => 'discount1',
                'dto2' => 'discount2',
                'pordefecto' => 'is_default',
                'ean13' => 'ean13',
                'upc' => 'upc',
            ]
        );
    }

    /**
     * Monitorear cambios en PRECIOS
     */
    protected function monitorPrices(): int
    {
        return $this->monitorEntity(
            ArtiprovTarifapro::class,
            SupplierProductPrice::class,
            'erp_price_id',
            'idartiprov_tarifapro',
            [
                'fecha' => 'effective_date',
                'pcosto' => 'cost',
                'dto1' => 'discount1',
                'dto2' => 'discount2',
                'estado' => 'is_active',
            ]
        );
    }

    /**
     * Función genérica para monitorear cambios en cualquier entidad
     *
     * Algoritmo:
     * 1. Buscar registros en Oracle donde fmodificacion > last_synced_at en Supplier
     * 2. Procesar en chunks
     * 3. Para cada registro: mapear campos Oracle → Supplier
     * 4. Usar UpdateOrCreate para sincronizar
     * 5. Actualizar last_synced_at
     *
     * @param  string  $oracleModel  Modelo Oracle a monitorear
     * @param  string  $supplierModel  Modelo Supplier destino
     * @param  string  $supplierForeignKey  FK en tabla Supplier
     * @param  string  $oraclePrimaryKey  PK en tabla Oracle
     * @param  array  $fieldMapping  Mapeo Oracle → Supplier
     * @return int Número de registros sincronizados
     */
    protected function monitorEntity(
        string $oracleModel,
        string $supplierModel,
        string $supplierForeignKey,
        string $oraclePrimaryKey,
        array $fieldMapping
    ): int {
        // =====================================================================
        // MEJORA #4: AUDIT TRAIL - Track sync metrics
        // =====================================================================
        $startTime = microtime(true);
        $memoryStart = memory_get_usage(true);

        $syncedCount = 0;
        $failedCount = 0;
        $skippedCount = 0;
        $errors = [];

        try {
            // Obtener timestamp del último sync (o muy antiguo si es primera vez)
            $lastSyncTime = Cache::get("monitor_last_sync_{$oracleModel}", now()->subYears(10));

            // =====================================================================
            // MEJORA #9: LAZY LOADING - Use lazy() instead of get()
            // =====================================================================
            // Buscar registros en Oracle que han sido modificados
            $changes = $oracleModel::on('oracle')
                ->where('fmodificacion', '>', $lastSyncTime)
                ->where('fbaja', null) // Ignorar soft-deletes en Oracle
                ->orderBy('fmodificacion')
                ->lazy(); // LAZY LOADING: Process records one-by-one without loading all into memory

            $totalChanges = 0;
            $maxFmodInChunk = null;

            // Procesar en chunks
            $chunkSize = config('erp.supplier_sync.oracle_monitor_chunk_size', 100);
            foreach ($changes->chunk($chunkSize) as $chunk) {
                $totalChanges += $chunk->count();
                $maxFmodInChunk = null;

                \DB::transaction(function () use ($chunk, $supplierModel, $supplierForeignKey, $oraclePrimaryKey, $fieldMapping, &$syncedCount, &$failedCount, &$errors, &$maxFmodInChunk) {
                    foreach ($chunk as $oracleRecord) {
                        // Track the high-water mark inside the transaction so we
                        // only advance the sync cursor for records that actually
                        // committed. If the transaction rolls back, $maxFmodInChunk
                        // never gets propagated to the cache below.
                        if ($oracleRecord->fmodificacion && (! $maxFmodInChunk || $oracleRecord->fmodificacion > $maxFmodInChunk)) {
                            $maxFmodInChunk = $oracleRecord->fmodificacion;
                        }
                        try {
                            // Preparar datos para Supplier
                            $supplierData = [
                                $supplierForeignKey => $oracleRecord->{$oraclePrimaryKey},
                                'erp_created_at' => $oracleRecord->fcreacion,
                                'erp_updated_at' => $oracleRecord->fmodificacion,
                                'erp_deleted_at' => $oracleRecord->fbaja,
                                'last_synced_at' => now(),
                            ];

                            // Mapear campos
                            foreach ($fieldMapping as $oracleField => $supplierField) {
                                if (isset($oracleRecord->{$oracleField})) {
                                    $supplierData[$supplierField] = $oracleRecord->{$oracleField};
                                }
                            }

                            // Sincronizar en Supplier
                            $supplierModel::updateOrCreate(
                                [$supplierForeignKey => $oracleRecord->{$oraclePrimaryKey}],
                                $supplierData
                            );

                            $syncedCount++;

                        } catch (\Exception $e) {
                            $failedCount++;
                            $errorMsg = substr($e->getMessage(), 0, 200); // Truncate long errors

                            $errors[] = [
                                'oracle_id' => $oracleRecord->{$oraclePrimaryKey},
                                'error' => $errorMsg,
                            ];

                            Log::error('Failed to sync record in MonitorOracleChanges', [
                                'oracle_model' => $oracleModel,
                                'oracle_id' => $oracleRecord->{$oraclePrimaryKey},
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                });

                // Advance the sync cursor only after the chunk commits successfully.
                // If the next chunk explodes mid-flight, we resume from here on the
                // next cycle instead of reprocessing the whole window from scratch.
                if ($maxFmodInChunk) {
                    Cache::put("monitor_last_sync_{$oracleModel}", $maxFmodInChunk, 24 * 60 * 60);
                }
            }

            // Final fallback: if no records were processed but we got here without
            // exceptions, bump the cursor so the next cycle doesn't re-scan the
            // same empty window.
            if ($totalChanges === 0) {
                Cache::put("monitor_last_sync_{$oracleModel}", now(), 24 * 60 * 60);
            }

            // =====================================================================
            // MEJORA #4: AUDIT TRAIL - Create audit record
            // =====================================================================
            $elapsedTime = microtime(true) - $startTime;
            $memoryUsed = (memory_get_usage(true) - $memoryStart) / 1024 / 1024; // MB
            $peakMemory = memory_get_peak_usage(true) / 1024 / 1024; // MB

            if ($totalChanges > 0) {
                $entityType = $this->getEntityTypeFromModel($oracleModel);

                SupplierSyncAudit::create([
                    'uid' => \Str::ulid(),
                    'entity_type' => $entityType,
                    'sync_direction' => 'erp_to_supplier',
                    'records_synced' => $syncedCount,
                    'records_failed' => $failedCount,
                    'records_skipped' => $skippedCount,
                    'elapsed_seconds' => round($elapsedTime, 2),
                    'memory_mb' => round($memoryUsed, 2),
                    'peak_memory_mb' => round($peakMemory, 2),
                    'metadata' => [
                        'oracle_model' => class_basename($oracleModel),
                        'supplier_model' => class_basename($supplierModel),
                        'total_changes_detected' => $totalChanges,
                        'errors' => $failedCount > 0 ? array_slice($errors, 0, 5) : null, // First 5 errors only
                    ],
                    'cycle_number' => property_exists($this, 'currentCycleNumber') ? $this->currentCycleNumber : null,
                    'triggered_by' => 'monitor_job',
                    'synced_at' => now(),
                ]);
            }

        } catch (\Exception $e) {
            $failedCount++;

            Log::error('Error monitoring entity in MonitorOracleChanges', [
                'oracle_model' => $oracleModel,
                'supplier_model' => $supplierModel,
                'error' => $e->getMessage(),
            ]);

            // Create audit record even on failure
            $elapsedTime = microtime(true) - $startTime;
            $entityType = $this->getEntityTypeFromModel($oracleModel);

            SupplierSyncAudit::create([
                'uid' => \Str::ulid(),
                'entity_type' => $entityType,
                'sync_direction' => 'erp_to_supplier',
                'records_synced' => $syncedCount,
                'records_failed' => $failedCount,
                'records_skipped' => $skippedCount,
                'elapsed_seconds' => round($elapsedTime, 2),
                'metadata' => [
                    'oracle_model' => class_basename($oracleModel),
                    'supplier_model' => class_basename($supplierModel),
                    'critical_error' => $e->getMessage(),
                ],
                'cycle_number' => property_exists($this, 'currentCycleNumber') ? $this->currentCycleNumber : null,
                'triggered_by' => 'monitor_job',
                'synced_at' => now(),
            ]);
        }

        return $syncedCount;
    }

    /**
     * Get entity type name from Oracle model class name
     */
    protected function getEntityTypeFromModel(string $oracleModel): string
    {
        return match (class_basename($oracleModel)) {
            'DeporteCl' => 'sports',
            'CategoriaCl' => 'categories',
            'FamiliaCl' => 'families',
            'SubfamiliaCl' => 'subfamilies',
            'GrupoCl' => 'groups',
            'Proveedor' => 'providers',
            'Articulo' => 'products',
            'Artiprov' => 'provider_products',
            'ArtiprovTarifapro' => 'prices',
            default => strtolower(class_basename($oracleModel)),
        };
    }

    /**
     * Se llama si el job falla permanentemente
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('❌ MonitorOracleChanges job FAILED PERMANENTLY', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'note' => 'The real-time monitor has stopped. Please restart it manually or via supervisor.',
        ]);
    }
}
