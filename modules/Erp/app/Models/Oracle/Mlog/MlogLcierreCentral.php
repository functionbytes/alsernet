<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_LCIERRE_CENTRAL
 * Tabla de replicación/materialización de Oracle
 */
class MlogLcierreCentral extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_lcierre_central';

    protected $primaryKey = 'idlcierre';

    public $incrementing = false;

    public $timestamps = false;
}
