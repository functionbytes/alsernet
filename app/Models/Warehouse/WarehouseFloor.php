<?php

namespace App\Models\Warehouse;

use App\Library\Traits\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Floor Model
 *
 * Representa un PISO/PLANTA del almacén.
 *
 * @property int $id
 * @property string $uid UUID universal
 * @property string $code Código único (P1, P2, S0)
 * @property string $name Nombre legible
 * @property string|null $description Descripción
 * @property bool $available Disponibilidad
 * @property int $order Orden visual
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Floor extends Model
{
    use HasFactory, HasUid;

    protected $table = 'warehouse_floors';
    protected $primaryKey = 'id';
    protected $keyType = 'int';
    public $incrementing = true;

    /**
     * Campos que pueden ser asignados masivamente
     */
    protected $fillable = [
        'uid',
        'code',
        'name',
        'description',
        'available',
        'order',
    ];

    /**
     * Casteo de tipos
     */
    protected $casts = [
        'available' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * ===============================================
     * RELACIONES
     * ===============================================
     */

    /**
     * Un piso tiene muchas estanterías
     */
    public function stands(): HasMany
    {
        return $this->hasMany(Stand::class, 'floor_id', 'id');
    }

    /**
     * ===============================================
     * SCOPES
     * ===============================================
     */

    /**
     * Scope: Solo pisos disponibles
     */
    public function scopeAvailable($query)
    {
        return $query->where('available', true);
    }

    /**
     * Scope: Ordenado por orden y nombre
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc')->orderBy('name', 'asc');
    }

    /**
     * Scope: Buscar por código
     */
    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }

    /**
     * Scope: Buscar por nombre (búsqueda partial)
     */
    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'like', "%{$search}%")
            ->orWhere('code', 'like', "%{$search}%");
    }

    /**
     * ===============================================
     * MÉTODOS HELPERS
     * ===============================================
     */

    /**
     * Obtener el número total de estanterías
     */
    public function getStandCount(): int
    {
        return $this->stands()->count();
    }

    /**
     * Obtener el número de estanterías disponibles
     */
    public function getAvailableStandCount(): int
    {
        return $this->stands()->where('available', true)->count();
    }

    /**
     * Obtener el número total de posiciones en este piso
     */
    public function getTotalSlotsCount(): int
    {
        return InventorySlot::whereHas('stand', function ($query) {
            $query->where('floor_id', $this->id);
        })->count();
    }

    /**
     * Obtener el número de posiciones ocupadas
     */
    public function getOccupiedSlotsCount(): int
    {
        return InventorySlot::whereHas('stand', function ($query) {
            $query->where('floor_id', $this->id);
        })->where('is_occupied', true)->count();
    }

    /**
     * Obtener el porcentaje de ocupación
     */
    public function getOccupancyPercentage(): float
    {
        $total = $this->getTotalSlotsCount();
        if ($total === 0) {
            return 0;
        }

        $occupied = $this->getOccupiedSlotsCount();
        return ($occupied / $total) * 100;
    }

    /**
     * Obtener información resumida del piso
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'uid' => $this->uid,
            'code' => $this->code,
            'name' => $this->name,
            'available' => $this->available,
            'stands_count' => $this->getStandCount(),
            'available_stands_count' => $this->getAvailableStandCount(),
            'total_slots' => $this->getTotalSlotsCount(),
            'occupied_slots' => $this->getOccupiedSlotsCount(),
            'occupancy_percentage' => round($this->getOccupancyPercentage(), 2),
        ];
    }
}
