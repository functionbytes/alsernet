<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_CLIENTECUOTA
 * Tabla de replicación/materialización de Oracle
 */
class MlogClientecuota extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_clientecuota';

    protected $primaryKey = 'idclientecuota';

    public $incrementing = false;

    public $timestamps = false;
}
