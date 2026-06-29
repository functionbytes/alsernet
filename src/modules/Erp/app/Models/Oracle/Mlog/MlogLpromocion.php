<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_LPROMOCION
 * Tabla de replicación/materialización de Oracle
 */
class MlogLpromocion extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_lpromocion';

    protected $primaryKey = 'idlpromocion';

    public $incrementing = false;

    public $timestamps = false;
}
