<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_PEDIDOCLI_CAPTHAYA
 * Tabla de replicación/materialización de Oracle
 */
class MlogPedidocliCapthaya extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_pedidocli_capthaya';

    protected $primaryKey = 'idpedidocli';

    public $incrementing = false;

    public $timestamps = false;
}
