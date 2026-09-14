<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_TIPOALBARANCLI
 * Tabla de replicación/materialización de Oracle
 */
class MlogTipoalbarancli extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_tipoalbarancli';

    protected $primaryKey = 'idtipoalbarancli';

    public $incrementing = false;

    public $timestamps = false;
}
