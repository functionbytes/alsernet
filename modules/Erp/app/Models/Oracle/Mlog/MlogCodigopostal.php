<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_CODIGOPOSTAL
 * Tabla de replicación/materialización de Oracle
 */
class MlogCodigopostal extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_codigopostal';

    protected $primaryKey = 'idcodigopostal';

    public $incrementing = false;

    public $timestamps = false;
}
