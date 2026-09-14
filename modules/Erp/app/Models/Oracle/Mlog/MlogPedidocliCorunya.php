<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_PEDIDOCLI_CORUNYA
 * Tabla de replicación/materialización de Oracle
 */
class MlogPedidocliCorunya extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_pedidocli_corunya';

    protected $primaryKey = 'idpedidocli';

    public $incrementing = false;

    public $timestamps = false;
}
