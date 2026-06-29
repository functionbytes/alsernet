<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_ASIENTO_CENT
 * Tabla de replicación/materialización de Oracle
 */
class MlogAsientoCent extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_asiento_cent';

    protected $primaryKey = 'idasiento';

    public $incrementing = false;

    public $timestamps = false;
}
