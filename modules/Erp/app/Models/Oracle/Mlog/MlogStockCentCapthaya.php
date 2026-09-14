<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_STOCK_CENT_CAPTHAYA
 * Tabla de replicación/materialización de Oracle
 */
class MlogStockCentCapthaya extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_stock_cent_capthaya';

    protected $primaryKey = 'idstock';

    public $incrementing = false;

    public $timestamps = false;
}
