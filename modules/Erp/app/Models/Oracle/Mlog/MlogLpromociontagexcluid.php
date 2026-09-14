<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_LPROMOCIONTAGEXCLUID
 * Tabla de replicación/materialización de Oracle
 */
class MlogLpromociontagexcluid extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_lpromociontagexcluid';

    protected $primaryKey = 'idlpromociontagexcluido';

    public $incrementing = false;

    public $timestamps = false;
}
