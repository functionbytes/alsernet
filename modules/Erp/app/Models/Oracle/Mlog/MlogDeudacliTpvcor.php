<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_DEUDACLI_TPVCOR
 * Tabla de replicación/materialización de Oracle
 */
class MlogDeudacliTpvcor extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_deudacli_tpvcor';

    protected $primaryKey = 'iddeudacli';

    public $incrementing = false;

    public $timestamps = false;
}
