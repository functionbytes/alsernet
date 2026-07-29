<?php

namespace Modules\Erp\Models\Oracle\Web;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla W_PRODUCTO
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_REFERENCIA (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: REFERENCIA
 *
 * ✅ IDX_WPRODUCTO_WMODELO (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID_MODELO
 *
 * ✅ IDX_W_PRODUCTO_ARTICULO (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDARTICULO
 *
 * PK_W_PRODUCTO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID
 */
class WProducto extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'w_producto';

    protected $primaryKey = 'id';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'activo', 'precio', 'referencia', 'imagen', 'id_modelo',
        'precio_anterior', 'vendible', 'texto_no_vendible', 'microprecio', 'texto_no_vendible_en',
        'precio_sin_iva', 'precio_anterior_sin_iva', 'unidades_oferta', 'imagen_seo', 'estado',
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
     * Relación inversa con WPerfilesProd
     */
    public function wPerfilesProds()
    {
        return $this->hasMany(WPerfilesProd::class, 'id_producto', 'idw_producto');
    }

    /**
     * Relación inversa con WProductoImagen
     */
    public function wProductoImagens()
    {
        return $this->hasMany(WProductoImagen::class, 'id_producto', 'idw_producto');
    }

    /**
     * Relación: Modelo
     * ✅ Usa IDX_WPRODUCTO_WMODELO (indexado)
     */
    public function modelo()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Web\WModelo::class, 'id_modelo', 'id');
    }

    /**
     * Relación: WAyudas
     * ✅ Usa PK_W_PRODUCTO (indexado)
     */
    public function wAyudas()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Web\WAyudas::class, 'id', 'id');
    }

    /**
     * Relación: Articulo
     * ✅ Usa IDX_W_PRODUCTO_ARTICULO (indexado)
     */
    public function articulo()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Articulo\Articulo::class, 'idarticulo', 'idarticulo');
    }
}
