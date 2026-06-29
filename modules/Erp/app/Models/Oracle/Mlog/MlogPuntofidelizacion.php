<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_PUNTOFIDELIZACION
 * Tabla de replicación/materialización de Oracle
 */
class MlogPuntofidelizacion extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_puntofidelizacion';

    protected $primaryKey = 'idpuntofidelizacion';

    public $incrementing = false;

    public $timestamps = false;
}
