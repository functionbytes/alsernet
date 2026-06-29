<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_CAMBIO_ARTICULO
 * Tabla de replicación/materialización de Oracle
 */
class MlogCambioArticulo extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_cambio_articulo';

    protected $primaryKey = 'idcambio_articulo';

    public $incrementing = false;

    public $timestamps = false;
}
