<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_LZONA_POSTAL
 * Tabla de replicación/materialización de Oracle
 */
class MlogLzonaPostal extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_lzona_postal';

    protected $primaryKey = 'idlzona_postal';

    public $incrementing = false;

    public $timestamps = false;
}
