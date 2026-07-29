<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla STOCK_CENTRAL
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_STOCK_CENTRAL_IDALM (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDALMACEN
 *
 * ✅ IDX_STOCK_CENTRAL_IDART (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDARTICULO
 *
 * PK_STOCK_CENTRAL (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDSTOCK
 */
class Stock extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'stock_central';

    protected $primaryKey = 'idstock';

    public $timestamps = false;

    protected $fillable = [
        'idalmacen', 'idarticulo', 'idteststock', 'estado', 'unidades',
        'not',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Stock
     * ✅ Usa PK_STOCK_CENTRAL (indexado)
     */
    public function stock()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\Stock::class, 'idstock', 'idstock');
    }

    /**
     * Relación: Almacen
     * ✅ Usa IDX_STOCK_CENTRAL_IDALM (indexado)
     */
    public function almacen()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Almacen::class, 'idalmacen', 'idalmacen');
    }

    /**
     * Relación: Articulo
     * ✅ Usa IDX_STOCK_CENTRAL_IDART (indexado)
     */
    public function articulo()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Articulo\Articulo::class, 'idarticulo', 'idarticulo');
    }

    /**
     * Relación: Teststock
     * ⚠️  SIN ÍNDICE en IDTESTSTOCK
     */
    public function teststock()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\Teststock::class, 'idteststock', 'idteststock');
    }
}
