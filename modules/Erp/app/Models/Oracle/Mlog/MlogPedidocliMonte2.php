<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_PEDIDOCLI_MONTE2
 * Tabla de replicación/materialización de Oracle
 */
class MlogPedidocliMonte2 extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_pedidocli_monte2';

    protected $primaryKey = 'idpedidocli';

    public $incrementing = false;

    public $timestamps = false;
}
