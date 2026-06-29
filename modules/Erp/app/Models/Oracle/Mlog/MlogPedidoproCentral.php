<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_PEDIDOPRO_CENTRAL
 * Tabla de replicación/materialización de Oracle
 */
class MlogPedidoproCentral extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_pedidopro_central';

    protected $primaryKey = 'idpedidopro_central';

    public $incrementing = false;

    public $timestamps = false;
}
