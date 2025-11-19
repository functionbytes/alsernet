<?php

namespace App\Models\Warehouse;

use App\Library\Traits\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseLocation extends Model
{
    use HasFactory, HasUid;

    protected $table = 'warehouse_locations';
    protected $primaryKey = 'id';
    protected $keyType = 'int';
    public $incrementing = true;

    /**
     * Campos que pueden ser asignados masivamente
     */
    protected $fillable = [
        'uid',
        'warehouse_id',
        'floor_id',
        'style_id',
        'code',
        'barcode',
        'position_x',
        'position_y',
        'position_z',
        'total_levels',
        'total_sections',
        'capacity',
        'available',
        'notes',
    ];

    /**
     * Casteo de tipos
     */
    protected $casts = [
        'capacity' => 'decimal:2',
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
     * Una estantería pertenece a un piso
     */
    public function floor(): BelongsTo
    {
        return $this->belongsTo('App\Models\Warehouse\WarehouseFloor', 'floor_id', 'id');
    }

    /**
     * Una estantería tiene un estilo
     */
    public function style(): BelongsTo
    {
        return $this->belongsTo('App\Models\Warehouse\WarehouseLocationStyle', 'style_id', 'id');
    }

    /**
     * Una estantería tiene muchas posiciones
     */
    public function slots(): HasMany
    {
        return $this->hasMany('App\Models\Warehouse\WarehouseInventorySlot', 'location_id', 'id');
    }

    /**
     * ===============================================
     * SCOPES
     * ===============================================
     */

    /**
     * Scope: Solo estanterías activas
     */
    public function scopeAvailable($query)
    {
        return $query->where('available', true);
    }

    /**
     * Scope: Buscar por piso
     */
    public function scopeByFloor($query, $floorId)
    {
        return $query->where('floor_id', $floorId);
    }

    /**
     * Scope: Buscar por código
     */
    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }

    /**
     * Scope: Buscar por código de barras
     */
    public function scopeByBarcode($query, $barcode)
    {
        return $query->where('barcode', $barcode);
    }

    /**
     * Scope: Buscar por estilo
     */
    public function scopeByStyle($query, $styleId)
    {
        return $query->where('stand_style_id', $styleId);
    }

    /**
     * Scope: Búsqueda general
     */
    public function scopeSearch($query, $search)
    {
        return $query->where('code', 'like', "%{$search}%")
            ->orWhere('barcode', 'like', "%{$search}%");
    }

    /**
     * Scope: Ordenado por posición
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('position_x', 'asc')
            ->orderBy('position_y', 'asc');
    }

    /**
     * ===============================================
     * MÉTODOS HELPERS
     * ===============================================
     */

    /**
     * Obtener el nombre completo de la estantería
     */
    public function getFullName(): string
    {
        return "{$this->code} ({$this->floor?->name})";
    }

    /**
     * Obtener el número total de posiciones
     */
    public function getTotalSlots(): int
    {
        // Total = (número de caras) × (niveles) × (secciones)
        $facesCount = count($this->style?->faces ?? []);
        return $facesCount * $this->total_levels * $this->total_sections;
    }

    /**
     * Obtener el número de posiciones ocupadas
     */
    public function getOccupiedSlots(): int
    {
        return $this->slots()->where('is_occupied', true)->count();
    }

    /**
     * Obtener el número de posiciones libres
     */
    public function getAvailableSlots(): int
    {
        return $this->getTotalSlots() - $this->getOccupiedSlots();
    }

    /**
     * Obtener el porcentaje de ocupación
     */
    public function getOccupancyPercentage(): float
    {
        $total = $this->getTotalSlots();
        if ($total === 0) {
            return 0;
        }

        $occupied = $this->getOccupiedSlots();
        return ($occupied / $total) * 100;
    }

    /**
     * Obtener la capacidad total de peso en la estantería
     */
    public function getTotalCapacity(): float
    {
        return $this->capacity ?? 0;
    }

    /**
     * Obtener el peso actual
     */
    public function getCurrentWeight(): float
    {
        return $this->slots()->sum('weight_current');
    }

    /**
     * Verificar si está cerca del límite de capacidad
     */
    public function isNearCapacity(int $threshold = 90): bool
    {
        if (!$this->capacity) {
            return false;
        }

        $currentWeight = $this->getCurrentWeight();
        $percentageUsed = ($currentWeight / $this->capacity) * 100;

        return $percentageUsed >= $threshold;
    }

    /**
     * Obtener una posición específica por cara, nivel y sección
     */
    public function getSlot(string $face, int $level, int $section): ?WarehouseInventorySlot
    {
        return $this->slots()
            ->where('face', $face)
            ->where('level', $level)
            ->where('section', $section)
            ->first();
    }

    /**
     * Obtener todas las posiciones de una cara
     */
    public function getSlotsByFace(string $face)
    {
        return $this->slots()
            ->where('face', $face)
            ->orderBy('level', 'asc')
            ->orderBy('section', 'asc')
            ->get();
    }

    /**
     * Obtener todas las posiciones de un nivel
     */
    public function getSlotsByLevel(int $level)
    {
        return $this->slots()
            ->where('level', $level)
            ->orderBy('face', 'asc')
            ->orderBy('section', 'asc')
            ->get();
    }

    /**
     * Obtener información resumida
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'uid' => $this->uid,
            'code' => $this->code,
            'full_name' => $this->getFullName(),
            'floor' => $this->floor?->name,
            'style' => $this->style?->name,
            'position' => [
                'x' => $this->position_x,
                'y' => $this->position_y,
                'z' => $this->position_z,
            ],
            'dimensions' => [
                'levels' => $this->total_levels,
                'sections' => $this->total_sections,
            ],
            'available' => $this->available,
            'capacity' => $this->capacity,
            'current_weight' => $this->getCurrentWeight(),
            'total_slots' => $this->getTotalSlots(),
            'occupied_slots' => $this->getOccupiedSlots(),
            'available_slots' => $this->getAvailableSlots(),
            'occupancy_percentage' => round($this->getOccupancyPercentage(), 2),
            'near_capacity' => $this->isNearCapacity(),
        ];
    }

    /**
     * Crear todas las posiciones (slots) para esta estantería
     * Útil al crear una estantería nueva
     */
    public function createSlots(): int
    {
        $facesCount = count($this->style?->faces ?? []);
        $created = 0;

        foreach ($this->style?->faces ?? [] as $face) {
            for ($level = 1; $level <= $this->total_levels; $level++) {
                for ($section = 1; $section <= $this->total_sections; $section++) {
                    WarehouseInventorySlot::create([
                        'location_id' => $this->id,
                        'face' => $face,
                        'level' => $level,
                        'section' => $section,
                    ]);
                    $created++;
                }
            }
        }

        return $created;
    }
}
