<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_CLIENTECUENTA_CENT
 * Tabla de replicación/materialización de Oracle
 */
class MlogClientecuentaCent extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_clientecuenta_cent';

    protected $primaryKey = 'idclientecuenta';

    public $incrementing = false;

    public $timestamps = false;
}
