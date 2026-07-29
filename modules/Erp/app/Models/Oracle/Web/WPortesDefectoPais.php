<?php

namespace Modules\Erp\Models\Oracle\Web;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla W_PORTES_DEFECTO_PAIS
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_WPORTES_DEF_PAIS_PAIS (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID_PAIS
 *
 * ✅ IDX_WPORTES_DEF_PAIS_PORTE (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID_PORTE
 *
 * PK_W_PORTES_DEFECTO_PAIS (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID
 */
class WPortesDefectoPais extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'w_portes_defecto_pais';

    protected $primaryKey = 'id';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'estado', 'idusuariocre', 'idusuariomod', 'idusuariobaja', 'id_porte',
        'id_pais',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: WAyudas
     * ✅ Usa PK_W_PORTES_DEFECTO_PAIS (indexado)
     */
    public function wAyudas()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Web\WAyudas::class, 'id', 'id');
    }
}
