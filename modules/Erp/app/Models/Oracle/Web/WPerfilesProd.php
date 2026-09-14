<?php

namespace Modules\Erp\Models\Oracle\Web;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Articulo\Articulo;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla W_PERFILES_PROD
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_WPERFILES_PRO_WPERFILES (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID_MODELO
 *
 * ✅ IDX_WPERFILES_PRO_WPRODUCTO (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID_PRODUCTO
 *
 * ✅ IDX_WPERFILES_PRO_WVALORESPROD (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID_VALOR
 *
 * PK_W_PERFILES_PROD (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID
 */
class WPerfilesProd extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'w_perfiles_prod';

    protected $primaryKey = 'id';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'id_producto', 'id_valor', 'id_modelo', 'orden', 'estado',
        'idusuariocre', 'idusuariomod', 'idusuariobaja', 'idarticulo', 'idmodelo',
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
     * Relación con WProducto
     */
    public function _producto()
    {
        return $this->belongsTo(WProducto::class, 'id_producto', 'idw_producto');
    }

    /**
     * Relación con WValoresProd
     */
    public function _valor()
    {
        return $this->belongsTo(WValoresProd::class, 'id_valor', 'idw_valores_prod');
    }

    /**
     * Relación: Modelo
     * ✅ Usa IDX_WPERFILES_PRO_WPERFILES (indexado)
     */
    public function modelo()
    {
        return $this->belongsTo(WModelo::class, 'id_modelo', 'id');
    }

    /**
     * Relación: Producto
     * ✅ Usa IDX_WPERFILES_PRO_WPRODUCTO (indexado)
     */
    public function producto()
    {
        return $this->belongsTo(WProducto::class, 'id_producto', 'id');
    }

    /**
     * Relación: Valor
     * ✅ Usa IDX_WPERFILES_PRO_WVALORESPROD (indexado)
     */
    public function valor()
    {
        return $this->belongsTo(WValoresProd::class, 'id_valor', 'id');
    }

    /**
     * Relación: WAyudas
     * ✅ Usa PK_W_PERFILES_PROD (indexado)
     */
    public function wAyudas()
    {
        return $this->belongsTo(WAyudas::class, 'id', 'id');
    }

    /**
     * Relación: Articulo
     * ⚠️  SIN ÍNDICE en IDARTICULO
     */
    public function articulo()
    {
        return $this->belongsTo(Articulo::class, 'idarticulo', 'idarticulo');
    }
}
