<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_LPEDIDOPRO_TPVCOR
 * Tabla de replicación/materialización de Oracle
 */
class MlogLpedidoproTpvcor extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_lpedidopro_tpvcor';

    protected $primaryKey = 'idlpedidopro';

    public $incrementing = false;

    public $timestamps = false;
}
