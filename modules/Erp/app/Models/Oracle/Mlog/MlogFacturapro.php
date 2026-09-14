<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_FACTURAPRO
 * Tabla de replicación/materialización de Oracle
 */
class MlogFacturapro extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_facturapro';

    protected $primaryKey = 'idfacturapro';

    public $incrementing = false;

    public $timestamps = false;
}
