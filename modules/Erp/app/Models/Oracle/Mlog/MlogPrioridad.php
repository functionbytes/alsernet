<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_PRIORIDAD
 * Tabla de replicación/materialización de Oracle
 */
class MlogPrioridad extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_prioridad';

    protected $primaryKey = 'idprioridad';

    public $incrementing = false;

    public $timestamps = false;
}
