<?php

namespace Modules\Erp\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceValidationHistory extends Model
{
    protected $table = 'price_validation_history';

    protected $fillable = [
        'price_validation_id',
        'old_status',
        'new_status',
        'old_price',
        'new_price',
        'adjusted_price',
        'action',
        'changed_by',
        'ip_address',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'price_validation_id' => 'integer',
        'old_price' => 'decimal:2',
        'new_price' => 'decimal:2',
        'adjusted_price' => 'decimal:2',
        'metadata' => 'array',
    ];

    /**
     * Relación con la validación principal
     */
    public function validation(): BelongsTo
    {
        return $this->belongsTo(PriceValidation::class, 'price_validation_id');
    }

    /**
     * Obtener descripción legible de la acción
     */
    public function getActionDescriptionAttribute(): string
    {
        return match ($this->action) {
            'created' => 'Validación creada',
            'status_changed' => "Estado cambiado de {$this->old_status} a {$this->new_status}",
            'price_adjusted' => "Precio ajustado de {$this->old_price} a {$this->adjusted_price}",
            'approved' => 'Validación aprobada',
            'rejected' => 'Validación rechazada',
            'applied' => 'Cambios aplicados a Prestashop',
            'reverted' => 'Cambios revertidos',
            'note_added' => 'Nota agregada',
            'batch_processed' => 'Procesado en lote',
            default => 'Acción desconocida',
        };
    }
}
