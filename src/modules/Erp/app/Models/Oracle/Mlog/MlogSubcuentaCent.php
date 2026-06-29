<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_SUBCUENTA_CENT
 * Tabla de replicación/materialización de Oracle
 */
class MlogSubcuentaCent extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_subcuenta_cent';

    protected $primaryKey = 'idsubcuenta';

    public $incrementing = false;

    public $timestamps = false;
}
