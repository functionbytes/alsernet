<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_LPEDIDOPRO_CAPTHAYA
 * Tabla de replicación/materialización de Oracle
 */
class MlogLpedidoproCapthaya extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_lpedidopro_capthaya';

    protected $primaryKey = 'idlpedidopro';

    public $incrementing = false;

    public $timestamps = false;
}
