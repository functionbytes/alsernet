<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_PEDIDOPRO_MONTE2
 * Tabla de replicación/materialización de Oracle
 */
class MlogPedidoproMonte2 extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_pedidopro_monte2';

    protected $primaryKey = 'idpedidopro';

    public $incrementing = false;

    public $timestamps = false;
}
