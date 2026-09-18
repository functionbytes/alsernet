<?php

namespace Modules\Erp\Models\Oracle\Configuracion;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Models\Oracle\Otros\CambioArticulo;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla CAMBIO_ARTICULO_X_ALMACEN
 *
 * ÍNDICES DISPONIBLES:
 * PK_CAMBIO_ARTICULO_X_ALMACEN (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCAMBIO_ARTICULO_X_ALMACEN
 */
class CambioArticuloXAlmacen extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'cambio_articulo_x_almacen';

    protected $primaryKey = 'idcambio_articulo_x_almacen';

    public $timestamps = false;

    protected $fillable = [
        'idcambio_articulo', 'idalmacen', 'procesado', 'fprocesado',
    ];

    protected $casts = [
        'fprocesado' => 'datetime',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: CambioArticuloXAlmacen
     * ✅ Usa PK_CAMBIO_ARTICULO_X_ALMACEN (indexado)
     */
    public function cambioArticuloXAlmacen()
    {
        return $this->belongsTo(CambioArticuloXAlmacen::class, 'idcambio_articulo_x_almacen', 'idcambio_articulo_x_almacen');
    }

    /**
     * Relación: CambioArticulo
     * ⚠️  SIN ÍNDICE en IDCAMBIO_ARTICULO
     */
    public function cambioArticulo()
    {
        return $this->belongsTo(CambioArticulo::class, 'idcambio_articulo', 'idcambio_articulo');
    }

    /**
     * Relación: Almacen
     * ⚠️  SIN ÍNDICE en IDALMACEN
     */
    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'idalmacen', 'idalmacen');
    }
}
