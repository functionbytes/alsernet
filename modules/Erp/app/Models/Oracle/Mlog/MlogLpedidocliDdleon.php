<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_LPEDIDOCLI_DDLEON
 * Tabla de replicación/materialización de Oracle
 */
class MlogLpedidocliDdleon extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_lpedidocli_ddleon';

    protected $primaryKey = 'idlpedidocli';

    public $incrementing = false;

    public $timestamps = false;
}
