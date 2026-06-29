<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_TESTSTOCK
 * Tabla de replicación/materialización de Oracle
 */
class MlogTeststock extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_teststock';

    protected $primaryKey = 'idteststock';

    public $incrementing = false;

    public $timestamps = false;
}
