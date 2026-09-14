<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla STOCK_CENT_TPVCOR
 *
 * ÍNDICES DISPONIBLES:
 * PK_STOCK_CENT_TPVCOR (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDSTOCK
 */
class StockCentTpvcor extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'stock_cent_tpvcor';

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
}
