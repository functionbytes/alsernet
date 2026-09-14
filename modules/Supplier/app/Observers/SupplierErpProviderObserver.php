<?php

namespace Modules\Supplier\Observers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Supplier\Events\SupplierErpProviderUpdated;
use Modules\Supplier\Models\Supplier\Supplier;

class SupplierErpProviderObserver
{
    protected array $syncableFields = ['label', 'email'];

    public function updated(Supplier $provider): void
    {
        // Prevención de loops
        $syncInProgressKey = "sync_in_progress_provider_{$provider->id}";
        if (Cache::has($syncInProgressKey)) {
            Log::debug('Provider update skipped - sync in progress from ERP', [
                'provider_id' => $provider->id,
            ]);

            return;
        }

        // Detectar qué campos cambiaron
        $dirtyFields = array_keys($provider->getDirty());

        // Filtrar solo campos sincronizables
        $changedSyncableFields = array_intersect($dirtyFields, $this->syncableFields);

        // Si no hay campos sincronizables, no hacer nada
        if (empty($changedSyncableFields)) {
            Log::debug('Provider update - no syncable fields changed', [
                'provider_id' => $provider->id,
                'changed_fields' => $dirtyFields,
            ]);

            return;
        }

        Log::info('Provider updated - dispatching sync event', [
            'provider_id' => $provider->id,
            'erp_id' => $provider->erp_id,
            'changed_syncable_fields' => $changedSyncableFields,
        ]);

        // Disparar evento
        SupplierErpProviderUpdated::dispatch(
            $provider,
            $changedSyncableFields,
            auth()->id() ?? 0,
            request()?->ip() ?? '127.0.0.1'
        );
    }

    /**
     * Se llama cuando un proveedor es creado
     */
    public function created(Supplier $provider): void
    {
        // No disparar eventos en creación
    }

    /**
     * Se llama cuando un proveedor es eliminado (soft delete)
     */
    public function deleted(Supplier $provider): void
    {
        Log::info('Provider soft deleted', ['provider_id' => $provider->id, 'erp_id' => $provider->erp_id]);
    }

    public function restored(Supplier $provider): void
    {
        Log::info('Provider restored from soft delete', ['provider_id' => $provider->id, 'erp_id' => $provider->erp_id]);
    }
}
