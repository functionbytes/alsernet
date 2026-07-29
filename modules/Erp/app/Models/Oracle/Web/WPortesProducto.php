<?php

namespace Modules\Erp\Models\Oracle\Web;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla W_PORTES_PRODUCTO
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_WPORTES_PRODUCTO_WPAISES (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID_PAIS
 *
 * ✅ IDX_WPORTES_PRODUCTO_WPRODUCTO (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID_PRODUCTO
 *
 * PK_W_PORTES_PRODUCTO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID
 */
class WPortesProducto extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'w_portes_producto';

    protected $primaryKey = 'id';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'id_producto', 'referencia', 'codigo', 'id_pais', 'estado',
        'idusuariocre', 'idusuariomod', 'idusuariobaja', 'idarticulo',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: WAyudas
     * ✅ Usa PK_W_PORTES_PRODUCTO (indexado)
     */
    public function wAyudas()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Web\WAyudas::class, 'id', 'id');
    }

    /**
     * Relación: Articulo
     * ⚠️  SIN ÍNDICE en IDARTICULO
     */
    public function articulo()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Articulo\Articulo::class, 'idarticulo', 'idarticulo');
    }
}
