<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_TIPOCLIENTE
 * Tabla de replicación/materialización de Oracle
 */
class MlogTipocliente extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_tipocliente';

    protected $primaryKey = 'idtipocliente';

    public $incrementing = false;

    public $timestamps = false;
}
