<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_MANTFIL
 * Tabla de replicación/materialización de Oracle
 */
class MlogMantfil extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_mantfil';

    protected $primaryKey = 'idmantfil';

    public $incrementing = false;

    public $timestamps = false;
}
