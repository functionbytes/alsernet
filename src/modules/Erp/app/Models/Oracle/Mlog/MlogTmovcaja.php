<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_TMOVCAJA
 * Tabla de replicación/materialización de Oracle
 */
class MlogTmovcaja extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_tmovcaja';

    protected $primaryKey = 'idtmovcaja';

    public $incrementing = false;

    public $timestamps = false;
}
