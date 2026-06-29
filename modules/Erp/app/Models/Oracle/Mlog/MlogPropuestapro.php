<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_PROPUESTAPRO
 * Tabla de replicación/materialización de Oracle
 */
class MlogPropuestapro extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_propuestapro';

    protected $primaryKey = 'idpropuestapro';

    public $incrementing = false;

    public $timestamps = false;
}
