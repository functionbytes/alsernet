<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_FPPEDCLI_MONTE2
 * Tabla de replicación/materialización de Oracle
 */
class MlogFppedcliMonte2 extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_fppedcli_monte2';

    protected $primaryKey = 'idfppedcli';

    public $incrementing = false;

    public $timestamps = false;
}
