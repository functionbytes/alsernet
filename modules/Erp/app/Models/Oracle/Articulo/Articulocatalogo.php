<?php

namespace Modules\Erp\Models\Oracle\Articulo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla ARTICULOCATALOGO
 *
 * ÍNDICES DISPONIBLES:
 * PK_ARTICULOCATALOGO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDARTICULOCATALOGO
 */
class Articulocatalogo extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'articulocatalogo';

    protected $primaryKey = 'idarticulocatalogo';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idarticulo', 'idcatalogo', 'idusuariocre', 'idusuariobaj', 'idusuariomod',
        'estado', 'defecto',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Articulocatalogo
     * ✅ Usa PK_ARTICULOCATALOGO (indexado)
     */
    public function articulocatalogo()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Articulo\Articulocatalogo::class, 'idarticulocatalogo', 'idarticulocatalogo');
    }

    /**
     * Relación: Articulo
     * ⚠️  SIN ÍNDICE en IDARTICULO
     */
    public function articulo()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Articulo\Articulo::class, 'idarticulo', 'idarticulo');
    }

    /**
     * Relación: Catalogo
     * ⚠️  SIN ÍNDICE en IDCATALOGO
     */
    public function catalogo()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Catalogo\Catalogo::class, 'idcatalogo', 'idcatalogo');
    }
}
