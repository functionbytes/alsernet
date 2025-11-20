<?php

namespace App\Models\Warehouse;

use App\Library\Traits\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Warehouse\WarehouseInventorySlot;


class WarehouseFloor extends Model
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
        'warehouse_id',
        'code',
        'name',
        'description',
        'level',
        'available',
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
     * Relación con Warehouse
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo('App\Models\Warehouse\Warehouse', 'warehouse_id', 'id');
    }

    /**
     * Relación con Ubicaciones
     */
    public function locations(): HasMany
    {
        return $this->hasMany('App\Models\Warehouse\WarehouseLocation', 'floor_id', 'id');
    }

    /**
     * ===============================================
     * SCOPES
     * ===============================================
     */

    /**
     * Scope: Filtrar por warehouse
     */
    public function scopeByWarehouse($query, $warehouse_id)
    {
        return $query->where('warehouse_id', $warehouse_id);
    }

    /**
     * Scope: Buscar por uid
     */
    public function scopeUid($query, $uid)
    {
        return $query->where('uid', $uid)->first();
    }

    /**
     * Scope: Solo pisos disponibles
     */
    public function scopeAvailable($query)
    {
        return $query->where('available', true);
    }

    /**
     * Scope: Ordenado por nivel y nombre
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('level', 'asc')->orderBy('name', 'asc');
    }

    /**
     * Scope: Buscar por nombre (búsqueda partial)
     */
    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'like', "%{$search}%");
    }

    public function getlocationCount(): int
    {
        return $this->locations()->count();
    }

    public function getAvailablelocationCount(): int
    {
        return $this->locations()->where('available', true)->count();
    }

    public function getTotalSlotsCount(): int
    {
        return WarehouseInventorySlot::whereHas('location', function ($query) {
            $query->where('floor_id', $this->id);
        })->count();
    }


    public function getOccupiedSlotsCount(): int
    {
        return WarehouseInventorySlot::whereHas('location', function ($query) {
            $query->where('floor_id', $this->id);
        })->where('quantity', '>', 0)->count();
    }

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
            'warehouse_id' => $this->warehouse_id,
            'name' => $this->name,
            'level' => $this->level,
            'available' => $this->available,
            'locations_count' => $this->getlocationCount(),
            'available_locations_count' => $this->getAvailablelocationCount(),
            'total_slots' => $this->getTotalSlotsCount(),
            'occupied_slots' => $this->getOccupiedSlotsCount(),
            'occupancy_percentage' => round($this->getOccupancyPercentage(), 2),
        ];
    }
}
