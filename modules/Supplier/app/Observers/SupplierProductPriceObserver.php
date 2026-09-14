<?php

namespace Modules\Supplier\Observers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Supplier\Events\SupplierProductPriceChanged;
use Modules\Supplier\Models\SupplierProductPrice;

/**
 * Observer que dispara eventos cuando un precio de producto es modificado
 *
 * Responsabilidades:
 * - Detectar cambios en campos sincronizables (cost, discount1, discount2, effective_date, is_active)
 * - Filtrar cambios no sincronizables (last_synced_at, erp_updated_at, etc.)
 * - Prevenir loops infinitos mediante cache flags
 * - Disparar evento SupplierProductPriceChanged solo si hay cambios sincronizables
 *
 * Prevención de loops:
 * - Si cache.sync_in_progress_price_{id} existe → skip (ERP está actualizando este precio)
 * - Limpiar flag después de evento procesado
 */
class SupplierProductPriceObserver
{
    /**
     * Campos que se pueden sincronizar a ERP
     */
    protected array $syncableFields = [
        'cost',
        'discount1',
        'discount2',
        'effective_date',
        'is_active',
    ];

    /**
     * Se llama cuando un precio es actualizado
     */
    public function updated(SupplierProductPrice $price): void
    {
        // Prevención de loops: si está en proceso de sync desde ERP, ignorar
        $syncInProgressKey = "sync_in_progress_price_{$price->id}";
        if (Cache::has($syncInProgressKey)) {
            Log::debug('Price update skipped - sync in progress from ERP', [
                'price_id' => $price->id,
            ]);

            return;
        }

        // Detectar qué campos cambiaron
        $dirtyFields = array_keys($price->getDirty());

        // Filtrar solo campos sincronizables
        $changedSyncableFields = array_intersect($dirtyFields, $this->syncableFields);

        // Si no hay campos sincronizables que cambiaron, no hacer nada
        if (empty($changedSyncableFields)) {
            Log::debug('Price update - no syncable fields changed', [
                'price_id' => $price->id,
                'changed_fields' => $dirtyFields,
            ]);

            return;
        }

        Log::info('Price updated - dispatching sync event', [
            'price_id' => $price->id,
            'erp_price_id' => $price->erp_price_id,
            'changed_syncable_fields' => $changedSyncableFields,
        ]);

        // Obtener valores originales para conflictdetection
        $previousValues = $price->getOriginal();

        // Disparar evento
        SupplierProductPriceChanged::dispatch(
            $price,
            $changedSyncableFields,
            $previousValues,
            auth()->id() ?? 0,
            request()?->ip() ?? '127.0.0.1'
        );
    }

    /**
     * Se llama cuando un precio es creado (generalmente desde ERP sync, no aquí)
     */
    public function created(SupplierProductPrice $price): void
    {
        // No disparar eventos en creación, solo en actualización
        // Los precios se crean como parte de la sincronización batch desde ERP
    }

    /**
     * Se llama cuando un precio es eliminado (soft delete)
     */
    public function deleted(SupplierProductPrice $price): void
    {
        // No disparar eventos en soft delete
        Log::info('Price soft deleted', [
            'price_id' => $price->id,
            'erp_price_id' => $price->erp_price_id,
        ]);
    }

    /**
     * Se llama cuando un precio es restaurado de soft delete
     */
    public function restored(SupplierProductPrice $price): void
    {
        Log::info('Price restored from soft delete', [
            'price_id' => $price->id,
            'erp_price_id' => $price->erp_price_id,
        ]);
    }
}
