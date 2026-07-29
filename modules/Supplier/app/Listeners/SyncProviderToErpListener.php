<?php

namespace Modules\Supplier\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Supplier\Events\SupplierErpProviderUpdated;
use Modules\Supplier\Models\Sync\SyncFailure;
use Modules\Supplier\Services\ErpSyncService;

/**
 * Listener que sincroniza proveedores de Supplier → Oracle en tiempo real
 *
 * Similar a SyncPriceToErpListener pero para proveedores
 */
class SyncProviderToErpListener implements ShouldQueue
{
    use InteractsWithQueue;

    public $tries = 3;

    public $timeout = 30;

    public $backoff = [5, 15, 30];

    public $queue = 'sync';

    public function __construct(
        protected ErpSyncService $erpSyncService
    ) {}

    /**
     * Procesar el evento de cambio de proveedor
     *
     * @throws \Exception
     */
    public function handle(SupplierErpProviderUpdated $event): void
    {
        // Validar que se puede sincronizar
        if (! $event->shouldSyncToErp()) {
            Log::info('Provider sync skipped - no erp_provider_id', [
                'supplier_provider_id' => $event->provider->id,
                'changed_fields' => $event->changedFields,
            ]);

            return;
        }

        // Atomic duplicate prevention (Cache::add). Previous `has() + put()` had
        // a race window: two concurrent workers could both pass `has()` and run
        // the Oracle sync twice for the same provider change.
        $cacheKey = $this->getDuplicateKey($event);
        if (! Cache::add($cacheKey, true, now()->addMinutes(5))) {
            Log::debug('Provider sync skipped - already processing', [
                'supplier_provider_id' => $event->provider->id,
            ]);

            return;
        }

        try {
            // Sincronizar al Oracle
            $this->erpSyncService->syncProviderToOracle(
                $event->provider,
                $event->changedFields
            );

            Log::info('Provider synced to ERP successfully', [
                'supplier_provider_id' => $event->provider->id,
                'erp_id' => $event->provider->erp_id,
                'changed_fields' => $event->changedFields,
                'user_id' => $event->userId,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to sync provider to ERP - will retry', [
                'supplier_provider_id' => $event->provider->id,
                'erp_id' => $event->provider->erp_id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            throw $e;
        }
    }

    /**
     * Se llama cuando el job falla permanentemente
     */
    public function failed(SupplierErpProviderUpdated $event, \Throwable $exception): void
    {
        // Guardar en Dead Letter Queue
        SyncFailure::create([
            'uid' => Str::ulid(),
            'sync_type' => 'provider',
            'supplier_id' => $event->provider->id,
            'erp_id' => $event->provider->erp_id,
            'changed_data' => array_combine($event->changedFields, array_fill(0, count($event->changedFields), null)),
            'error_message' => $exception->getMessage(),
            'retry_count' => $this->attempts(),
        ]);

        Log::critical('Provider sync to ERP FAILED PERMANENTLY', [
            'supplier_provider_id' => $event->provider->id,
            'erp_id' => $event->provider->erp_id,
            'error' => $exception->getMessage(),
            'user_id' => $event->userId,
            'changed_fields' => $event->changedFields,
        ]);
    }

    /**
     * Generar clave de cache para prevenir duplicados
     */
    protected function getDuplicateKey(SupplierErpProviderUpdated $event): string
    {
        $hashData = json_encode([
            'provider_id' => $event->provider->id,
            'changed_fields' => $event->changedFields,
        ]);

        return 'erp_sync_provider_'.md5($hashData);
    }
}
