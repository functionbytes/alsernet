<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_DEUDAPRO
 * Tabla de replicación/materialización de Oracle
 */
class MlogDeudapro extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_deudapro';

    protected $primaryKey = 'iddeudapro';

    public $incrementing = false;

    public $timestamps = false;
}
