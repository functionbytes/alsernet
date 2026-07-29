<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla PRIORIDAD
 *
 * ÍNDICES DISPONIBLES:
 * PK_PRIORIDAD (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDPRIORIDAD
 */
class Prioridad extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'prioridad';

    protected $primaryKey = 'idprioridad';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'descripcion', 'idusuariocre', 'idusuariomod', 'estado', 'nivel',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Prioridad
     * ✅ Usa PK_PRIORIDAD (indexado)
     */
    public function prioridad()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\Prioridad::class, 'idprioridad', 'idprioridad');
    }
}
