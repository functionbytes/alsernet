<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_TIPOTELEFONO
 * Tabla de replicación/materialización de Oracle
 */
class MlogTipotelefono extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_tipotelefono';

    protected $primaryKey = 'idtipotelefono';

    public $incrementing = false;

    public $timestamps = false;
}
