<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_TIPODIRECCION
 * Tabla de replicación/materialización de Oracle
 */
class MlogTipodireccion extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_tipodireccion';

    protected $primaryKey = 'idtipodireccion';

    public $incrementing = false;

    public $timestamps = false;
}
