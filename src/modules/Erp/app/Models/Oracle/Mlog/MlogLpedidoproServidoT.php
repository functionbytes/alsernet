<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_LPEDIDOPRO_SERVIDO_T
 * Tabla de replicación/materialización de Oracle
 */
class MlogLpedidoproServidoT extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_lpedidopro_servido_t';

    protected $primaryKey = 'idlpedidopro_servido';

    public $incrementing = false;

    public $timestamps = false;
}
