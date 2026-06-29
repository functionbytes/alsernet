<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_STOCK_CENT_MONTE2
 * Tabla de replicación/materialización de Oracle
 */
class MlogStockCentMonte2 extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_stock_cent_monte2';

    protected $primaryKey = 'idstock';

    public $incrementing = false;

    public $timestamps = false;
}
