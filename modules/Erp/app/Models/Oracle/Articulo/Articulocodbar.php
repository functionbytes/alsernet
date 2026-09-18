<?php

namespace Modules\Erp\Models\Oracle\Articulo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla ARTICULOCODBAR
 *
 * ÍNDICES DISPONIBLES:
 * PK_ARTICULOCODBAR (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDARTICULOCODBAR
 */
class Articulocodbar extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'articulocodbar';

    protected $primaryKey = 'idarticulocodbar';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idarticulo', 'codbar', 'estado', 'idusuariocre', 'idusuariomod',
        'idusuariobaj',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Articulocodbar
     * ✅ Usa PK_ARTICULOCODBAR (indexado)
     */
    public function articulocodbar()
    {
        return $this->belongsTo(Articulocodbar::class, 'idarticulocodbar', 'idarticulocodbar');
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
