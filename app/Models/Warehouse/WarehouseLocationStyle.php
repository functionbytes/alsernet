<?php

namespace App\Models\Warehouse;

use App\Library\Traits\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseLocationStyle extends Model
{
    use HasFactory, HasUid;

    protected $table = 'warehouse_location_styles';
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
        'type',
        'faces',
        'width',
        'height',
        'default_levels',
        'default_sections',
        'available',
    ];

    /**
     * Casteo de tipos
     */
    protected $casts = [
        'faces' => 'array',
        'available' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * ===============================================
     * CONSTANTES
     * ===============================================
     */

    // Tipos de estilos disponibles
    const TYPE_ROW = 'row';
    const TYPE_ISLAND = 'island';
    const TYPE_WALL = 'wall';

    // Caras disponibles
    const FACE_LEFT = 'left';
    const FACE_RIGHT = 'right';
    const FACE_FRONT = 'front';
    const FACE_BACK = 'back';

    /**
     * Lista de tipos válidos
     */
    public static array $types = [
        self::TYPE_ROW,
        self::TYPE_ISLAND,
        self::TYPE_WALL,
    ];

    /**
     * Lista de caras válidas
     */
    public static array $validFaces = [
        self::FACE_LEFT,
        self::FACE_RIGHT,
        self::FACE_FRONT,
        self::FACE_BACK,
    ];

    /**
     * Obtener las caras disponibles según el tipo de estilo
     */
    public static function getFacesByType(string $type): array
    {
        return match ($type) {
            self::TYPE_ISLAND => [self::FACE_FRONT, self::FACE_BACK, self::FACE_LEFT, self::FACE_RIGHT], // 4 caras (isla cuadrada)
            self::TYPE_ROW => [self::FACE_FRONT, self::FACE_BACK], // 2 caras (pasillo)
            self::TYPE_WALL => [self::FACE_FRONT], // 1 cara (pared)
            default => [self::FACE_FRONT],
        };
    }

    /**
     * ===============================================
     * RELACIONES
     * ===============================================
     */

    /**
     * Un estilo tiene muchas estanterías
     */
    public function locations(): HasMany
    {
        return $this->hasMany('App\Models\Warehouse\WarehouseLocation', 'style_id', 'id');
    }

    /**
     * ===============================================
     * SCOPES
     * ===============================================
     */

    /**
     * Scope: Solo estilos activos
     */
    public function scopeAvailable($query)
    {
        return $query->where('available', true);
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
     * Obtener la descripción amigable del tipo
     */
    public function getTypeName(): string
    {
        return match ($this->type) {
            self::TYPE_ROW => 'Pasillo Lineal',
            self::TYPE_ISLAND => 'Isla (360°)',
            self::TYPE_WALL => 'Pared',
            default => 'Desconocido',
        };
    }

    /**
     * Obtener descripción de las caras (texto legible)
     */
    public function getFacesLabel(): string
    {
        $labels = [];
        foreach ($this->faces as $face) {
            $labels[] = match ($face) {
                self::FACE_LEFT => 'Izquierda',
                self::FACE_RIGHT => 'Derecha',
                self::FACE_FRONT => 'Frente',
                self::FACE_BACK => 'Atrás',
                default => $face,
            };
        }

        return implode(', ', $labels);
    }

    /**
     * Validar que todas las caras sean válidas
     */
    public function hasValidFaces(): bool
    {
        if (!is_array($this->faces) || empty($this->faces)) {
            return false;
        }

        foreach ($this->faces as $face) {
            if (!in_array($face, self::$validFaces)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Obtener el número total de estanterías de este estilo
     */
    public function getStandCount(): int
    {
        return $this->locations()->count();
    }

    /**
     * Obtener el número de estanterías activas de este estilo
     */
    public function getActiveStandCount(): int
    {
        return $this->locations()->where('available', true)->count();
    }

    /**
     * Obtener información resumida del estilo
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'uid' => $this->uid,
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'type_name' => $this->getTypeName(),
            'faces' => $this->faces,
            'width' => $this->width,
            'height' => $this->height,
            'faces_label' => $this->getFacesLabel(),
            'default_levels' => $this->default_levels,
            'default_sections' => $this->default_sections,
            'available' => $this->available,
            'locations_count' => $this->getStandCount(),
            'active_locations_count' => $this->getActiveStandCount(),
        ];
    }
}
