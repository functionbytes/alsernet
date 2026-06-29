<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_TIPOART
 * Tabla de replicación/materialización de Oracle
 */
class MlogTipoart extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_tipoart';

    protected $primaryKey = 'idtipoart';

    public $incrementing = false;

    public $timestamps = false;
}
