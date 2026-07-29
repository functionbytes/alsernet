<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla STOCK_CENTRAL_WEB_HIST
 */
class StockCentralWebHist extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'stock_central_web_hist';

    protected $primaryKey = 'idarticulo';

    public $timestamps = false;

    protected $fillable = [
        'unidades', 'idstock_central_web', 'fecha',
    ];

    protected $casts = [
        'fecha' => 'datetime',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Articulo
     * ⚠️  SIN ÍNDICE en IDARTICULO
     */
    public function articulo()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Articulo\Articulo::class, 'idarticulo', 'idarticulo');
    }

    /**
     * Relación: StockCentralWeb
     * ⚠️  SIN ÍNDICE en IDSTOCK_CENTRAL_WEB
     */
    public function stockCentralWeb()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\StockCentralWeb::class, 'idstock_central_web', 'idstock_central_web');
    }
}
