<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_TCOBRO
 * Tabla de replicación/materialización de Oracle
 */
class MlogTcobro extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_tcobro';

    protected $primaryKey = 'idtcobro';

    public $incrementing = false;

    public $timestamps = false;
}
