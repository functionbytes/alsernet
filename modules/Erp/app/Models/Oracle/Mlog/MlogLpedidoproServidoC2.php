<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_LPEDIDOPRO_SERVIDO_C2
 * Tabla de replicación/materialización de Oracle
 */
class MlogLpedidoproServidoC2 extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_lpedidopro_servido_c2';

    protected $primaryKey = 'idlpedidopro_servido';

    public $incrementing = false;

    public $timestamps = false;
}
