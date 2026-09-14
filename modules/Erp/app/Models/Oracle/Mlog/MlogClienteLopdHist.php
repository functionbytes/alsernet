<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_CLIENTE_LOPD_HIST
 * Tabla de replicación/materialización de Oracle
 */
class MlogClienteLopdHist extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_cliente_lopd_hist';

    protected $primaryKey = 'idcliente_lopd_hist';

    public $incrementing = false;

    public $timestamps = false;
}
