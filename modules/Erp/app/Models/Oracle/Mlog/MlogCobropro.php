<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_COBROPRO
 * Tabla de replicación/materialización de Oracle
 */
class MlogCobropro extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_cobropro';

    protected $primaryKey = 'idcobropro';

    public $incrementing = false;

    public $timestamps = false;
}
