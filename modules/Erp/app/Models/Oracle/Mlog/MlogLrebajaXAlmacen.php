<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_LREBAJA_X_ALMACEN
 * Tabla de replicación/materialización de Oracle
 */
class MlogLrebajaXAlmacen extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_lrebaja_x_almacen';

    protected $primaryKey = 'idlrebaja_x_almacen';

    public $incrementing = false;

    public $timestamps = false;
}
