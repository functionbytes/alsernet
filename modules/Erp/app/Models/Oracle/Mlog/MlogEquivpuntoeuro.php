<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_EQUIVPUNTOEURO
 * Tabla de replicación/materialización de Oracle
 */
class MlogEquivpuntoeuro extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_equivpuntoeuro';

    protected $primaryKey = 'idequivpuntoeuro';

    public $incrementing = false;

    public $timestamps = false;
}
