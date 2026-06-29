<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_W_CARACTERISTICAS_PR1
 * Tabla de replicación/materialización de Oracle
 */
class MlogWCaracteristicasPr1 extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_w_caracteristicas_pr1';

    protected $primaryKey = 'id';

    public $incrementing = false;

    public $timestamps = false;
}
