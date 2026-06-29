<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_PEDIDOCLIESTADO
 * Tabla de replicación/materialización de Oracle
 */
class MlogPedidocliestado extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_pedidocliestado';

    protected $primaryKey = 'estado';

    public $incrementing = false;

    public $timestamps = false;
}
