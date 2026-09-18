<?php

namespace Modules\Erp\Models\Oracle\Articulo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Configuracion\Idioma;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla ARTICULOIDIOMA
 *
 * ÍNDICES DISPONIBLES:
 * PK_ARTICULOIDIOMA (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDARTICULOIDIOMA
 */
class Articuloidioma extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'articuloidioma';

    protected $primaryKey = 'idarticuloidioma';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idarticulo', 'ididioma', 'idusuariocre', 'idusuariobaj', 'idusuariomod',
        'estado', 'descripcion', 'descripcioncorta',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Articuloidioma
     * ✅ Usa PK_ARTICULOIDIOMA (indexado)
     */
    public function articuloidioma()
    {
        return $this->belongsTo(Articuloidioma::class, 'idarticuloidioma', 'idarticuloidioma');
    }

    /**
     * Relación: Articulo
     * ⚠️  SIN ÍNDICE en IDARTICULO
     */
    public function articulo()
    {
        return $this->belongsTo(Articulo::class, 'idarticulo', 'idarticulo');
    }

    /**
     * Relación: Idioma
     * ⚠️  SIN ÍNDICE en IDIDIOMA
     */
    public function idioma()
    {
        return $this->belongsTo(Idioma::class, 'ididioma', 'ididioma');
    }
}
