<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_PERIODO_CUOTA
 * Tabla de replicación/materialización de Oracle
 */
class MlogPeriodoCuota extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_periodo_cuota';

    protected $primaryKey = 'idperiodo_cuota';

    public $incrementing = false;

    public $timestamps = false;
}
