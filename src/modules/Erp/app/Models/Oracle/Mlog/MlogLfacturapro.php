<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_LFACTURAPRO
 * Tabla de replicación/materialización de Oracle
 */
class MlogLfacturapro extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_lfacturapro';

    protected $primaryKey = 'idlfacturapro';

    public $incrementing = false;

    public $timestamps = false;
}
