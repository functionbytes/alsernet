<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_DEUDACLI_MONTE2
 * Tabla de replicación/materialización de Oracle
 */
class MlogDeudacliMonte2 extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_deudacli_monte2';

    protected $primaryKey = 'iddeudacli';

    public $incrementing = false;

    public $timestamps = false;
}
