<?php

namespace Modules\Supplier\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Supplier\Models\SupplierProductPrice;

/**
 * Evento disparado cuando un precio de producto cambia en Supplier
 *
 * Usado por:
 * - SyncPriceToErpListener para sincronizar cambios a Oracle
 *
 * Información incluida:
 * - Objeto SupplierProductPrice modificado
 * - Campos que cambiaron (cost, discount1, discount2, effective_date, is_active)
 * - Valores previos para detección de conflictos
 * - Usuario que hizo el cambio
 * - IP para auditoría
 */
class SupplierProductPriceChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public SupplierProductPrice $price,
        public array $changedFields,
        public ?array $previousValues,
        public int $userId,
        public string $ipAddress,
    ) {}

    /**
     * Validar que debería sincronizarse a ERP
     *
     * Condiciones:
     * - El precio tiene ID de ERP (erp_price_id)
     * - Los campos modificados son sincronizables
     */
    public function shouldSyncToErp(): bool
    {
        // Solo sincronizar si el precio está vinculado a Oracle
        if (empty($this->price->erp_price_id)) {
            return false;
        }

        // Verificar que hay campos sincronizables que cambiaron
        $syncableFields = ['cost', 'discount1', 'discount2', 'effective_date', 'is_active'];
        $hasChangedSyncableField = count(array_intersect($this->changedFields, $syncableFields)) > 0;

        return $hasChangedSyncableField;
    }

    /**
     * Obtener descripción legible del evento para logging
     */
    public function getDescription(): string
    {
        $fields = implode(', ', $this->changedFields);

        return "Precio #{$this->price->id} modificado (campos: $fields)";
    }
}
