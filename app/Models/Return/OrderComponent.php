<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'order_item_id',
        'component_id',
        'quantity_required',
        'quantity_reserved',
        'quantity_allocated',
        'quantity_shipped',
        'quantity_missing',
        'status',
        'unit_cost',
        'total_cost',
        'deduction_amount',
        'deduction_type',
        'deduction_applied',
        'is_essential',
        'can_substitute',
        'substitute_component_id',
        'substitute_quantity',
        'serial_numbers',
        'batch_number',
        'expected_date',
        'notes',
        'shipment_tracking',
        'reserved_at',
        'allocated_at',
        'shipped_at',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'deduction_amount' => 'decimal:2',
        'deduction_applied' => 'decimal:2',
        'is_essential' => 'boolean',
        'can_substitute' => 'boolean',
        'serial_numbers' => 'array',
        'shipment_tracking' => 'array',
        'expected_date' => 'date',
        'reserved_at' => 'datetime',
        'allocated_at' => 'datetime',
        'shipped_at' => 'datetime',
    ];

    /**
     * Estados de componente en orden
     */
    const STATUS_PENDING = 'pending';
    const STATUS_RESERVED = 'reserved';
    const STATUS_ALLOCATED = 'allocated';
    const STATUS_PARTIAL = 'partial';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_MISSING = 'missing';
    const STATUS_SUBSTITUTED = 'substituted';

    /**
     * Relación con orden
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relación con item de orden
     */
    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * Relación con componente
     */
    public function component()
    {
        return $this->belongsTo(ProductComponent::class);
    }

    /**
     * Componente sustituto
     */
    public function substituteComponent()
    {
        return $this->belongsTo(ProductComponent::class, 'substitute_component_id');
    }

    /**
     * Items de envío
     */
    public function shipmentItems()
    {
        return $this->hasMany(ComponentShipmentItem::class);
    }

    /**
     * Scope para componentes pendientes
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope para componentes faltantes
     */
    public function scopeMissing($query)
    {
        return $query->where('status', self::STATUS_MISSING);
    }

    /**
     * Scope para componentes esenciales
     */
    public function scopeEssential($query)
    {
        return $query->where('is_essential', true);
    }

    /**
     * Verificar si está completamente enviado
     */
    public function isFullyShipped(): bool
    {
        return $this->quantity_shipped >= $this->quantity_required;
    }

    /**
     * Verificar si está parcialmente enviado
     */
    public function isPartiallyShipped(): bool
    {
        return $this->quantity_shipped > 0 && $this->quantity_shipped < $this->quantity_required;
    }

    /**
     * Obtener cantidad pendiente
     */
    public function getPendingQuantity(): int
    {
        return max(0, $this->quantity_required - $this->quantity_shipped);
    }

    /**
     * Reservar stock del componente
     */
    public function reserveStock(): bool
    {
        $pendingQuantity = $this->getPendingQuantity();

        if ($pendingQuantity <= 0) {
            return true; // Ya completado
        }

        $component = $this->component;

        // Intentar reservar la cantidad total primero
        if ($component->hasStock($pendingQuantity)) {
            if ($component->reserveStock($pendingQuantity, $this->order_id)) {
                $this->update([
                    'quantity_reserved' => $this->quantity_reserved + $pendingQuantity,
                    'status' => self::STATUS_RESERVED,
                    'reserved_at' => now(),
                ]);
                return true;
            }
        }

        // Si no hay stock completo, reservar lo disponible si se permiten envíos parciales
        if ($this->order->allows_partial_shipment && $component->available_stock > 0) {
            $availableQuantity = min($pendingQuantity, $component->available_stock);

            if ($component->reserveStock($availableQuantity, $this->order_id)) {
                $this->update([
                    'quantity_reserved' => $this->quantity_reserved + $availableQuantity,
                    'quantity_missing' => $pendingQuantity - $availableQuantity,
                    'status' => $availableQuantity < $pendingQuantity ? self::STATUS_PARTIAL : self::STATUS_RESERVED,
                    'reserved_at' => now(),
                ]);
                return true;
            }
        }

        // Marcar como faltante
        $this->update([
            'quantity_missing' => $pendingQuantity,
            'status' => self::STATUS_MISSING,
        ]);

        return false;
    }

    /**
     * Asignar stock (separar físicamente)
     */
    public function allocateStock(int $quantity = null): bool
    {
        $quantity = $quantity ?? $this->quantity_reserved;

        if ($quantity > $this->quantity_reserved) {
            return false;
        }

        $this->update([
            'quantity_allocated' => $this->quantity_allocated + $quantity,
            'status' => $this->quantity_allocated + $quantity >= $this->quantity_required
                ? self::STATUS_ALLOCATED
                : self::STATUS_PARTIAL,
            'allocated_at' => now(),
        ]);

        return true;
    }

    /**
     * Marcar como enviado
     */
    public function markAsShipped(int $quantity, array $shipmentData = []): bool
    {
        if ($quantity > $this->quantity_allocated) {
            return false;
        }

        $component = $this->component;

        // Actualizar stock físico
        $component->updateStock(-$quantity, 'out', $this->order_id, 'Enviado en orden');

        // Liberar stock reservado
        $reservedToRelease = min($quantity, $this->quantity_reserved);
        if ($reservedToRelease > 0) {
            $component->releaseStock($reservedToRelease, $this->order_id);
        }

        // Actualizar tracking de envíos
        $tracking = $this->shipment_tracking ?? [];
        $tracking[] = array_merge([
            'quantity' => $quantity,
            'shipped_at' => now(),
            'shipment_id' => null,
        ], $shipmentData);

        $this->update([
            'quantity_shipped' => $this->quantity_shipped + $quantity,
            'quantity_reserved' => max(0, $this->quantity_reserved - $reservedToRelease),
            'quantity_allocated' => max(0, $this->quantity_allocated - $quantity),
            'status' => $this->quantity_shipped + $quantity >= $this->quantity_required
                ? self::STATUS_SHIPPED
                : self::STATUS_PARTIAL,
            'shipment_tracking' => $tracking,
            'shipped_at' => $this->shipped_at ?? now(),
        ]);

        return true;
    }

    /**
     * Aplicar sustitución
     */
    public function applySubstitution(ProductComponent $substitute, int $quantity): bool
    {
        if (!$substitute->hasStock($quantity)) {
            return false;
        }

        // Reservar stock del sustituto
        if (!$substitute->reserveStock($quantity, $this->order_id)) {
            return false;
        }

        $this->update([
            'substitute_component_id' => $substitute->id,
            'substitute_quantity' => $quantity,
            'status' => self::STATUS_SUBSTITUTED,
            'can_substitute' => true,
        ]);

        return true;
    }

    /**
     * Calcular y aplicar deducción
     */
    public function calculateDeduction(): float
    {
        if ($this->quantity_missing <= 0) {
            return 0;
        }

        $component = $this->component;
        $basePrice = $this->orderItem->price;

        $deduction = $component->calculateDeduction($this->quantity_missing, $basePrice);

        $this->update([
            'deduction_amount' => $component->deduction_percentage > 0 ?
                ($basePrice * $component->deduction_percentage) / 100 :
                $component->fixed_deduction_amount,
            'deduction_type' => $component->deduction_percentage > 0 ? 'percentage' : 'fixed_amount',
            'deduction_applied' => $deduction,
        ]);

        return $deduction;
    }

    /**
     * Obtener fecha estimada de disponibilidad
     */
    public function getEstimatedAvailabilityDate()
    {
        if ($this->expected_date) {
            return $this->expected_date;
        }

        // Calcular basado en lead time del componente
        $component = $this->component;
        return now()->addDays($component->getEstimatedLeadTime())->toDateString();
    }

    /**
     * Verificar si puede ser sustituido
     */
    public function canBeSubstituted(): bool
    {
        if (!$this->can_substitute) {
            return false;
        }

        $substitutes = $this->component->getAvailableSubstitutes();
        return $substitutes->isNotEmpty();
    }

    /**
     * Obtener resumen del estado
     */
    public function getStatusSummary(): array
    {
        return [
            'component_code' => $this->component->code,
            'component_name' => $this->component->name,
            'quantity_required' => $this->quantity_required,
            'quantity_reserved' => $this->quantity_reserved,
            'quantity_allocated' => $this->quantity_allocated,
            'quantity_shipped' => $this->quantity_shipped,
            'quantity_missing' => $this->quantity_missing,
            'quantity_pending' => $this->getPendingQuantity(),
            'status' => $this->status,
            'is_essential' => $this->is_essential,
            'is_fully_shipped' => $this->isFullyShipped(),
            'is_partially_shipped' => $this->isPartiallyShipped(),
            'can_substitute' => $this->canBeSubstituted(),
            'deduction_applied' => $this->deduction_applied,
            'estimated_availability' => $this->getEstimatedAvailabilityDate(),
        ];
    }

    /**
     * Buscar componentes alternativos automáticamente
     */
    public function findAlternatives(): array
    {
        $alternatives = [];
        $substitutes = $this->component->getAvailableSubstitutes();

        foreach ($substitutes as $substitution) {
            $substitute = $substitution->substituteComponent;
            $availableQuantity = min($this->getPendingQuantity(), $substitute->available_stock);

            if ($availableQuantity > 0) {
                $alternatives[] = [
                    'component' => $substitute,
                    'substitution' => $substitution,
                    'available_quantity' => $availableQuantity,
                    'cost_difference' => $substitution->cost_difference,
                    'compatibility_level' => $substitution->compatibility_level,
                    'performance_impact' => $substitution->performance_impact,
                ];
            }
        }

        return $alternatives;
    }
}
