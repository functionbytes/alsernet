<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_FPPEDCLI_TPVCOR
 * Tabla de replicación/materialización de Oracle
 */
class MlogFppedcliTpvcor extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_fppedcli_tpvcor';

    protected $primaryKey = 'idfppedcli';

    public $incrementing = false;

    public $timestamps = false;
}
