<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_COBROCLI_MONTE2
 * Tabla de replicación/materialización de Oracle
 */
class MlogCobrocliMonte2 extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_cobrocli_monte2';

    protected $primaryKey = 'idcobrocli';

    public $incrementing = false;

    public $timestamps = false;
}
