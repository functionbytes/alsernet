<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_TRASPASO_CAPTHAYA
 * Tabla de replicación/materialización de Oracle
 */
class MlogTraspasoCapthaya extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_traspaso_capthaya';

    protected $primaryKey = 'idtraspaso';

    public $incrementing = false;

    public $timestamps = false;
}
