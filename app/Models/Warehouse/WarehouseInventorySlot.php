<?php

namespace App\Models\Warehouse;

use App\Library\Traits\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * InventorySlot Model
 *
 * Representa una POSICIÓN CONCRETA dentro de una estantería.
 * Ubicación: [Stand] → [Cara] → [Nivel] → [Sección]
 *
 * @property int $id
 * @property string $uid UUID universal
 * @property int $stand_id ID de la estantería
 * @property int|null $product_id ID del producto
 * @property string $face Cara (left, right, front, back)
 * @property int $level Nivel (1=arriba)
 * @property int $section Sección (1=izquierda)
 * @property string|null $barcode Código de barras
 * @property int $quantity Cantidad actual
 * @property int|null $max_quantity Máximo permitido
 * @property float $weight_current Peso actual
 * @property float|null $weight_max Peso máximo
 * @property bool $is_occupied Cache: ocupada?
 * @property \Illuminate\Support\Carbon|null $last_movement Última operación
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class InventorySlot extends Model
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
        'location_id',
        'product_id',
        'face',
        'level',
        'section',
        'barcode',
        'quantity',
        'max_quantity',
        'weight_current',
        'weight_max',
        'is_occupied',
        'last_movement',
        'last_inventarie_id',
    ];

    /**
     * Casteo de tipos
     */
    protected $casts = [
        'quantity' => 'integer',
        'max_quantity' => 'integer',
        'weight_current' => 'decimal:2',
        'weight_max' => 'decimal:2',
        'is_occupied' => 'boolean',
        'last_movement' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * ===============================================
     * CONSTANTES
     * ===============================================
     */

    // Caras válidas
    const FACE_LEFT = 'left';
    const FACE_RIGHT = 'right';
    const FACE_FRONT = 'front';
    const FACE_BACK = 'back';

    /**
     * ===============================================
     * RELACIONES
     * ===============================================
     */

    /**
     * Una posición pertenece a una Ubicación (Location/Stand)
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo('App\Models\Location', 'location_id', 'id');
    }

    /**
     * Una posición puede contener un producto
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo('App\Models\Product\Product', 'product_id', 'id');
    }

    /**
     * Último inventario que afectó esta posición
     */
    public function lastInventarie(): BelongsTo
    {
        return $this->belongsTo('App\Models\Warehouse\Warehouse', 'last_inventarie_id', 'id');
    }

    /**
     * Movimientos de este slot
     */
    public function movements()
    {
        return $this->hasMany(InventoryMovement::class, 'slot_id');
    }

    /**
     * ===============================================
     * SCOPES
     * ===============================================
     */

    /**
     * Scope: Solo posiciones ocupadas
     */
    public function scopeOccupied($query)
    {
        return $query->where('is_occupied', true);
    }

    /**
     * Scope: Solo posiciones libres
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_occupied', false);
    }

    /**
     * Scope: Buscar por location
     */
    public function scopeByLocation($query, $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    /**
     * Scope: Buscar por producto
     */
    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope: Buscar por cara
     */
    public function scopeByFace($query, $face)
    {
        return $query->where('face', $face);
    }

    /**
     * Scope: Buscar por nivel
     */
    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Scope: Buscar por código de barras
     */
    public function scopeByBarcode($query, $barcode)
    {
        return $query->where('barcode', $barcode);
    }

    /**
     * Scope: Buscar por código de barras
     */
    public function scopeSearch($query, $search)
    {
        return $query->where('barcode', 'like', "%{$search}%")
            ->orWhere('uid', 'like', "%{$search}%");
    }

    /**
     * Scope: Posiciones cerca del límite de peso
     */
    public function scopeNearWeightCapacity($query, $threshold = 90)
    {
        return $query->where('weight_max', '>', 0)
            ->whereRaw('(weight_current / weight_max * 100) >= ?', [$threshold]);
    }

    /**
     * Scope: Posiciones que exceden capacidad
     */
    public function scopeOverCapacity($query)
    {
        return $query->where('weight_max', '>', 0)
            ->whereRaw('weight_current > weight_max');
    }

    /**
     * Scope: Posiciones que exceden cantidad máxima
     */
    public function scopeOverQuantity($query)
    {
        return $query->where('max_quantity', '>', 0)
            ->whereRaw('quantity > max_quantity');
    }

    /**
     * ===============================================
     * MÉTODOS HELPERS
     * ===============================================
     */

    /**
     * Obtener la dirección de la posición en formato legible
     * Ejemplo: "Almacén Central / Piso 1 / PASILLO1A / Izquierda / Nivel 2 / Sección 3"
     */
    public function getAddress(): string
    {
        $faceLabel = $this->getFaceLabel();
        $inv = $this->location?->inventarie?->name ?? 'N/A';
        $floor = $this->location?->floor?->name ?? 'N/A';
        $loc = $this->location?->code ?? 'N/A';
        return "{$inv} / {$floor} / {$loc} / {$faceLabel} / N{$this->level} / S{$this->section}";
    }

    /**
     * Obtener etiqueta amigable de la cara
     */
    public function getFaceLabel(): string
    {
        return match ($this->face) {
            self::FACE_LEFT => 'Izquierda',
            self::FACE_RIGHT => 'Derecha',
            self::FACE_FRONT => 'Frente',
            self::FACE_BACK => 'Atrás',
            default => $this->face,
        };
    }

    /**
     * Verificar si la posición está ocupada
     */
    public function isOccupied(): bool
    {
        return $this->is_occupied || ($this->product_id !== null && $this->quantity > 0);
    }

    /**
     * Verificar si la posición está disponible
     */
    public function isAvailable(): bool
    {
        return !$this->isOccupied();
    }

    /**
     * Obtener capacidad de cantidad disponible
     */
    public function getAvailableQuantity(): int
    {
        if (!$this->max_quantity) {
            return PHP_INT_MAX; // Sin límite
        }

        return max(0, $this->max_quantity - $this->quantity);
    }

    /**
     * Obtener capacidad de peso disponible (en kg)
     */
    public function getAvailableWeight(): float
    {
        if (!$this->weight_max) {
            return PHP_FLOAT_MAX; // Sin límite
        }

        return max(0, $this->weight_max - $this->weight_current);
    }

    /**
     * Obtener porcentaje de ocupación de peso
     */
    public function getWeightPercentage(): float
    {
        if (!$this->weight_max || $this->weight_max === 0) {
            return 0;
        }

        return ($this->weight_current / $this->weight_max) * 100;
    }

    /**
     * Obtener porcentaje de ocupación de cantidad
     */
    public function getQuantityPercentage(): float
    {
        if (!$this->max_quantity || $this->max_quantity === 0) {
            return 0;
        }

        return ($this->quantity / $this->max_quantity) * 100;
    }

    /**
     * Verificar si se puede agregar cantidad
     */
    public function canAddQuantity(int $amount): bool
    {
        if (!$this->max_quantity) {
            return true; // Sin límite
        }

        return ($this->quantity + $amount) <= $this->max_quantity;
    }

    /**
     * Verificar si se puede agregar peso
     */
    public function canAddWeight(float $weight): bool
    {
        if (!$this->weight_max) {
            return true; // Sin límite
        }

        return ($this->weight_current + $weight) <= $this->weight_max;
    }

    /**
     * Verificar si está cerca del límite de cantidad
     */
    public function isNearQuantityCapacity(int $threshold = 90): bool
    {
        return $this->getQuantityPercentage() >= $threshold;
    }

    /**
     * Verificar si está cerca del límite de peso
     */
    public function isNearWeightCapacity(int $threshold = 90): bool
    {
        return $this->getWeightPercentage() >= $threshold;
    }

    /**
     * Verificar si excede cantidad máxima
     */
    public function isOverQuantity(): bool
    {
        return $this->max_quantity && $this->quantity > $this->max_quantity;
    }

    /**
     * Verificar si excede peso máximo
     */
    public function isOverWeight(): bool
    {
        return $this->weight_max && $this->weight_current > $this->weight_max;
    }

    /**
     * Agregar cantidad (con auditoría)
     */
    public function addQuantity(
        int $amount,
        ?string $reason = null,
        ?int $userId = null,
        ?int $inventarieId = null
    ): bool {
        if (!$this->canAddQuantity($amount)) {
            return false;
        }

        $fromQty = $this->quantity;
        $toQty = $this->quantity + $amount;

        $this->update([
            'quantity' => $toQty,
            'is_occupied' => true,
            'last_movement' => now(),
            'last_inventarie_id' => $inventarieId,
        ]);

        // Crear movimiento en auditoría
        InventoryMovement::create([
            'slot_id' => $this->id,
            'product_id' => $this->product_id,
            'movement_type' => InventoryMovement::TYPE_ADD,
            'from_quantity' => $fromQty,
            'to_quantity' => $toQty,
            'quantity_delta' => $amount,
            'from_weight' => $this->weight_current,
            'to_weight' => $this->weight_current,
            'weight_delta' => 0,
            'reason' => $reason ?? 'Manual',
            'inventarie_id' => $inventarieId,
            'user_id' => $userId ?? auth()->id(),
        ]);

        return true;
    }

    /**
     * Restar cantidad (con auditoría)
     */
    public function subtractQuantity(
        int $amount,
        ?string $reason = null,
        ?int $userId = null,
        ?int $inventarieId = null
    ): bool {
        $newQuantity = $this->quantity - $amount;

        if ($newQuantity < 0) {
            return false;
        }

        $fromQty = $this->quantity;

        $this->update([
            'quantity' => $newQuantity,
            'is_occupied' => $newQuantity > 0,
            'last_movement' => now(),
            'last_inventarie_id' => $inventarieId,
        ]);

        // Crear movimiento en auditoría
        InventoryMovement::create([
            'slot_id' => $this->id,
            'product_id' => $this->product_id,
            'movement_type' => InventoryMovement::TYPE_SUBTRACT,
            'from_quantity' => $fromQty,
            'to_quantity' => $newQuantity,
            'quantity_delta' => -$amount,
            'from_weight' => $this->weight_current,
            'to_weight' => $this->weight_current,
            'weight_delta' => 0,
            'reason' => $reason ?? 'Manual',
            'inventarie_id' => $inventarieId,
            'user_id' => $userId ?? auth()->id(),
        ]);

        return true;
    }

    /**
     * Agregar peso (con auditoría)
     */
    public function addWeight(
        float $weight,
        ?string $reason = null,
        ?int $userId = null,
        ?int $inventarieId = null
    ): bool {
        if (!$this->canAddWeight($weight)) {
            return false;
        }

        $fromWeight = $this->weight_current;
        $toWeight = $this->weight_current + $weight;

        $this->update([
            'weight_current' => $toWeight,
            'is_occupied' => true,
            'last_movement' => now(),
            'last_inventarie_id' => $inventarieId,
        ]);

        // Crear movimiento en auditoría
        InventoryMovement::create([
            'slot_id' => $this->id,
            'product_id' => $this->product_id,
            'movement_type' => InventoryMovement::TYPE_ADD,
            'from_quantity' => $this->quantity,
            'to_quantity' => $this->quantity,
            'quantity_delta' => 0,
            'from_weight' => $fromWeight,
            'to_weight' => $toWeight,
            'weight_delta' => $weight,
            'reason' => $reason ?? 'Manual',
            'inventarie_id' => $inventarieId,
            'user_id' => $userId ?? auth()->id(),
        ]);

        return true;
    }

    /**
     * Restar peso (con auditoría)
     */
    public function subtractWeight(
        float $weight,
        ?string $reason = null,
        ?int $userId = null,
        ?int $inventarieId = null
    ): bool {
        $newWeight = $this->weight_current - $weight;

        if ($newWeight < 0) {
            return false;
        }

        $fromWeight = $this->weight_current;

        $this->update([
            'weight_current' => $newWeight,
            'is_occupied' => $newWeight > 0,
            'last_movement' => now(),
            'last_inventarie_id' => $inventarieId,
        ]);

        // Crear movimiento en auditoría
        InventoryMovement::create([
            'slot_id' => $this->id,
            'product_id' => $this->product_id,
            'movement_type' => InventoryMovement::TYPE_SUBTRACT,
            'from_quantity' => $this->quantity,
            'to_quantity' => $this->quantity,
            'quantity_delta' => 0,
            'from_weight' => $fromWeight,
            'to_weight' => $newWeight,
            'weight_delta' => -$weight,
            'reason' => $reason ?? 'Manual',
            'inventarie_id' => $inventarieId,
            'user_id' => $userId ?? auth()->id(),
        ]);

        return true;
    }

    /**
     * Vaciar la posición completamente (con auditoría)
     */
    public function clear(
        ?string $reason = null,
        ?int $userId = null,
        ?int $inventarieId = null
    ): void {
        $fromQty = $this->quantity;
        $fromWeight = $this->weight_current;

        $this->update([
            'product_id' => null,
            'quantity' => 0,
            'weight_current' => 0,
            'is_occupied' => false,
            'last_movement' => now(),
            'last_inventarie_id' => $inventarieId,
        ]);

        // Crear movimiento en auditoría
        InventoryMovement::create([
            'slot_id' => $this->id,
            'product_id' => null,
            'movement_type' => InventoryMovement::TYPE_CLEAR,
            'from_quantity' => $fromQty,
            'to_quantity' => 0,
            'quantity_delta' => -$fromQty,
            'from_weight' => $fromWeight,
            'to_weight' => 0,
            'weight_delta' => -$fromWeight,
            'reason' => $reason ?? 'Manual',
            'inventarie_id' => $inventarieId,
            'user_id' => $userId ?? auth()->id(),
        ]);
    }

    /**
     * Obtener información completa de la posición
     */
    public function getFullInfo(): array
    {
        return [
            'id' => $this->id,
            'uid' => $this->uid,
            'address' => $this->getAddress(),
            'stand' => [
                'id' => $this->stand?->id,
                'code' => $this->stand?->code,
                'name' => $this->stand?->name,
            ],
            'position' => [
                'face' => $this->face,
                'face_label' => $this->getFaceLabel(),
                'level' => $this->level,
                'section' => $this->section,
            ],
            'product' => [
                'id' => $this->product_id,
                'name' => $this->product?->name,
            ],
            'quantity' => [
                'current' => $this->quantity,
                'max' => $this->max_quantity,
                'available' => $this->getAvailableQuantity(),
                'percentage' => round($this->getQuantityPercentage(), 2),
            ],
            'weight' => [
                'current' => $this->weight_current,
                'max' => $this->weight_max,
                'available' => $this->getAvailableWeight(),
                'percentage' => round($this->getWeightPercentage(), 2),
            ],
            'is_occupied' => $this->is_occupied,
            'is_available' => $this->isAvailable(),
            'last_movement' => $this->last_movement,
            'created_at' => $this->created_at,
        ];
    }

    /**
     * Obtener resumensimplificado
     */
    public function getSummary(): array
    {
        return [
            'address' => $this->getAddress(),
            'is_occupied' => $this->is_occupied,
            'product' => $this->product?->name ?? 'N/A',
            'quantity' => $this->quantity,
            'weight' => round($this->weight_current, 2),
        ];
    }
}
