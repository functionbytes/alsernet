<?php

namespace Modules\Erp\Models\Oracle\Web;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla W_VALORES_PROD
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_WVALPROD_WCARACTPROD (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID_CARACTERISTICA
 *
 * PK_W_VALORES_PROD (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID
 *
 * ⚠️  UK_WVALPROD (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: NOMBRE, ID_CARACTERISTICA
 */
class WValoresProd extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'w_valores_prod';

    protected $primaryKey = 'id';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'nombre', 'id_caracteristica', 'estado', 'idusuariocre', 'idusuariomod',
        'idusuariobaja',
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
     * Relación inversa con WPerfilesProd
     */
    public function wPerfilesProds()
    {
        return $this->hasMany(WPerfilesProd::class, 'id_valor', 'idw_valores_prod');
    }

    /**
     * Relación inversa con WValoresProdIdioma
     */
    public function wValoresProdIdiomas()
    {
        return $this->hasMany(WValoresProdIdioma::class, 'id_valor', 'idw_valores_prod');
    }

    /**
     * Relación: Caracteristica
     * ✅ Usa IDX_WVALPROD_WCARACTPROD (indexado)
     */
    public function caracteristica()
    {
        return $this->belongsTo(WCaracteristicasProd::class, 'id_caracteristica', 'id');
    }

    /**
     * Relación: WAyudas
     * ✅ Usa PK_W_VALORES_PROD (indexado)
     */
    public function wAyudas()
    {
        return $this->belongsTo(WAyudas::class, 'id', 'id');
    }
}
