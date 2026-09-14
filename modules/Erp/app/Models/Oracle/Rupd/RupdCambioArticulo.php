<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_CAMBIO_ARTICULO
 * Tabla de replicación/materialización de Oracle
 */
class RupdCambioArticulo extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_cambio_articulo';

    protected $primaryKey = 'idcambio_articulo';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
