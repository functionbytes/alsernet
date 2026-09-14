<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_TIPOFOTOGRAFIA
 * Tabla de replicación/materialización de Oracle
 */
class MlogTipofotografia extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_tipofotografia';

    protected $primaryKey = 'idtipofotografia';

    public $incrementing = false;

    public $timestamps = false;
}
