<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_PEDIDOPRO_CORUNYA
 * Tabla de replicación/materialización de Oracle
 */
class MlogPedidoproCorunya extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_pedidopro_corunya';

    protected $primaryKey = 'idpedidopro';

    public $incrementing = false;

    public $timestamps = false;
}
