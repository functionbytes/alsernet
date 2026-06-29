<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_TIPOPEDIDOPROVEEDOR
 * Tabla de replicación/materialización de Oracle
 */
class MlogTipopedidoproveedor extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_tipopedidoproveedor';

    protected $primaryKey = 'idtipopedidoprov';

    public $incrementing = false;

    public $timestamps = false;
}
