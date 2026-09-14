<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_CLIENTE_SEGURO
 * Tabla de replicación/materialización de Oracle
 */
class MlogClienteSeguro extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_cliente_seguro';

    protected $primaryKey = 'idcliente_seguro';

    public $incrementing = false;

    public $timestamps = false;
}
