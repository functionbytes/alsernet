<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'code',
        'sku',
        'description',
        'category',
        'type',
        'quantity_per_product',
        'unit_cost',
        'replacement_cost',
        'weight',
        'dimensions',
        'supplier_sku',
        'supplier_id',
        'lead_time_days',
        'minimum_stock',
        'maximum_stock',
        'reorder_point',
        'current_stock',
        'reserved_stock',
        'is_trackable',
        'has_serial_numbers',
        'is_replaceable',
        'affects_functionality',
        'deduction_percentage',
        'fixed_deduction_amount',
        'compatibility_level',
        'compatible_alternatives',
        'metadata',
        'location',
        'is_active',
    ];

    protected $casts = [
        'dimensions' => 'array',
        'compatible_alternatives' => 'array',
        'metadata' => 'array',
        'unit_cost' => 'decimal:2',
        'replacement_cost' => 'decimal:2',
        'weight' => 'decimal:3',
        'deduction_percentage' => 'decimal:2',
        'fixed_deduction_amount' => 'decimal:2',
        'is_trackable' => 'boolean',
        'has_serial_numbers' => 'boolean',
        'is_replaceable' => 'boolean',
        'affects_functionality' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Tipos de componentes
     */
    const TYPE_ESSENTIAL = 'essential';
    const TYPE_OPTIONAL = 'optional';
    const TYPE_ACCESSORY = 'accessory';
    const TYPE_CONSUMABLE = 'consumable';

    /**
     * Categorías de componentes
     */
    const CATEGORY_ELECTRONICS = 'electronics';
    const CATEGORY_MECHANICAL = 'mechanical';
    const CATEGORY_ACCESSORY = 'accessory';
    const CATEGORY_SOFTWARE = 'software';
    const CATEGORY_PACKAGING = 'packaging';

    /**
     * Relación con producto
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relación con proveedor
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Movimientos de stock
     */
    public function stockMovements()
    {
        return $this->hasMany(ComponentStockMovement::class, 'component_id');
    }

    /**
     * Componentes de órdenes
     */
    public function orderComponents()
    {
        return $this->hasMany(OrderComponent::class);
    }

    /**
     * Sustituciones originales (donde este es el original)
     */
    public function substitutesFor()
    {
        return $this->hasMany(ComponentSubstitution::class, 'original_component_id');
    }

    /**
     * Sustituciones como sustituto (donde este es el sustituto)
     */
    public function substitutesAs()
    {
        return $this->hasMany(ComponentSubstitution::class, 'substitute_component_id');
    }

    /**
     * Retornos de este componente
     */
    public function returns()
    {
        return $this->hasMany(ComponentReturn::class);
    }

    /**
     * Scope para componentes activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para componentes esenciales
     */
    public function scopeEssential($query)
    {
        return $query->where('type', self::TYPE_ESSENTIAL);
    }

    /**
     * Scope para componentes con stock bajo
     */
    public function scopeLowStock($query)
    {
        return $query->whereColumn('current_stock', '<=', 'minimum_stock');
    }

    /**
     * Scope para componentes que necesitan reorden
     */
    public function scopeNeedsReorder($query)
    {
        return $query->whereColumn('current_stock', '<=', 'reorder_point');
    }

    /**
     * Obtener stock disponible
     */
    public function getAvailableStockAttribute(): int
    {
        return max(0, $this->current_stock - $this->reserved_stock);
    }

    /**
     * Verificar si hay stock suficiente
     */
    public function hasStock(int $quantity): bool
    {
        return $this->available_stock >= $quantity;
    }

    /**
     * Reservar stock
     */
    public function reserveStock(int $quantity, string $reference = null): bool
    {
        if (!$this->hasStock($quantity)) {
            return false;
        }

        $this->increment('reserved_stock', $quantity);

        // Registrar movimiento
        $this->recordStockMovement(
            'reservation',
            0, // No cambia el stock físico
            0,
            $quantity,
            $reference,
            'Stock reservado'
        );

        return true;
    }

    /**
     * Liberar stock reservado
     */
    public function releaseStock(int $quantity, string $reference = null): bool
    {
        if ($this->reserved_stock < $quantity) {
            return false;
        }

        $this->decrement('reserved_stock', $quantity);

        // Registrar movimiento
        $this->recordStockMovement(
            'release',
            0, // No cambia el stock físico
            0,
            $quantity,
            $reference,
            'Stock liberado'
        );

        return true;
    }

    /**
     * Actualizar stock físico
     */
    public function updateStock(int $quantity, string $movementType, string $reference = null, string $reason = null): void
    {
        $stockBefore = $this->current_stock;
        $stockAfter = $stockBefore + $quantity;

        $this->update(['current_stock' => $stockAfter]);

        $this->recordStockMovement(
            $movementType,
            $stockBefore,
            $stockAfter,
            $quantity,
            $reference,
            $reason
        );
    }

    /**
     * Registrar movimiento de stock
     */
    protected function recordStockMovement(
        string $type,
        int $stockBefore,
        int $stockAfter,
        int $quantity,
        string $reference = null,
        string $reason = null
    ): void {
        ComponentStockMovement::create([
            'component_id' => $this->id,
            'movement_type' => $type,
            'reference_type' => $reference ? class_basename($reference) : null,
            'reference_id' => is_numeric($reference) ? $reference : null,
            'quantity' => $quantity,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'reason' => $reason,
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * Calcular deducción por faltante
     */
    public function calculateDeduction(int $missingQuantity, decimal $basePrice): float
    {
        if ($missingQuantity <= 0) {
            return 0;
        }

        $percentageDeduction = ($basePrice * $this->deduction_percentage) / 100;
        $fixedDeduction = $this->fixed_deduction_amount;

        return ($percentageDeduction + $fixedDeduction) * $missingQuantity;
    }

    /**
     * Obtener sustitutos disponibles
     */
    public function getAvailableSubstitutes()
    {
        return $this->substitutesFor()
            ->where('is_active', true)
            ->with('substituteComponent')
            ->whereHas('substituteComponent', function ($query) {
                $query->where('is_active', true)
                    ->where('current_stock', '>', 0);
            })
            ->orderBy('priority', 'desc')
            ->orderBy('compatibility_level', 'desc')
            ->get();
    }

    /**
     * Verificar compatibilidad para sustitución
     */
    public function canBeSubstitutedBy(ProductComponent $substitute): bool
    {
        $substitution = ComponentSubstitution::where('original_component_id', $this->id)
            ->where('substitute_component_id', $substitute->id)
            ->where('is_active', true)
            ->first();

        return $substitution !== null;
    }

    /**
     * Obtener información de empaquetado
     */
    public function getPackagingInfo(): array
    {
        return [
            'weight' => $this->weight,
            'dimensions' => $this->dimensions,
            'is_fragile' => $this->metadata['is_fragile'] ?? false,
            'requires_special_handling' => $this->metadata['requires_special_handling'] ?? false,
            'packaging_type' => $this->metadata['packaging_type'] ?? 'standard',
        ];
    }

    /**
     * Verificar si necesita números de serie
     */
    public function requiresSerialNumbers(): bool
    {
        return $this->has_serial_numbers && $this->is_trackable;
    }

    /**
     * Obtener tiempo de entrega estimado
     */
    public function getEstimatedLeadTime(): int
    {
        // Considerar stock actual y tiempo del proveedor
        if ($this->available_stock > 0) {
            return 0; // Disponible inmediatamente
        }

        return $this->lead_time_days;
    }

    /**
     * Verificar si es crítico para la funcionalidad
     */
    public function isCritical(): bool
    {
        return $this->type === self::TYPE_ESSENTIAL && $this->affects_functionality;
    }

    /**
     * Obtener resumen del estado del componente
     */
    public function getStatusSummary(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'current_stock' => $this->current_stock,
            'reserved_stock' => $this->reserved_stock,
            'available_stock' => $this->available_stock,
            'minimum_stock' => $this->minimum_stock,
            'needs_reorder' => $this->current_stock <= $this->reorder_point,
            'is_low_stock' => $this->current_stock <= $this->minimum_stock,
            'estimated_lead_time' => $this->getEstimatedLeadTime(),
            'is_critical' => $this->isCritical(),
        ];
    }
}
