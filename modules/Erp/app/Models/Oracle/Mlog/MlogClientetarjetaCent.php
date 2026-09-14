<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_CLIENTETARJETA_CENT
 * Tabla de replicación/materialización de Oracle
 */
class MlogClientetarjetaCent extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_clientetarjeta_cent';

    protected $primaryKey = 'idclientetarjeta';

    public $incrementing = false;

    public $timestamps = false;
}
