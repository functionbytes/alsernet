<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla CAMBIO_ARTICULO
 *
 * ÍNDICES DISPONIBLES:
 * PK_CAMBIO_ARTICULO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCAMBIO_ARTICULO
 */
class CambioArticulo extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'cambio_articulo';

    protected $primaryKey = 'idcambio_articulo';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'fecha', 'precio_old', 'precio_new', 'descripcion_old', 'descripcion_new',
        'codigo_old', 'codigo_new', 'tipo', 'idusuariocre', 'idusuariomod',
        'idususariobaj', 'estado', 'idarticulo',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: CambioArticulo
     * ✅ Usa PK_CAMBIO_ARTICULO (indexado)
     */
    public function cambioArticulo()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\CambioArticulo::class, 'idcambio_articulo', 'idcambio_articulo');
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
