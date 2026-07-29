<?php

namespace Modules\Supplier\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Supplier\Events\SupplierProductPriceChanged;
use Modules\Supplier\Models\Sync\SyncFailure;
use Modules\Supplier\Services\ErpSyncService;

/**
 * Listener que sincroniza precios de Supplier → Oracle en tiempo real
 *
 * Características:
 * - Se encola en cola 'sync' para procesamiento asíncrono
 * - 3 intentos de reintento con backoff exponencial
 * - Prevención de duplicados mediante cache
 * - Detección y manejo de conflictos
 * - Dead Letter Queue para fallos permanentes
 *
 * Flujo:
 * 1. Recibe evento SupplierProductPriceChanged
 * 2. Valida que debería sincronizarse (tiene erp_price_id)
 * 3. Previene duplicados con cache flag
 * 4. Llama ErpSyncService::syncPriceToOracle()
 * 5. Si falla permanentemente: guarda en SyncFailure
 */
class SyncPriceToErpListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Número de reintentos antes de considerar fallido permanentemente
     */
    public $tries = 3;

    /**
     * Timeout en segundos para cada intento
     */
    public $timeout = 30;

    /**
     * Backoff en segundos: [primer intento, segundo, tercero]
     */
    public $backoff = [5, 15, 30];

    /**
     * Cola dedicada para sincronización bidireccional
     */
    public $queue = 'sync';

    public function __construct(
        protected ErpSyncService $erpSyncService
    ) {}

    /**
     * Procesar el evento de cambio de precio
     *
     * @throws \Exception
     */
    public function handle(SupplierProductPriceChanged $event): void
    {
        // Validar que se puede sincronizar
        if (! $event->shouldSyncToErp()) {
            Log::info('Price sync skipped - no erp_price_id', [
                'supplier_price_id' => $event->price->id,
                'changed_fields' => $event->changedFields,
            ]);

            return;
        }

        // Atomic duplicate prevention (Cache::add). Previous `has() + put()` had
        // a race window: two concurrent workers could both pass `has()` and run
        // the Oracle sync twice for the same price change.
        $cacheKey = $this->getDuplicateKey($event);
        if (! Cache::add($cacheKey, true, now()->addMinutes(5))) {
            Log::debug('Price sync skipped - already processing', [
                'supplier_price_id' => $event->price->id,
                'cache_key' => $cacheKey,
            ]);

            return;
        }

        try {
            // Sincronizar al Oracle
            $this->erpSyncService->syncPriceToOracle(
                $event->price,
                $event->changedFields
            );

            Log::info('Price synced to ERP successfully', [
                'supplier_price_id' => $event->price->id,
                'erp_price_id' => $event->price->erp_price_id,
                'changed_fields' => $event->changedFields,
                'user_id' => $event->userId,
                'ip_address' => $event->ipAddress,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to sync price to ERP - will retry', [
                'supplier_price_id' => $event->price->id,
                'erp_price_id' => $event->price->erp_price_id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // Re-lanzar para que Laravel reintente
            throw $e;
        }
    }

    /**
     * Se llama cuando el job falla permanentemente (después de $tries intentos)
     *
     * Guarda el registro en Dead Letter Queue para diagnóstico y retry manual
     */
    public function failed(SupplierProductPriceChanged $event, \Throwable $exception): void
    {
        // Guardar en Dead Letter Queue
        SyncFailure::create([
            'uid' => Str::ulid(),
            'sync_type' => 'price',
            'supplier_id' => $event->price->id,
            'erp_id' => $event->price->erp_price_id,
            'changed_data' => array_combine($event->changedFields, array_fill(0, count($event->changedFields), null)),
            'error_message' => $exception->getMessage().' (Stack: '.get_class($exception).')',
            'retry_count' => $this->attempts(),
        ]);

        Log::critical('Price sync to ERP FAILED PERMANENTLY', [
            'supplier_price_id' => $event->price->id,
            'erp_price_id' => $event->price->erp_price_id,
            'error' => $exception->getMessage(),
            'exception_class' => get_class($exception),
            'user_id' => $event->userId,
            'changed_fields' => $event->changedFields,
            'note' => 'Registrado en supplier_sync_failures para retry manual',
        ]);
    }

    /**
     * Generar clave de cache para prevenir duplicados
     *
     * Incluye: precio + campos modificados = si exactamente lo mismo, es duplicado
     */
    protected function getDuplicateKey(SupplierProductPriceChanged $event): string
    {
        $hashData = json_encode([
            'price_id' => $event->price->id,
            'changed_fields' => $event->changedFields,
            'current_values' => array_intersect_key(
                $event->price->toArray(),
                array_flip($event->changedFields)
            ),
        ]);

        return 'erp_sync_price_'.md5($hashData);
    }
}
