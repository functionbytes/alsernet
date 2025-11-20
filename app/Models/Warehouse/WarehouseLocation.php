<?php

namespace App\Models\Warehouse;

use App\Library\Traits\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

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
        'code',
        'style_id',
        'position_x',
        'position_y',
        'total_levels',
        'available',
        'notes',
    ];

    /**
     * Casteo de tipos
     */
    protected $casts = [
        'available' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scopeId($query ,$id)
    {
        return $query->where('id', $id)->first();
    }

    public function scopeUid($query ,$uid)
    {
        return $query->where('uid', $uid)->first();
    }


    /**
     * Una estantería pertenece a un warehouse
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo('App\Models\Warehouse\Warehouse', 'warehouse_id', 'id');
    }

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
     * Una estantería tiene muchas secciones
     */
    public function sections(): HasMany
    {
        return $this->hasMany('App\Models\Warehouse\WarehouseLocationSection', 'location_id', 'id');
    }

    /**
     * Una estantería tiene muchas posiciones (a través de secciones)
     */
    public function slots()
    {
        return $this->hasManyThrough(
            'App\Models\Warehouse\WarehouseInventorySlot',
            'App\Models\Warehouse\WarehouseLocationSection',
            'location_id',
            'section_id'
        );
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
     * Scope: Buscar por estilo
     */
    public function scopeByStyle($query, $styleId)
    {
        return $query->where('style_id', $styleId);
    }

    /**
     * Scope: Búsqueda general
     */
    public function scopeSearch($query, $search)
    {
        return $query->where('uid', 'like', "%{$search}%");
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
        return $this->slots()->where('quantity', '>', 0)->count();
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
     * Obtener una posición específica por sección code
     */
    public function getSlot(string $sectionCode): ?WarehouseInventorySlot
    {
        return $this->slots()
            ->whereHas('section', function ($query) use ($sectionCode) {
                $query->where('code', $sectionCode);
            })
            ->first();
    }

    /**
     * Obtener todas las posiciones de una cara (sección)
     */
    public function getSlotsByFace(string $face)
    {
        return $this->slots()
            ->whereHas('section', function ($query) use ($face) {
                $query->where('face', $face);
            })
            ->orderBy('section_id', 'asc')
            ->get();
    }

    /**
     * Obtener todas las posiciones de un nivel
     */
    public function getSlotsByLevel(int $level)
    {
        return $this->slots()
            ->whereHas('section', function ($query) use ($level) {
                $query->where('level', $level);
            })
            ->orderBy('section_id', 'asc')
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
            ],
            'dimensions' => [
                'levels' => $this->total_levels,
                'sections' => $this->total_sections,
            ],
            'available' => $this->available,
            'total_slots' => $this->getTotalSlots(),
            'occupied_slots' => $this->getOccupiedSlots(),
            'available_slots' => $this->getAvailableSlots(),
            'occupancy_percentage' => round($this->getOccupancyPercentage(), 2),
        ];
    }

    /**
     * Crear posiciones (slots) basadas en secciones
     * Las secciones ya contienen el nivel y la cara
     * Los slots se crean a través de las secciones
     */
    public function createSlotsBySections(): int
    {
        $created = 0;

        // For each section in this location, we don't need to create slots manually
        // Slots are created through product assignments to sections
        // This method is kept for backwards compatibility but may not be needed

        return $created;
    }
}
