<?php

namespace Modules\Erp\Models\Oracle\Web;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla W_PERFILES_NAV
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_WPERFILES_NAV_WMODELO (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID_MODELO
 *
 * ✅ IDX_WPERFILES_NAV_WVALORESNAV (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID_VALOR
 *
 * PK_W_PERFILES_NAV (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID
 */
class WPerfilesNav extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'w_perfiles_nav';

    protected $primaryKey = 'id';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'id_valor', 'id_modelo', 'estado', 'idusuariocre', 'idusuariomod',
        'idusuariobaja', 'principal', 'idmodelo',
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
     * Relación con WValoresNav
     */
    public function _valor()
    {
        return $this->belongsTo(WValoresNav::class, 'id_valor', 'idw_valores_nav');
    }

    /**
     * Relación: Modelo
     * ✅ Usa IDX_WPERFILES_NAV_WMODELO (indexado)
     */
    public function modelo()
    {
        return $this->belongsTo(WModelo::class, 'id_modelo', 'id');
    }

    /**
     * Relación: Valor
     * ✅ Usa IDX_WPERFILES_NAV_WVALORESNAV (indexado)
     */
    public function valor()
    {
        return $this->belongsTo(WValoresNav::class, 'id_valor', 'id');
    }

    /**
     * Relación: WAyudas
     * ✅ Usa PK_W_PERFILES_NAV (indexado)
     */
    public function wAyudas()
    {
        return $this->belongsTo(WAyudas::class, 'id', 'id');
    }
}
