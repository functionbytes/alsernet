<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_PEDIDOPRO_TPVCOR
 * Tabla de replicación/materialización de Oracle
 */
class MlogPedidoproTpvcor extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_pedidopro_tpvcor';

    protected $primaryKey = 'idpedidopro';

    public $incrementing = false;

    public $timestamps = false;
}
