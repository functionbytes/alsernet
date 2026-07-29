<?php

namespace Modules\Erp\Models\Oracle\Web;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla W_VALORES_PROD_IDIOMA
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_WVAL_PROD_IDIOMA_WVALPROD (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID_VALOR
 *
 * PK_W_VALORES_PROD_IDIOMA (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID
 */
class WValoresProdIdioma extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'w_valores_prod_idioma';

    protected $primaryKey = 'id';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'id_valor', 'nombre', 'idioma', 'estado', 'idusuariocre',
        'idusuariomod', 'idusuariobaja',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación con WValoresProd
     */
    public function _valor()
    {
        return $this->belongsTo(WValoresProd::class, 'id_valor', 'idw_valores_prod');
    }

    /**
     * Relación: Valor
     * ✅ Usa IDX_WVAL_PROD_IDIOMA_WVALPROD (indexado)
     */
    public function valor()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Web\WValoresProd::class, 'id_valor', 'id');
    }

    /**
     * Relación: WAyudas
     * ✅ Usa PK_W_VALORES_PROD_IDIOMA (indexado)
     */
    public function wAyudas()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Web\WAyudas::class, 'id', 'id');
    }
}
