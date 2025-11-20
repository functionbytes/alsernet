<?php

namespace App\Models\Warehouse;

use App\Library\Traits\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseOperationItem extends Model
{
    use HasFactory, HasUid;

    protected $table = 'warehouse_operation_items';

    protected $fillable = [
        'uid',
        'operation_id',
        'slot_id',
        'expected_quantity',
        'actual_quantity',
        'difference',
        'status',
        'is_validated',
        'validated_at',
        'validated_by',
        'notes',
    ];

    protected $casts = [
        'expected_quantity' => 'integer',
        'actual_quantity' => 'integer',
        'difference' => 'integer',
        'is_validated' => 'boolean',
        'validated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Operación a la que pertenece este item
     */
    public function operation(): BelongsTo
    {
        return $this->belongsTo(WarehouseInventoryOperation::class, 'operation_id');
    }

    /**
     * Slot de inventario que se está validando
     */
    public function slot(): BelongsTo
    {
        return $this->belongsTo(WarehouseInventorySlot::class, 'slot_id');
    }

    /**
     * Usuario que validó este item
     */
    public function validatedByUser(): BelongsTo
    {
        return $this->belongsTo('App\Models\User', 'validated_by', 'id');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeValidated($query)
    {
        return $query->where('status', 'validated');
    }

    public function scopeWithDiscrepancy($query)
    {
        return $query->where('status', 'discrepancy');
    }

    public function scopeMissing($query)
    {
        return $query->where('status', 'missing');
    }

    /**
     * Helpers
     */
    public function validate(int $actualQuantity, ?int $userId = null): void
    {
        $this->expected_quantity = $this->slot->quantity;
        $this->actual_quantity = $actualQuantity;
        $this->difference = $actualQuantity - $this->expected_quantity;

        // Determinar estado basado en kardex vs quantity
        if ($this->difference === 0) {
            $this->status = 'validated';
        } elseif ($actualQuantity === 0 && $this->expected_quantity > 0) {
            $this->status = 'missing';
        } else {
            $this->status = 'discrepancy';
        }

        $this->is_validated = true;
        $this->validated_at = now();
        $this->validated_by = $userId ?? auth()->id();

        $this->save();
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            'pending' => '#FFC107',      // Yellow
            'validated' => '#28A745',    // Green
            'discrepancy' => '#DC3545',  // Red
            'missing' => '#FD7E14',      // Orange
            default => '#6C757D',        // Gray
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Pendiente',
            'validated' => 'Validado',
            'discrepancy' => 'Discrepancia',
            'missing' => 'Faltante',
            default => 'Desconocido',
        };
    }

    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'uid' => $this->uid,
            'slot' => $this->slot?->getSummary(),
            'expected_quantity' => $this->expected_quantity,
            'actual_quantity' => $this->actual_quantity,
            'difference' => $this->difference,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'status_color' => $this->getStatusColor(),
            'is_validated' => $this->is_validated,
            'validated_at' => $this->validated_at,
            'notes' => $this->notes,
        ];
    }
}
