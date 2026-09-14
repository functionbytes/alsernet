<?php

namespace Modules\Erp\Models\Oracle\Articulo;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Models\Oracle\Configuracion\Almacen;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla ARTICULOSTOCKMINMAX
 *
 * ÍNDICES DISPONIBLES:
 * PK_ARTICULOSTOCKMINMAX (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDARTICULOSTOCKMINMAX
 */
class Articulostockminmax extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'articulostockminmax';

    protected $primaryKey = 'idarticulostockminmax';

    public $timestamps = false;

    protected $fillable = [
        'idarticulo', 'stockmintotal', 'stockmaxtotal', 'stockrecomendado', 'idalmacen',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Articulostockminmax
     * ✅ Usa PK_ARTICULOSTOCKMINMAX (indexado)
     */
    public function articulostockminmax()
    {
        return $this->belongsTo(Articulostockminmax::class, 'idarticulostockminmax', 'idarticulostockminmax');
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
     * Relación: Almacen
     * ⚠️  SIN ÍNDICE en IDALMACEN
     */
    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'idalmacen', 'idalmacen');
    }
}
