<?php

namespace App\Models\Warehouse;

use App\Library\Traits\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseInventoryOperation extends Model
{
    use HasFactory, HasUid;

    protected $table = 'warehouse_inventory_operations';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->uid) {
                $model->uid = \Illuminate\Support\Str::uuid();
            }
        });
    }

    protected $fillable = [
        'uid',
        'warehouse_id',
        'user_id',
        'started_at',
        'closed_at',
        'closed_by',
        'description',
        'status',
        'total_items',
        'validated_items',
        'discrepancy_items',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'closed_at' => 'datetime',
        'total_items' => 'integer',
        'validated_items' => 'integer',
        'discrepancy_items' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Estados de operación
     */
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * ===============================================
     * RELACIONES
     * ===============================================
     */

    /**
     * Operación pertenece a un Warehouse
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * Usuario que inició la operación
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo('App\Models\User', 'user_id');
    }

    /**
     * Usuario que cerró la operación
     */
    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo('App\Models\User', 'closed_by');
    }

    /**
     * Items que forman parte de esta operación
     */
    public function items(): HasMany
    {
        return $this->hasMany(WarehouseOperationItem::class, 'operation_id');
    }

    /**
     * ===============================================
     * SCOPES
     * ===============================================
     */

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_IN_PROGRESS]);
    }

    public function scopeByWarehouse($query, $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    /**
     * ===============================================
     * MÉTODOS
     * ===============================================
     */

    /**
     * Obtener operación activa (no cerrada) de un warehouse
     */
    public static function getActiveByWarehouse($warehouseId): ?self
    {
        return self::where('warehouse_id', $warehouseId)
            ->whereNull('closed_at')
            ->first();
    }

    /**
     * Agregar items a la operación
     */
    public function addSlots($slotIds): void
    {
        foreach ($slotIds as $slotId) {
            WarehouseOperationItem::firstOrCreate(
                [
                    'operation_id' => $this->id,
                    'slot_id' => $slotId,
                ],
                [
                    'uid' => \Illuminate\Support\Str::uuid(),
                    'expected_quantity' => WarehouseInventorySlot::find($slotId)->quantity ?? 0,
                    'status' => 'pending',
                ]
            );
        }

        $this->updateStats();
    }

    /**
     * Validar un item de la operación
     */
    public function validateItem($itemId, int $scannedQuantity, ?int $userId = null): bool
    {
        $item = $this->items()->findOrFail($itemId);
        $item->validate($scannedQuantity, $userId);

        $this->updateStats();

        return $this->checkCompletion();
    }

    /**
     * Actualizar estadísticas de la operación
     */
    public function updateStats(): void
    {
        $totalItems = $this->items()->count();
        $validatedItems = $this->items()->where('status', 'validated')->count();
        $discrepancyItems = $this->items()->where('status', 'discrepancy')->count();

        $this->update([
            'total_items' => $totalItems,
            'validated_items' => $validatedItems,
            'discrepancy_items' => $discrepancyItems,
        ]);
    }

    /**
     * Verificar si la operación está completada
     */
    public function checkCompletion(): bool
    {
        if ($this->total_items === 0) {
            return false;
        }

        // Operación completada si todos los items están validados o tienen discrepancia registrada
        $pendingItems = $this->items()
            ->where('status', 'pending')
            ->count();

        if ($pendingItems === 0) {
            // Todos los items han sido procesados
            $this->update(['status' => self::STATUS_COMPLETED]);
            return true;
        }

        return false;
    }

    /**
     * Marcar como en progreso
     */
    public function startProgress(): void
    {
        if ($this->status === self::STATUS_PENDING) {
            $this->update(['status' => self::STATUS_IN_PROGRESS]);
        }
    }

    /**
     * Cerrar la operación
     */
    public function close(?int $userId = null): bool
    {
        try {
            $this->update([
                'closed_at' => now(),
                'closed_by' => $userId ?? auth()->id(),
                'status' => self::STATUS_COMPLETED,
            ]);

            return true;
        } catch (\Exception $e) {
            \Log::error('Error al cerrar operación de inventario', [
                'operation_id' => $this->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Cancelar operación
     */
    public function cancel(?string $reason = null, ?int $userId = null): bool
    {
        try {
            $this->update([
                'closed_at' => now(),
                'closed_by' => $userId ?? auth()->id(),
                'status' => self::STATUS_CANCELLED,
                'description' => $reason ? $this->description . "\n[CANCELADA: $reason]" : $this->description,
            ]);

            return true;
        } catch (\Exception $e) {
            \Log::error('Error al cancelar operación', [
                'operation_id' => $this->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Obtener porcentaje de progreso
     */
    public function getProgressPercentage(): int
    {
        if ($this->total_items === 0) {
            return 0;
        }

        $processedItems = $this->items()
            ->whereIn('status', ['validated', 'discrepancy'])
            ->count();

        return (int) round(($processedItems / $this->total_items) * 100);
    }

    /**
     * Obtener resumen de la operación
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'uid' => $this->uid,
            'warehouse' => [
                'id' => $this->warehouse?->id,
                'name' => $this->warehouse?->name,
            ],
            'status' => $this->status,
            'started_at' => $this->started_at,
            'closed_at' => $this->closed_at,
            'progress' => [
                'total_items' => $this->total_items,
                'validated_items' => $this->validated_items,
                'discrepancy_items' => $this->discrepancy_items,
                'pending_items' => $this->total_items - $this->validated_items - $this->discrepancy_items,
                'percentage' => $this->getProgressPercentage(),
            ],
            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
            ],
            'closed_by_user' => $this->closedByUser ? [
                'id' => $this->closedByUser->id,
                'name' => $this->closedByUser->name,
            ] : null,
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
                'description' => $this->description,
                'items' => $this->items()
                    ->with('slot.section.location', 'slot.product')
                    ->get()
                    ->map(fn($item) => $item->getSummary()),
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ]
        );
    }

    /**
     * Obtener estado en color
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => '#FFC107',      // Yellow
            self::STATUS_IN_PROGRESS => '#17A2B8',  // Cyan
            self::STATUS_COMPLETED => '#28A745',    // Green
            self::STATUS_CANCELLED => '#DC3545',    // Red
            default => '#6C757D',                   // Gray
        };
    }

    /**
     * Obtener etiqueta de estado
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pendiente',
            self::STATUS_IN_PROGRESS => 'En Progreso',
            self::STATUS_COMPLETED => 'Completada',
            self::STATUS_CANCELLED => 'Cancelada',
            default => 'Desconocido',
        };
    }
}
