<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_CALIBRE
 * Tabla de replicación/materialización de Oracle
 */
class MlogCalibre extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_calibre';

    protected $primaryKey = 'idcalibre';

    public $incrementing = false;

    public $timestamps = false;
}
