<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_APUNTE_CENT
 * Tabla de replicación/materialización de Oracle
 */
class MlogApunteCent extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_apunte_cent';

    protected $primaryKey = 'idapunte';

    public $incrementing = false;

    public $timestamps = false;
}
