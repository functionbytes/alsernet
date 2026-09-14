<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_TIPOPROV
 * Tabla de replicación/materialización de Oracle
 */
class MlogTipoprov extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_tipoprov';

    protected $primaryKey = 'idtipoprov';

    public $incrementing = false;

    public $timestamps = false;
}
