<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_CAMBIO_ARTICULO_X_AL
 * Tabla de replicación/materialización de Oracle
 */
class RupdCambioArticuloXAl extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_cambio_articulo_x_al';

    protected $primaryKey = 'idcambio_articulo_x_almacen';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
