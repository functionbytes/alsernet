<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_TIPOMEDIDA
 * Tabla de replicación/materialización de Oracle
 */
class MlogTipomedida extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_tipomedida';

    protected $primaryKey = 'idtipomedida';

    public $incrementing = false;

    public $timestamps = false;
}
