<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_LREBAJA_X_ALMACEN
 * Tabla de replicación/materialización de Oracle
 */
class RupdLrebajaXAlmacen extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_lrebaja_x_almacen';

    protected $primaryKey = 'idlrebaja_x_almacen';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
