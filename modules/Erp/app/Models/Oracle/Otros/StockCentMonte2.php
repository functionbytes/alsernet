<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla STOCK_CENT_MONTE2
 *
 * ÍNDICES DISPONIBLES:
 * PK_STOCK_CENT_MONTE2 (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDSTOCK
 */
class StockCentMonte2 extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'stock_cent_monte2';

    protected $primaryKey = 'idstock';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idalmacen', 'idarticulo', 'idteststock', 'estado', 'unidades',
        'not', 'idalmacen_creacion',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Stock
     * ✅ Usa PK_STOCK_CENT_MONTE2 (indexado)
     */
    public function stock()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\Stock::class, 'idstock', 'idstock');
    }

    /**
     * Relación: Almacen
     * ⚠️  SIN ÍNDICE en IDALMACEN
     */
    public function almacen()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Almacen::class, 'idalmacen', 'idalmacen');
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
     * Relación: Teststock
     * ⚠️  SIN ÍNDICE en IDTESTSTOCK
     */
    public function teststock()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\Teststock::class, 'idteststock', 'idteststock');
    }
}
