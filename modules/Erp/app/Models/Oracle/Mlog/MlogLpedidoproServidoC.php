<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_LPEDIDOPRO_SERVIDO_C
 * Tabla de replicación/materialización de Oracle
 */
class MlogLpedidoproServidoC extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_lpedidopro_servido_c';

    protected $primaryKey = 'idlpedidopro_servido_central';

    public $incrementing = false;

    public $timestamps = false;
}
