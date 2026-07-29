<?php

namespace Modules\Supplier\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Supplier\Models\Product\Product;

/**
 * Evento disparado cuando un producto cambia en Supplier
 *
 * Usado por:
 * - SyncProductToErpListener para sincronizar cambios a Oracle
 *
 * Campos sincronizables (ver SupplierProductObserver::$syncableFields):
 * - name, code, available, web_published, category_id, erp_category_id, erp_model_id
 */
class SupplierProductUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public const SYNCABLE_FIELDS = [
        'name',
        'code',
        'available',
        'web_published',
        'category_id',
        'erp_category_id',
        'erp_model_id',
    ];

    public function __construct(
        public Product $product,
        public array $changedFields,
        public int $userId,
        public string $ipAddress,
    ) {}

    /**
     * Validar que debería sincronizarse a ERP
     */
    public function shouldSyncToErp(): bool
    {
        // Solo sincronizar si el producto tiene ID de ERP
        if (empty($this->product->erp_id)) {
            return false;
        }

        // Verificar que hay campos sincronizables que cambiaron
        return count(array_intersect($this->changedFields, self::SYNCABLE_FIELDS)) > 0;
    }

    /**
     * Obtener descripción legible del evento para logging
     */
    public function getDescription(): string
    {
        $fields = implode(', ', $this->changedFields);

        return "Producto #{$this->product->id} modificado (campos: $fields)";
    }
}
