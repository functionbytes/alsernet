<?php

namespace App\Models\Warehouse;

use App\Library\Traits\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseInventorySlot extends Model
{
    use HasFactory, HasUid;

    protected $table = 'warehouse_inventory_slots';
    protected $primaryKey = 'id';
    protected $keyType = 'int';
    public $incrementing = true;

    /**
     * Campos que pueden ser asignados masivamente
     */
    protected $fillable = [
        'uid',
        'section_id',
        'product_id',
        'quantity',
        'kardex',
        'is_occupied',
        'last_movement',
        'last_section_id',
    ];

    /**
     * Casteo de tipos
     */
    protected $casts = [
        'quantity' => 'integer',
        'kardex' => 'integer',
        'is_occupied' => 'boolean',
        'last_movement' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * ===============================================
     * RELACIONES
     * ===============================================
     */

    /**
     * El slot pertenece a una Sección de Ubicación
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo('App\Models\Warehouse\WarehouseLocationSection', 'section_id', 'id');
    }

    /**
     * El slot pertenece a una Ubicación (a través de Sección)
     */
    public function location(): \Illuminate\Database\Eloquent\Relations\HasOneThrough
    {
        return $this->hasOneThrough(
            'App\Models\Warehouse\WarehouseLocation',
            'App\Models\Warehouse\WarehouseLocationSection',
            'id',           // Foreign key on WarehouseLocationSection table
            'id',           // Foreign key on WarehouseLocation table
            'section_id',   // Local key on WarehouseInventorySlot table
            'location_id'   // Foreign key on WarehouseLocationSection table
        );
    }

    /**
     * El slot puede contener un producto
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo('App\Models\Product\Product', 'product_id', 'id');
    }

    /**
     * Sección anterior donde estaba el producto
     */
    public function lastSection(): BelongsTo
    {
        return $this->belongsTo('App\Models\Warehouse\WarehouseLocationSection', 'last_section_id', 'id');
    }

    /**
     * Historial de movimientos del slot
     */
    public function movements(): HasMany
    {
        return $this->hasMany('App\Models\Warehouse\WarehouseInventoryMovement', 'slot_id');
    }

    /**
     * ===============================================
     * SCOPES
     * ===============================================
     */

    /**
     * Scope: Solo slots ocupados
     */
    public function scopeOccupied($query)
    {
        return $query->where('quantity', '>', 0);
    }

    /**
     * Scope: Solo slots libres
     */
    public function scopeAvailable($query)
    {
        return $query->where('quantity', '=', 0);
    }

    /**
     * Scope: Buscar por sección
     */
    public function scopeBySection($query, $sectionId)
    {
        return $query->where('section_id', $sectionId);
    }

    /**
     * Scope: Buscar por producto
     */
    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope: Búsqueda general
     */
    public function scopeSearch($query, $search)
    {
        return $query->where('uid', 'like', "%{$search}%")
            ->orWhereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
    }

    /**
     * Scope: Slots con bajo stock
     */
    public function scopeLowStock($query, $threshold = 10)
    {
        return $query->where('quantity', '>', 0)
            ->where('quantity', '<=', $threshold);
    }

    /**
     * ===============================================
     * MÉTODOS HELPERS
     * ===============================================
     */

    /**
     * Obtener la dirección del slot en formato legible
     */
    public function getAddress(): string
    {
        $location = $this->section?->location;
        $floor = $location?->floor;
        $warehouse = $floor?->warehouse;

        return implode(' / ', array_filter([
            $warehouse?->name ?? 'N/A',
            $floor?->name ?? 'N/A',
            $location?->code ?? 'N/A',
            $this->section?->code ?? 'N/A',
            'L' . $this->section?->level ?? 'N/A',
        ]));
    }

    /**
     * Verificar si el slot está ocupado
     */
    public function isOccupied(): bool
    {
        return $this->quantity > 0;
    }

    /**
     * Verificar si el slot está disponible
     */
    public function isAvailable(): bool
    {
        return $this->quantity === 0;
    }

    /**
     * Obtener información de la ubicación
     */
    public function getLocation()
    {
        return $this->section?->location;
    }

    /**
     * Agregar cantidad al slot
     */
    public function addQuantity(int $amount, ?string $reason = null, ?int $userId = null): bool
    {
        $oldQuantity = $this->quantity;
        $newQuantity = $oldQuantity + $amount;

        $this->update([
            'quantity' => $newQuantity,
            'last_movement' => now(),
        ]);

        // Registrar movimiento en auditoría si existe el modelo
        if (class_exists('App\Models\Warehouse\WarehouseInventoryMovement')) {
            WarehouseInventoryMovement::create([
                'slot_id' => $this->id,
                'product_id' => $this->product_id,
                'movement_type' => 'add',
                'from_quantity' => $oldQuantity,
                'to_quantity' => $newQuantity,
                'quantity_delta' => $amount,
                'reason' => $reason ?? 'Manual',
                'user_id' => $userId ?? auth()->id(),
                'recorded_at' => now(),
            ]);
        }

        return true;
    }

    /**
     * Restar cantidad del slot
     */
    public function subtractQuantity(int $amount, ?string $reason = null, ?int $userId = null): bool
    {
        $oldQuantity = $this->quantity;
        $newQuantity = max(0, $oldQuantity - $amount);

        $this->update([
            'quantity' => $newQuantity,
            'last_movement' => now(),
        ]);

        // Registrar movimiento en auditoría
        if (class_exists('App\Models\Warehouse\WarehouseInventoryMovement')) {
            WarehouseInventoryMovement::create([
                'slot_id' => $this->id,
                'product_id' => $this->product_id,
                'movement_type' => 'subtract',
                'from_quantity' => $oldQuantity,
                'to_quantity' => $newQuantity,
                'quantity_delta' => -$amount,
                'reason' => $reason ?? 'Manual',
                'user_id' => $userId ?? auth()->id(),
                'recorded_at' => now(),
            ]);
        }

        return true;
    }

    /**
     * Vaciar el slot
     */
    public function clear(?string $reason = null, ?int $userId = null): bool
    {
        $oldQuantity = $this->quantity;

        $this->update([
            'quantity' => 0,
            'last_movement' => now(),
        ]);

        if (class_exists('App\Models\Warehouse\WarehouseInventoryMovement')) {
            WarehouseInventoryMovement::create([
                'slot_id' => $this->id,
                'product_id' => $this->product_id,
                'movement_type' => 'clear',
                'from_quantity' => $oldQuantity,
                'to_quantity' => 0,
                'quantity_delta' => -$oldQuantity,
                'reason' => $reason ?? 'Manual',
                'user_id' => $userId ?? auth()->id(),
                'recorded_at' => now(),
            ]);
        }

        return true;
    }

    /**
     * Mover producto a otra sección
     */
    public function moveTo(WarehouseLocationSection $newSection, int $quantity = null, ?string $reason = null, ?int $userId = null): bool
    {
        $moveQuantity = $quantity ?? $this->quantity;

        if ($moveQuantity > $this->quantity) {
            return false; // No hay suficiente cantidad
        }

        // Encontrar o crear slot en la nueva sección
        $newSlot = WarehouseInventorySlot::firstOrCreate(
            [
                'section_id' => $newSection->id,
                'product_id' => $this->product_id,
            ],
            [
                'quantity' => 0,
            ]
        );

        // Restar de la sección actual
        $this->subtractQuantity($moveQuantity, $reason ?? 'Moved to ' . $newSection->code, $userId);

        // Agregar a la nueva sección
        $newSlot->addQuantity($moveQuantity, $reason ?? 'Moved from ' . $this->section->code, $userId);

        // Actualizar last_section_id
        $newSlot->update(['last_section_id' => $this->section_id]);

        return true;
    }

    /**
     * Obtener resumen de información del slot
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'uid' => $this->uid,
            'section' => [
                'id' => $this->section?->id,
                'code' => $this->section?->code,
                'level' => $this->section?->level,
            ],
            'product' => [
                'id' => $this->product?->id,
                'name' => $this->product?->name,
            ],
            'quantity' => $this->quantity,
            'kardex' => $this->kardex,
            'is_occupied' => $this->isOccupied(),
            'last_movement' => $this->last_movement,
            'address' => $this->getAddress(),
        ];
    }

    /**
     * Obtener información completa del slot
     */
    public function getFullInfo(): array
    {
        return [
            'id' => $this->id,
            'uid' => $this->uid,
            'section' => $this->section?->getFullInfo(),
            'product' => [
                'id' => $this->product?->id,
                'name' => $this->product?->name,
                'sku' => $this->product?->sku ?? null,
            ],
            'quantity' => $this->quantity,
            'kardex' => $this->kardex,
            'is_occupied' => $this->isOccupied(),
            'last_section' => $this->lastSection?->getSummary(),
            'last_movement' => $this->last_movement,
            'address' => $this->getAddress(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
