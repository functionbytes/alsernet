<?php

namespace Modules\Erp\Models\Oracle\Web;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla W_AYUDAS_MOD
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_WAYUDAS_MOD_MODELO (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID_MODELO
 *
 * ✅ IDX_W_AYUDAS_MOD_W_AYUDAS (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID_AYUDA
 *
 * PK_W_AYUDAS_MOD (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID
 */
class WAyudasMod extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'w_ayudas_mod';

    protected $primaryKey = 'id';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'id_modelo', 'id_ayuda', 'estado', 'idusuariocre', 'idusuariomod',
        'idusuariobaja', 'idmodelo',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación con WModelo
     */
    public function _modelo()
    {
        return $this->belongsTo(WModelo::class, 'id_modelo', 'idw_modelo');
    }

    /**
     * Relación con WAyudas
     */
    public function _ayuda()
    {
        return $this->belongsTo(WAyudas::class, 'id_ayuda', 'idw_ayudas');
    }

    /**
     * Relación: Modelo
     * ✅ Usa IDX_WAYUDAS_MOD_MODELO (indexado)
     */
    public function modelo()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Web\WModelo::class, 'id_modelo', 'id');
    }

    /**
     * Relación: Ayuda
     * ✅ Usa IDX_W_AYUDAS_MOD_W_AYUDAS (indexado)
     */
    public function ayuda()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Web\WAyudas::class, 'id_ayuda', 'id');
    }

    /**
     * Relación: WAyudas
     * ✅ Usa PK_W_AYUDAS_MOD (indexado)
     */
    public function wAyudas()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Web\WAyudas::class, 'id', 'id');
    }
}
