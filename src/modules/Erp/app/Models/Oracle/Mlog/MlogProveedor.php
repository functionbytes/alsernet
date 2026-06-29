<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_PROVEEDOR
 * Tabla de replicación/materialización de Oracle
 */
class MlogProveedor extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_proveedor';

    protected $primaryKey = 'idproveedor';

    public $incrementing = false;

    public $timestamps = false;
}
