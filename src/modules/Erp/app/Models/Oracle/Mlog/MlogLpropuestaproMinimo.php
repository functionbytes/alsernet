<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_LPROPUESTAPRO_MINIMO
 * Tabla de replicación/materialización de Oracle
 */
class MlogLpropuestaproMinimo extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_lpropuestapro_minimo';

    protected $primaryKey = 'idlpropuestapro_minimo';

    public $incrementing = false;

    public $timestamps = false;
}
