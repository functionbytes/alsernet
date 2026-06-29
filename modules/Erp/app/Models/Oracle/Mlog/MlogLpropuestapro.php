<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_LPROPUESTAPRO
 * Tabla de replicación/materialización de Oracle
 */
class MlogLpropuestapro extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_lpropuestapro';

    protected $primaryKey = 'idlpropuestapro';

    public $incrementing = false;

    public $timestamps = false;
}
