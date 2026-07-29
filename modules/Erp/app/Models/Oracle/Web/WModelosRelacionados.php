<?php

namespace Modules\Erp\Models\Oracle\Web;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla W_MODELOS_RELACIONADOS
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_WMODELOS_REL_WMODELO1 (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: REL1
 *
 * ✅ IDX_WMODELOS_REL_WMODELO2 (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: REL2
 *
 * PK_W_MODELOS_RELACIONADOS (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID
 */
class WModelosRelacionados extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'w_modelos_relacionados';

    protected $primaryKey = 'id';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'rel1', 'rel2', 'estado', 'idusuariocre', 'idusuariomod',
        'idusuariobaja',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Modelo relacionado (relación 1)
     */
    public function modeloRel1()
    {
        return $this->belongsTo(WModelo::class, 'rel1', 'idw_modelo');
    }

    /**
     * Modelo relacionado (relación 2)
     */
    public function modeloRel2()
    {
        return $this->belongsTo(WModelo::class, 'rel2', 'idw_modelo');
    }

    /**
     * Relación: WAyudas
     * ✅ Usa PK_W_MODELOS_RELACIONADOS (indexado)
     */
    public function wAyudas()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Web\WAyudas::class, 'id', 'id');
    }
}
