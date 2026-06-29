<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_STOCK_CENT_DDLEON
 * Tabla de replicación/materialización de Oracle
 */
class MlogStockCentDdleon extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_stock_cent_ddleon';

    protected $primaryKey = 'idstock';

    public $incrementing = false;

    public $timestamps = false;
}
