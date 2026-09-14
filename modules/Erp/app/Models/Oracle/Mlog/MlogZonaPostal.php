<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_ZONA_POSTAL
 * Tabla de replicación/materialización de Oracle
 */
class MlogZonaPostal extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_zona_postal';

    protected $primaryKey = 'idzona_postal';

    public $incrementing = false;

    public $timestamps = false;
}
