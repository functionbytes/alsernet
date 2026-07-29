<?php

namespace Modules\Erp\Models\Oracle\Articulo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Catalogo\CatalogoImpreso;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla ARTICULO_CATALOGO_IMPRESO
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_ARTICULO_CATA_IMPR_IDARTIC (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDARTICULO
 *
 * ✅ IDX_ARTICULO_CATA_IMPR_IDCATAI (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCATALOGO_IMPRESO
 *
 * PK_ARTICULO_CATALOGO_IMPRESO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDARTICULO_CATALOGOIMPRESO
 */
class ArticuloCatalogoImpreso extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'articulo_catalogo_impreso';

    protected $primaryKey = 'idarticulo_catalogoimpreso';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idarticulo', 'idcatalogo_impreso', 'pagina', 'idusuariocre', 'idusuariobaj',
        'idusuariomod', 'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación con CatalogoImpreso
     */
    public function catalogo_impreso()
    {
        return $this->belongsTo(CatalogoImpreso::class, 'idcatalogo_impreso', 'idcatalogo_impreso');
    }

    /**
     * Relación: CatalogoImpreso
     * ✅ Usa IDX_ARTICULO_CATA_IMPR_IDCATAI (indexado)
     */
    public function catalogoImpreso()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Catalogo\CatalogoImpreso::class, 'idcatalogo_impreso', 'idcatalogo_impreso');
    }

    /**
     * Relación: ArticuloCatalogoimpreso
     * ✅ Usa PK_ARTICULO_CATALOGO_IMPRESO (indexado)
     */
    public function articuloCatalogoimpreso()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Articulo\ArticuloCatalogoImpreso::class, 'idarticulo_catalogoimpreso', 'idarticulo_catalogoimpreso');
    }

    /**
     * Relación: Articulo
     * ✅ Usa IDX_ARTICULO_CATA_IMPR_IDARTIC (indexado)
     */
    public function articulo()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Articulo\Articulo::class, 'idarticulo', 'idarticulo');
    }
}
