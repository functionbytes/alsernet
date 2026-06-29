<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_TIPODIARIO_CENT
 * Tabla de replicación/materialización de Oracle
 */
class MlogTipodiarioCent extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_tipodiario_cent';

    protected $primaryKey = 'idtipodiario';

    public $incrementing = false;

    public $timestamps = false;
}
