<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_LPEDIDOPRO_SERVIDO_M
 * Tabla de replicación/materialización de Oracle
 */
class MlogLpedidoproServidoM extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_lpedidopro_servido_m';

    protected $primaryKey = 'idlpedidopro_servido';

    public $incrementing = false;

    public $timestamps = false;
}
