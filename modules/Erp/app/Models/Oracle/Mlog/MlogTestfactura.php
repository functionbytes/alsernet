<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_TESTFACTURA
 * Tabla de replicación/materialización de Oracle
 */
class MlogTestfactura extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_testfactura';

    protected $primaryKey = 'idtestfactura';

    public $incrementing = false;

    public $timestamps = false;
}
