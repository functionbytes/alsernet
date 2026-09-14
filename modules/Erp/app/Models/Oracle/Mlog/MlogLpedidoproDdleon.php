<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_LPEDIDOPRO_DDLEON
 * Tabla de replicación/materialización de Oracle
 */
class MlogLpedidoproDdleon extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_lpedidopro_ddleon';

    protected $primaryKey = 'idlpedidopro';

    public $incrementing = false;

    public $timestamps = false;
}
