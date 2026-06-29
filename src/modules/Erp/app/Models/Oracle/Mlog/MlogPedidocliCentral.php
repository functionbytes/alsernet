<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_PEDIDOCLI_CENTRAL
 * Tabla de replicación/materialización de Oracle
 */
class MlogPedidocliCentral extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_pedidocli_central';

    protected $primaryKey = 'idpedidocli_central';

    public $incrementing = false;

    public $timestamps = false;
}
