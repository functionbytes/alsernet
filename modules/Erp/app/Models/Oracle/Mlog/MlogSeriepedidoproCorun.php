<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_SERIEPEDIDOPRO_CORUN
 * Tabla de replicación/materialización de Oracle
 */
class MlogSeriepedidoproCorun extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_seriepedidopro_corun';

    protected $primaryKey = 'idseriepedidopro';

    public $incrementing = false;

    public $timestamps = false;
}
