<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_DEUDACLI_CAPTHAYA
 * Tabla de replicación/materialización de Oracle
 */
class MlogDeudacliCapthaya extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_deudacli_capthaya';

    protected $primaryKey = 'iddeudacli';

    public $incrementing = false;

    public $timestamps = false;
}
