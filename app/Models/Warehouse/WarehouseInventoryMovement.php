<?php

namespace App\Models\Warehouse;

use App\Library\Traits\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseInventoryMovement extends Model
{
    use HasFactory, HasUid;

    protected $table = 'warehouse_inventory_movements';

    protected $fillable = [
        'uid',
        'slot_id',
        'product_id',
        'movement_type',
        'from_quantity',
        'to_quantity',
        'quantity_delta',
        'from_weight',
        'to_weight',
        'weight_delta',
        'reason',
        'warehouse_id',
        'warehouse_location_item_id',
        'user_id',
        'notes',
        'recorded_at',
    ];

    protected $casts = [
        'from_quantity' => 'integer',
        'to_quantity' => 'integer',
        'quantity_delta' => 'integer',
        'from_weight' => 'decimal:2',
        'to_weight' => 'decimal:2',
        'weight_delta' => 'decimal:2',
        'recorded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * ===============================================
     * CONSTANTES
     * ===============================================
     */

    const TYPE_ADD = 'add';
    const TYPE_SUBTRACT = 'subtract';
    const TYPE_CLEAR = 'clear';
    const TYPE_MOVE = 'move';
    const TYPE_COUNT = 'count';

    /**
     * ===============================================
     * RELACIONES
     * ===============================================
     */

    /**
     * Un movimiento pertenece a una posición (slot)
     */
    public function slot(): BelongsTo
    {
        return $this->belongsTo('App\Models\Warehouse\WarehouseInventorySlot', 'slot_id');
    }

    /**
     * Un movimiento puede referenciar un producto
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo('App\Models\Product\Product', 'product_id');
    }

    /**
     * Un movimiento puede estar vinculado a una operación de inventario
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo('App\Models\Warehouse\Warehouse', 'warehouse_id');
    }

    /**
     * Un movimiento puede vincularse a un item contado
     */
    public function inventarieLocationItem(): BelongsTo
    {
        return $this->belongsTo(
            'App\Models\Warehouse\InventarieLocationItem',
            'warehouse_location_item_id'
        );
    }

    /**
     * Un movimiento es realizado por un usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo('App\Models\User', 'user_id');
    }

    /**
     * ===============================================
     * SCOPES
     * ===============================================
     */

    /**
     * Scope: Movimientos de un slot específico
     */
    public function scopeBySlot($query, $slotId)
    {
        return $query->where('slot_id', $slotId);
    }

    /**
     * Scope: Movimientos de una Inventarie (sede)
     */
    public function scopeByInventarie($query, $inventarieId)
    {
        return $query->where('warehouse_id', $inventarieId);
    }

    /**
     * Scope: Movimientos por tipo
     */
    public function scopeByType($query, $type)
    {
        return $query->where('movement_type', $type);
    }

    /**
     * Scope: Movimientos recientes
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('recorded_at', '>=', now()->subDays($days));
    }

    /**
     * Scope: Movimientos por usuario
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Movimientos de sincronización
     */
    public function scopeSync($query)
    {
        return $query->where('movement_type', self::TYPE_COUNT)
            ->whereNotNull('warehouse_location_item_id');
    }

    /**
     * ===============================================
     * MÉTODOS
     * ===============================================
     */

    /**
     * Obtener etiqueta del tipo de movimiento
     */
    public function getTypeLabel(): string
    {
        return match ($this->movement_type) {
            self::TYPE_ADD => 'Agregar',
            self::TYPE_SUBTRACT => 'Restar',
            self::TYPE_CLEAR => 'Vaciar',
            self::TYPE_MOVE => 'Mover',
            self::TYPE_COUNT => 'Inventario',
            default => 'Desconocido',
        };
    }

    /**
     * Obtener resumen del movimiento
     */
    public function getSummary(): array
    {
        return [
            'type' => $this->movement_type,
            'type_label' => $this->getTypeLabel(),
            'slot_address' => $this->slot?->getAddress(),
            'product' => $this->product?->name ?? 'N/A',
            'quantity' => [
                'from' => $this->from_quantity,
                'to' => $this->to_quantity,
                'delta' => $this->quantity_delta,
            ],
            'weight' => [
                'from' => round($this->from_weight, 2),
                'to' => round($this->to_weight, 2),
                'delta' => round($this->weight_delta ?? 0, 2),
            ],
            'reason' => $this->reason,
            'user' => $this->user?->name,
            'recorded_at' => $this->recorded_at,
        ];
    }

    /**
     * Obtener información completa
     */
    public function getFullInfo(): array
    {
        return array_merge(
            $this->getSummary(),
            [
                'id' => $this->id,
                'uid' => $this->uid,
                'slot_id' => $this->slot_id,
                'product_id' => $this->product_id,
                'warehouse' => $this->warehouse?->name,
                'warehouse_item_id' => $this->warehouse_location_item_id,
                'notes' => $this->notes,
            ]
        );
    }
}
