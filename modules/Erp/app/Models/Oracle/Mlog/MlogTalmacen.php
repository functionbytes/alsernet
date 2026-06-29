<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_TALMACEN
 * Tabla de replicación/materialización de Oracle
 */
class MlogTalmacen extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_talmacen';

    protected $primaryKey = 'idtalmacen';

    public $incrementing = false;

    public $timestamps = false;
}
