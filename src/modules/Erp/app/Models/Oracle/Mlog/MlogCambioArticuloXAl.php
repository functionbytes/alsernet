<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_CAMBIO_ARTICULO_X_AL
 * Tabla de replicación/materialización de Oracle
 */
class MlogCambioArticuloXAl extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_cambio_articulo_x_al';

    protected $primaryKey = 'idcambio_articulo_x_almacen';

    public $incrementing = false;

    public $timestamps = false;
}
