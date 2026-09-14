<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_PERIODO_CUOTA
 * Tabla de replicación/materialización de Oracle
 */
class RupdPeriodoCuota extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_periodo_cuota';

    protected $primaryKey = 'idperiodo_cuota';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
