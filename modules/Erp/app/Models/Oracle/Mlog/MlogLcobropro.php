<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_LCOBROPRO
 * Tabla de replicación/materialización de Oracle
 */
class MlogLcobropro extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_lcobropro';

    protected $primaryKey = 'idlcobropro';

    public $incrementing = false;

    public $timestamps = false;
}
