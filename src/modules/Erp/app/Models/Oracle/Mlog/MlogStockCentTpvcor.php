<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_STOCK_CENT_TPVCOR
 * Tabla de replicación/materialización de Oracle
 */
class MlogStockCentTpvcor extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_stock_cent_tpvcor';

    protected $primaryKey = 'idstock';

    public $incrementing = false;

    public $timestamps = false;
}
