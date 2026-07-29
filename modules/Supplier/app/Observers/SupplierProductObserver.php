<?php

namespace Modules\Supplier\Observers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Supplier\Events\SupplierProductUpdated;
use Modules\Supplier\Models\Product\Product;

/**
 * Observer que dispara eventos cuando un producto es modificado
 *
 * Similar a SupplierProductPriceObserver pero para productos
 */
class SupplierProductObserver
{
    /**
     * Campos que se pueden sincronizar a ERP
     */
    protected array $syncableFields = [
        'name',
        'code',
        'available',
        'web_published',
        'category_id',
        'erp_category_id',
        'erp_model_id',
    ];

    /**
     * Se llama cuando un producto es actualizado
     */
    public function updated(Product $product): void
    {
        // Prevención de loops
        $syncInProgressKey = "sync_in_progress_product_{$product->id}";
        if (Cache::has($syncInProgressKey)) {
            Log::debug('Product update skipped - sync in progress from ERP', [
                'product_id' => $product->id,
            ]);

            return;
        }

        // Detectar qué campos cambiaron
        $dirtyFields = array_keys($product->getDirty());

        // Filtrar solo campos sincronizables
        $changedSyncableFields = array_intersect($dirtyFields, $this->syncableFields);

        // Si no hay campos sincronizables, no hacer nada
        if (empty($changedSyncableFields)) {
            Log::debug('Product update - no syncable fields changed', [
                'product_id' => $product->id,
                'changed_fields' => $dirtyFields,
            ]);

            return;
        }

        Log::info('Product updated - dispatching sync event', [
            'product_id' => $product->id,
            'erp_id' => $product->erp_id,
            'changed_syncable_fields' => $changedSyncableFields,
        ]);

        $userId = auth()->id();
        if ($userId === null) {
            Log::warning('SupplierProductObserver: no authenticated user; event attributed to system', [
                'product_id' => $product->id,
            ]);
        }

        // Disparar evento
        SupplierProductUpdated::dispatch(
            $product,
            $changedSyncableFields,
            $userId ?? 0,
            request()?->ip() ?? '127.0.0.1'
        );
    }

    /**
     * Se llama cuando un producto es creado
     */
    public function created(Product $product): void
    {
        // No disparar eventos en creación
    }

    /**
     * Se llama cuando un producto es eliminado (soft delete)
     */
    public function deleted(Product $product): void
    {
        Log::info('Product soft deleted', [
            'product_id' => $product->id,
            'erp_id' => $product->erp_id,
        ]);
    }

    /**
     * Se llama cuando un producto es restaurado de soft delete
     */
    public function restored(Product $product): void
    {
        Log::info('Product restored from soft delete', [
            'product_id' => $product->id,
            'erp_id' => $product->erp_id,
        ]);
    }
}
