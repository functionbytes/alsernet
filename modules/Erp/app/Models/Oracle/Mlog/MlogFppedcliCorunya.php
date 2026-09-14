<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_FPPEDCLI_CORUNYA
 * Tabla de replicación/materialización de Oracle
 */
class MlogFppedcliCorunya extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_fppedcli_corunya';

    protected $primaryKey = 'idfppedcli';

    public $incrementing = false;

    public $timestamps = false;
}
