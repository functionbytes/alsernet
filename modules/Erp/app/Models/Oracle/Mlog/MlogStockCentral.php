<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_STOCK_CENTRAL
 * Tabla de replicación/materialización de Oracle
 */
class MlogStockCentral extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_stock_central';

    protected $primaryKey = 'idstock';

    public $incrementing = false;

    public $timestamps = false;
}
