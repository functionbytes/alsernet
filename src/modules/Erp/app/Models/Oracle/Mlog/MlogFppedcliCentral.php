<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_FPPEDCLI_CENTRAL
 * Tabla de replicación/materialización de Oracle
 */
class MlogFppedcliCentral extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_fppedcli_central';

    protected $primaryKey = 'idfppedcli_central';

    public $incrementing = false;

    public $timestamps = false;
}
