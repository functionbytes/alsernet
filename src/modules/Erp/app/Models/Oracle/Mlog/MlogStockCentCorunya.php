<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_STOCK_CENT_CORUNYA
 * Tabla de replicación/materialización de Oracle
 */
class MlogStockCentCorunya extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_stock_cent_corunya';

    protected $primaryKey = 'idstock';

    public $incrementing = false;

    public $timestamps = false;
}
