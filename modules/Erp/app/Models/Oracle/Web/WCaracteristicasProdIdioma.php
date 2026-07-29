<?php

namespace Modules\Erp\Models\Oracle\Web;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla W_CARACTERISTICAS_PROD_IDIOMA
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_WCARACT_PROIDI_WCARACPROD (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID_CARACTERISTICA
 *
 * PK_W_CARACTERISTICAS_PROD_IDIO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID
 */
class WCaracteristicasProdIdioma extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'w_caracteristicas_prod_idioma';

    protected $primaryKey = 'id';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'nombre', 'idioma', 'id_caracteristica', 'estado', 'idusuariocre',
        'idusuariomod', 'idusuariobaja',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación con WCaracteristicasProd
     */
    public function _caracteristica()
    {
        return $this->belongsTo(WCaracteristicasProd::class, 'id_caracteristica', 'idw_caracteristicas_prod');
    }

    /**
     * Relación: Caracteristica
     * ✅ Usa IDX_WCARACT_PROIDI_WCARACPROD (indexado)
     */
    public function caracteristica()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Web\WCaracteristicasProd::class, 'id_caracteristica', 'id');
    }

    /**
     * Relación: WAyudas
     * ✅ Usa PK_W_CARACTERISTICAS_PROD_IDIO (indexado)
     */
    public function wAyudas()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Web\WAyudas::class, 'id', 'id');
    }
}
